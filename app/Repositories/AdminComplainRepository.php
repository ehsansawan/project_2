<?php

namespace App\Repositories;

use App\Models\Complain;
use App\Models\ComplainEndorsement;

class AdminComplainRepository
{
    public function getComplains(array $filters, int $page, int $pageSize): array
    {
        $query = Complain::with(['category', 'user.profile', 'pin', 'media']);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sort = $filters['sort'] ?? 'priority';
        $sortColumn = match ($sort) {
            'priority' => 'priority_score',
            'newest', 'oldest' => 'created_at',
            default => 'priority_score',
        };
        $query->orderBy($sortColumn, $sort === 'oldest' ? 'asc' : 'desc');

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $complain) {
            $items[] = $this->format($complain);
        }

        return [
            'current_page' => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'has_more_pages' => $paginator->hasMorePages(),
            'data' => $items,
        ];
    }

    public function find(int $id): ?Complain
    {
        return Complain::with(['category', 'user.profile', 'pin', 'media'])->find($id);
    }

    public function update(int $id, array $data): void
    {
        Complain::where('id', $id)->update($data);
    }

    public function votesCount(int $complainId): int
    {
        return ComplainEndorsement::where('complain_id', $complainId)
            ->where('action', 'support')
            ->count();
    }

    private function format(Complain $complain): array
    {
        return [
            'id' => $complain->id,
            'title' => $complain->title,
            'description' => $complain->description,
            'type' => $complain->type,
            'status' => $complain->status,
            'priority_score' => $complain->priority_score,
            'decision_reason' => $complain->decision_reason,
            'votes_count' => $this->votesCount($complain->id),
            'category' => $complain->category ? [
                'id' => $complain->category->id,
                'name' => $complain->category->name,
                'weight' => $complain->category->weight,
            ] : null,
            'location' => $complain->pin ? [
                'latitude' => $complain->pin->latitude,
                'longitude' => $complain->pin->longitude,
            ] : null,
            'submitted_by' => $complain->user ? [
                'id' => $complain->user->id,
                'first_name' => $complain->user->first_name,
                'last_name' => $complain->user->last_name,
                'image' => $complain->user->profile ? $complain->user->profile->image : null,
            ] : null,
            'media' => $complain->media->map(fn ($m) => [
                'id' => $m->id,
                'file_path' => $m->file_path,
                'media_type' => $m->media_type,
                'file_url' => str_starts_with($m->file_path, 'http://') || str_starts_with($m->file_path, 'https://')
                    ? $m->file_path
                    : asset('storage/' . $m->file_path),
            ])->toArray(),
            'created_at' => $complain->created_at,
            'updated_at' => $complain->updated_at,
        ];
    }
}