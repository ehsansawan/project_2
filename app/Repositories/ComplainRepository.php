<?php

namespace App\Repositories;

use App\Models\Complain;
use App\Models\ComplainMedia;
use App\Models\MapPin;
use App\Models\ComplainEndorsement;

class ComplainRepository
{
    public function createMapPin(float $latitude, float $longitude): MapPin
    {
        return MapPin::query()->create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'type' => 'other',
        ]);
    }

    public function createComplain(array $data): Complain
    {
        return Complain::query()->create($data);
    }

    public function attachMedia(int $complainId, array $mediaPaths): void
    {
        foreach ($mediaPaths as $media) {
            ComplainMedia::query()->create([
                'complain_id' => $complainId,
                'file_path' => $media['file_path'],
                'media_type' => $media['media_type'],
            ]);
        }
    }
    public function getPublishedComplains(array $filters, int $userId): array
    {
        $query = Complain::query()
            ->where('status', 'published');

        // الفلاتر
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // الترتيب
        $sort = $filters['sort'] ?? 'priority';
        $sortColumn = match ($sort) {
            'priority' => 'priority_score',
            'newest', 'oldest' => 'created_at',
            default => 'priority_score',
        };
        $sortDirection = ($sort === 'oldest') ? 'asc' : 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        // تنفيذ الاستعلام
        $complains = $query->paginate($pageSize = $filters['page_size'] ?? 15, ['*'], 'page', $filters['page'] ?? 1);

        // بناء البيانات يدوياً (بنفس طريقة Archive History)
        $items = [];
        foreach ($complains->items() as $complain) {
            $items[] = $this->formatComplain($complain, $userId);
        }

        return [
            'data' => $items,
            'current_page' => $complains->currentPage(),
            'per_page' => $complains->perPage(),
            'total' => $complains->total(),
            'last_page' => $complains->lastPage(),
        ];
    }
    public function getUserComplains(int $userId, array $filters): array
    {
        $complains = Complain::with(['category', 'pin', 'media'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($pageSize = $filters['page_size'] ?? 15, ['*'], 'page', $filters['page'] ?? 1);

        $complains->getCollection()->transform(function ($complain) {
            return [
                'id' => $complain->id,
                'title' => $complain->title,
                'description' => $complain->description,
                'type' => $complain->type,
                'status' => $complain->status,
                'priority_score' => $complain->priority_score,
                'category' => [
                    'id' => $complain->category->id,
                    'name' => $complain->category->name,
                ],
                'location' => $complain->pin ? [
                    'latitude' => $complain->pin->latitude,
                    'longitude' => $complain->pin->longitude,
                ] : null,
                'media' => $complain->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'file_path' => $media->file_path,
                        'media_type' => $media->media_type,
                        'file_url' => str_starts_with($media->file_path, 'http://') || str_starts_with($media->file_path, 'https://')
                            ? $media->file_path
                            : asset('storage/' . $media->file_path),
                    ];
                }),
                'created_at' => $complain->created_at,
                'updated_at' => $complain->updated_at,
            ];
        });

        return [
            'data' => $complains->items(),
            'current_page' => $complains->currentPage(),
            'total' => $complains->total(),
            'per_page' => $complains->perPage(),
            'last_page' => $complains->lastPage(),
            
        ];
    }

    public function addVote(int $complainId, int $userId): ComplainEndorsement
    {
        return ComplainEndorsement::create([
            'complain_id' => $complainId,
            'user_id' => $userId,
            'action' => 'support',
        ]);
    }

    public function removeVote(int $complainId, int $userId): bool
    {
        return ComplainEndorsement::where('complain_id', $complainId)
            ->where('user_id', $userId)
            ->where('action', 'support')
            ->delete();
    }

    public function calculatePriorityScore(int $complainId): float
    {
        // ✅ التغيير هنا: user.profile بدلاً من category.user.profile
        $complain = Complain::with('user.profile')->findOrFail($complainId);

        $votersIds = ComplainEndorsement::where('complain_id', $complainId)
            ->where('action', 'support')
            ->pluck('user_id');

        if ($votersIds->isEmpty()) {
            // القيمة الافتراضية: مؤشر مواطنة مقدم الشكوى
            $creatorScore = ($complain->user && $complain->user->profile)
                ? $complain->user->profile->citizenship_score
                : 0;
            return (float) $creatorScore;
        }

        $avgScore = \App\Models\Profile::whereIn('user_id', $votersIds)
            ->avg('citizenship_score');

        return round($avgScore ?? 0, 2);
    }
    
    public function updatePriorityScore(int $complainId, float $score): void
    {
        Complain::where('id', $complainId)->update(['priority_score' => $score]);
    }

    public function findComplain(int $id): ?Complain
    {
        return Complain::find($id);
    }

    public function hasUserVoted(int $complainId, int $userId): bool
    {
        return ComplainEndorsement::where('complain_id', $complainId)
            ->where('user_id', $userId)
            ->where('action', 'support')
            ->exists();
    }

    private function formatComplain($complain, int $userId): array
    {
        // تحميل العلاقات بشكل كسول (Lazy Loading) لتجنب المشاكل
        $category = $complain->category;
        $user = $complain->user;
        $pin = $complain->pin;
        $media = $complain->media;

        // حساب عدد الأصوات
        $votesCount = ComplainEndorsement::where('complain_id', $complain->id)
            ->where('action', 'support')
            ->count();

        $hasVoted = ComplainEndorsement::where('complain_id', $complain->id)
            ->where('user_id', $userId)
            ->where('action', 'support')
            ->exists();

        return [
            'id' => $complain->id,
            'title' => $complain->title,
            'description' => $complain->description,
            'type' => $complain->type,
            'status' => $complain->status,
            'priority_score' => $complain->priority_score,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
            ] : null,
            'location' => $pin ? [
                'latitude' => $pin->latitude,
                'longitude' => $pin->longitude,
            ] : null,
            'submitted_by' => $user ? [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ] : null,
            'votes_count' => $votesCount,
            'has_voted' => $hasVoted,
            'media' => $media ? $media->map(function ($m) {
                return [
                    'id' => $m->id,
                    'file_path' => $m->file_path,
                    'media_type' => $m->media_type,
                    'file_url' => str_starts_with($m->file_path, 'http://') || str_starts_with($m->file_path, 'https://')
                        ? $m->file_path
                        : asset('storage/' . $m->file_path),
                ];
            })->toArray() : [],
            'created_at' => $complain->created_at,
            'updated_at' => $complain->updated_at,
        ];
    }
}