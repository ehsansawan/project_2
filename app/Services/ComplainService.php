<?php

namespace App\Services;

use App\Repositories\ComplainRepository;
use App\Traits\PictureTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Complain;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ComplainService
{
    use PictureTrait;

    private ComplainRepository $complainRepository;

    public function __construct(ComplainRepository $complainRepository)
    {
        $this->complainRepository = $complainRepository;
    }

    public function createComplain(array $validatedData, int $userId): array
    {

        $status = match ($validatedData['type']) {
            'emergency' => 'published',
            'collective' => 'under_review',
            default => 'pending',
        };

        return DB::transaction(function () use ($validatedData, $userId, $status) {
            // 1. إنشاء الـ Pin
            $pin = $this->complainRepository->createMapPin(
                $validatedData['latitude'],
                $validatedData['longitude']
            );

            // 2. رفع المرفقات باستخدام الـ Trait
            $mediaPaths = [];
            if (isset($validatedData['media'])) {
                foreach ($validatedData['media'] as $file) {
                    $isVideo = str_starts_with($file->getMimeType(), 'video/');

                    // تحديد المجلد بناءً على النوع (صور أم فيديو)
                    $directory = $isVideo ? 'complains/videos' : 'complains/images';
                    $type = $isVideo ? 'video' : 'image';

                    // استدعاء الدالة من الـ Trait (سطر واحد فقط!)
                    $path = self::storePicture($file, $directory);

                    $mediaPaths[] = [
                        'file_path' => $path, // المسار النسبي سيُحفظ في الـ DB
                        'media_type' => $type,
                    ];
                }
            }

            // 3. إنشاء الشكوى
            $complainData = [
                'user_id' => $userId,
                'title' => $validatedData['title'],
                'description' => $validatedData['description'] ?? null,
                'type' => $validatedData['type'],
                'category_id' => $validatedData['category_id'],
                'status' => $status,
                'pin_id' => $pin->id,
            ];

            $complain = $this->complainRepository->createComplain($complainData);

            // 4. ربط المرفقات
            if (!empty($mediaPaths)) {
                $this->complainRepository->attachMedia($complain->id, $mediaPaths);
            }

            $complain->load('media', 'category', 'pin');

            return [
                'data' => $complain,
                'message' => 'Complaint created successfully',
                'code' => 201,
            ];
        });
    }

    public function getPublishedComplains(array $filters, int $userId): array
    {
        $result = $this->complainRepository->getPublishedComplains($filters, $userId);

        return [
            'data' => $result,
            'message' => 'Published complaints retrieved successfully',
            'code' => 200,
        ];
    }

    public function getUserComplains(int $userId,array $filters): array
    {
        $result = $this->complainRepository->getUserComplains($userId,$filters);

        return [
            'data' => $result['data'],
            'message' => 'User complaints retrieved successfully',
            'code' => 200,
        ];
    }

    public function vote(int $complainId, int $userId): array
    {
        $complain = $this->complainRepository->findComplain($complainId);

        if (!$complain || $complain->status !== 'published') {
            throw new NotFoundHttpException('Complaint not found or not published');
        }

        if ($this->complainRepository->hasUserVoted($complainId, $userId)) {
            throw new ConflictHttpException('You have already voted on this complaint');
        }

        DB::transaction(function () use ($complainId, $userId) {
            $this->complainRepository->addVote($complainId, $userId);
            $newScore = $this->complainRepository->calculatePriorityScore($complainId);
            $this->complainRepository->updatePriorityScore($complainId, $newScore);
        });

        return [
            'data' => [],
            'message' => 'Vote recorded successfully',
            'code' => 201,
        ];
    }

    public function unvote(int $complainId, int $userId): array
    {
        $complain = $this->complainRepository->findComplain($complainId);

        if (!$complain || $complain->status !== 'published') {
            throw new NotFoundHttpException('Complaint not found or not published');
        }

        if (!$this->complainRepository->hasUserVoted($complainId, $userId)) {
            throw new ConflictHttpException('You have not voted on this complaint');
        }

        DB::transaction(function () use ($complainId, $userId) {
            $this->complainRepository->removeVote($complainId, $userId);
            $newScore = $this->complainRepository->calculatePriorityScore($complainId);
            $this->complainRepository->updatePriorityScore($complainId, $newScore);
        });

        return [
            'data' => [],
            'message' => 'Vote removed successfully',
            'code' => 200,
        ];
    }
}
