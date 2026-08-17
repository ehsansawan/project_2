<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\News;
use App\Models\Notification;
use App\Repositories\NewsRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminNewsService
{
    private NewsRepository $repository;

    public function __construct(NewsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function store(array $validated, int $employeeId): array
    {
        $news = DB::transaction(function () use ($validated, $employeeId) {
            $news = $this->repository->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'type' => $validated['type'],
                'target_audience' => $validated['target_audience'] ?? 'all',
                'created_by' => $employeeId,
                'status' => 'pending_review',
            ]);

            if (isset($validated['image'])) {
                $path = $validated['image']->store('news/images', 'public');
                $this->repository->attachImage($news, $path);
            }

            if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
                $pin = $this->repository->createPin(
                    (float) $validated['latitude'],
                    (float) $validated['longitude']
                );
                $this->repository->attachPin($news, $pin->id);
            }

            AuditLog::create([
                'user_id' => $employeeId,
                'auditable_type' => News::class,
                'auditable_id' => $news->id,
                'action' => 'create',
                'details' => 'News submitted for review',
            ]);

            return $news;
        });

        return [
            'data' => $this->repository->find($news->id),
            'message' => 'News created and submitted for review',
            'code' => 201,
        ];
    }

    public function list(array $filters): array
    {
        $result = $this->repository->getForAdmin(
            $filters,
            $filters['page'] ?? 1,
            $filters['page_size'] ?? 15
        );

        return [
            'data' => $result,
            'message' => 'News retrieved successfully',
            'code' => 200,
        ];
    }

    public function details(int $id): array
    {
        $news = $this->repository->find($id);

        if (!$news) {
            throw new NotFoundHttpException('News not found');
        }

        return [
            'data' => $news,
            'message' => 'News details retrieved successfully',
            'code' => 200,
        ];
    }

    public function review(int $id, array $validated, int $managerId): array
    {
        $news = $this->repository->find($id);

        if (!$news) {
            throw new NotFoundHttpException('News not found');
        }

        if ($news->status !== 'pending_review') {
            throw new ConflictHttpException('This content has already been reviewed');
        }

        $approve = $validated['action'] === 'approve';
        $newStatus = $approve ? 'approved' : 'rejected';

        DB::transaction(function () use ($news, $approve, $newStatus, $validated, $managerId) {
            $this->repository->update($news->id, [
                'status' => $newStatus,
                'rejection_reason' => $validated['rejection_reason'] ?? null,
                'reviewed_by' => $managerId,
                'reviewed_at' => now(),
                'published_at' => $approve ? now() : null,
            ]);

            AuditLog::create([
                'user_id' => $managerId,
                'auditable_type' => News::class,
                'auditable_id' => $news->id,
                'action' => $approve ? 'approve' : 'reject',
                'details' => json_encode([
                    'from' => 'pending_review',
                    'to' => $newStatus,
                    'reason' => $validated['rejection_reason'] ?? null,
                ]),
            ]);

            Notification::create([
                'user_id' => $news->created_by,
                'title' => $approve ? 'News Approved' : 'News Rejected',
                'body' => $approve
                    ? "Your content \"{$news->title}\" has been published."
                    : "Your content \"{$news->title}\" was rejected. Reason: {$validated['rejection_reason']}",
            ]);
        });

        return [
            'data' => $this->repository->find($id),
            'message' => $approve ? 'News approved and published' : 'News rejected',
            'code' => 200,
        ];
    }
}