<?php

namespace App\Services;

use App\Repositories\ComplainRepository;
use App\Traits\PictureTrait;
use Illuminate\Support\Facades\DB;

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
            default => 'submitted',
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
}