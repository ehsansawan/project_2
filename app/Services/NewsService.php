<?php

namespace App\Services;

use App\Repositories\NewsRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsService
{
    private NewsRepository $repository;

    public function __construct(NewsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function list(array $filters): array
    {
        $result = $this->repository->getPublished(
            $filters,
            $filters['page'] ?? 1,
            $filters['page_size'] ?? 15
        );

        return [
            'data' => $result,
            'message' => 'Published news retrieved successfully',
            'code' => 200,
        ];
    }

    public function details(int $id): array
    {
        $news = $this->repository->find($id);

        if (!$news || $news->status !== 'approved') {
            throw new NotFoundHttpException('News not found');
        }

        return [
            'data' => $news,
            'message' => 'News details retrieved successfully',
            'code' => 200,
        ];
    }
}