<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Models\KnowledgeArticle;
use InvalidArgumentException;

final class KnowledgeArticleService
{
    public function __construct(private readonly KnowledgeArticle $model)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public function listPublished(array $filters, int $page, int $perPage = 10): array
    {
        return $this->model->paginate($filters, $page, $perPage, false);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public function listManagement(array $filters, int $page, int $perPage = 10): array
    {
        return $this->model->paginate($filters, $page, $perPage, true);
    }

    /** @return list<string> */
    public function categories(): array
    {
        return $this->model->categories(false);
    }

    /** @return array<string, mixed>|null */
    public function findPublished(string $identifier): ?array
    {
        $value = trim($identifier);
        if ($value === '') {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id !== false && $id !== null) {
            $article = $this->model->findById((int) $id);
            if ($article !== null && ($article['status'] ?? '') === 'published') {
                return $article;
            }
        }

        return $this->model->findPublishedBySlug($value);
    }

    /** @return array<string, mixed>|null */
    public function findForAdmin(int $id): ?array
    {
        return $this->model->findById($id);
    }

    /** @param array<string, mixed> $input */
    public function validate(array $input): array
    {
        $errors = [];

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 150) {
            $errors['title'] = 'Title is required and must be 150 characters or fewer.';
        }

        $slug = trim((string) ($input['slug'] ?? ''));
        if ($slug === '') {
            $errors['slug'] = 'Slug is required.';
        } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors['slug'] = 'Slug may only contain lowercase letters, numbers, and hyphens.';
        }

        $category = trim((string) ($input['category'] ?? 'general'));
        if ($category === '') {
            $errors['category'] = 'Category is required.';
        }

        $summary = trim((string) ($input['summary'] ?? ''));
        if ($summary === '') {
            $errors['summary'] = 'Summary is required.';
        }

        $content = (string) ($input['content'] ?? '');
        if (trim($content) === '') {
            $errors['content'] = 'Article content is required.';
        }

        $status = trim((string) ($input['status'] ?? 'draft'));
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $errors['status'] = 'Status is invalid.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $input */
    public function create(array $input, int $userId): int
    {
        $input = $this->normalizeInput($input);
        $errors = $this->validate($input);
        if ($errors !== []) {
            throw new HttpException(422, 'Please review the article details and try again.');
        }

        return $this->model->create([
            'title' => $input['title'],
            'slug' => $input['slug'],
            'category' => $input['category'],
            'summary' => $input['summary'],
            'content' => $input['content'],
            'status' => $input['status'],
            'is_featured' => (int) ($input['is_featured'] ?? 0),
            'created_by' => $userId,
        ]);
    }

    /** @param array<string, mixed> $input */
    public function update(int $id, array $input, int $userId): void
    {
        $input = $this->normalizeInput($input);
        $errors = $this->validate($input);
        if ($errors !== []) {
            throw new HttpException(422, 'Please review the article details and try again.');
        }

        $this->model->update($id, [
            'title' => $input['title'],
            'slug' => $input['slug'],
            'category' => $input['category'],
            'summary' => $input['summary'],
            'content' => $input['content'],
            'status' => $input['status'],
            'is_featured' => (int) ($input['is_featured'] ?? 0),
            'updated_by' => $userId,
        ]);
    }

    public function incrementViews(int $id): void
    {
        $this->model->incrementViews($id);
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function normalizeInput(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $slug = trim((string) ($input['slug'] ?? ''));
        $category = trim((string) ($input['category'] ?? 'general'));
        $summary = trim((string) ($input['summary'] ?? ''));
        $content = trim((string) ($input['content'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'draft'));

        if ($slug === '') {
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^a-zA-Z0-9\s-]/', '', $title)));
            $slug = preg_replace('/-+/', '-', (string) $slug) ?? 'article';
        }

        return [
            'title' => $title,
            'slug' => strtolower($slug),
            'category' => $category !== '' ? $category : 'general',
            'summary' => $summary,
            'content' => $content,
            'status' => in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft',
            'is_featured' => ((int) ($input['is_featured'] ?? 0)) === 1 ? 1 : 0,
        ];
    }
}
