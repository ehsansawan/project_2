<?php

namespace App\Repositories;

use App\Models\MapPin;
use App\Models\News;

class NewsRepository
{
    public function create(array $data): News
    {
        return News::create($data);
    }

    public function attachImage(News $news, string $path): void
    {
        $news->media()->create([
            'file_path' => $path,
            'media_type' => 'image',
        ]);
    }

    public function attachPin(News $news, int $pinId): void
    {
        $news->update(['pin_id' => $pinId]);
    }

    public function createPin(float $latitude, float $longitude): MapPin
    {
        return MapPin::create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'type' => 'other',
        ]);
    }

    public function update(int $id, array $data): void
    {
        News::where('id', $id)->update($data);
    }

    public function find(int $id): ?News
    {
        return News::with(['creator', 'reviewer', 'pin', 'media'])->find($id);
    }

    public function getPublished(array $filters, int $page, int $pageSize): array
    {
        $query = News::with(['pin', 'media'])
            ->where('status', 'approved');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $query->orderByDesc('published_at');

        return $this->paginate($query, $page, $pageSize, false);
    }

    public function getForAdmin(array $filters, int $page, int $pageSize): array
    {
        $query = News::with(['creator', 'reviewer', 'pin', 'media']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $query->orderByDesc('created_at');

        return $this->paginate($query, $page, $pageSize, true);
    }

    private function paginate($query, int $page, int $pageSize, bool $adminFields): array
    {
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $news) {
            $items[] = $this->format($news, $adminFields);
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

    private function format(News $news, bool $adminFields): array
    {
        $data = [
            'id' => $news->id,
            'title' => $news->title,
            'description' => $news->description,
            'type' => $news->type,
            'published_at' => $news->published_at,
            'location' => $news->pin ? [
                'latitude' => $news->pin->latitude,
                'longitude' => $news->pin->longitude,
            ] : null,
            'media' => $news->media->map(fn ($m) => [
                'id' => $m->id,
                'file_path' => $m->file_path,
                'media_type' => $m->media_type,
                'file_url' => str_starts_with($m->file_path, 'http://') || str_starts_with($m->file_path, 'https://')
                    ? $m->file_path
                    : asset('storage/' . $m->file_path),
            ])->toArray(),
        ];

        if ($adminFields) {
            $data['status'] = $news->status;
            $data['rejection_reason'] = $news->rejection_reason;
            $data['created_by'] = $news->creator ? [
                'id' => $news->creator->id,
                'first_name' => $news->creator->first_name,
                'last_name' => $news->creator->last_name,
            ] : null;
            $data['reviewed_by'] = $news->reviewer ? [
                'id' => $news->reviewer->id,
                'first_name' => $news->reviewer->first_name,
                'last_name' => $news->reviewer->last_name,
            ] : null;
            $data['reviewed_at'] = $news->reviewed_at;
            $data['created_at'] = $news->created_at;
        }

        return $data;
    }
}