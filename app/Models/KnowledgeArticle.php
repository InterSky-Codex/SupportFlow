<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

final class KnowledgeArticle
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'general'));
        $summary = trim((string) ($data['summary'] ?? ''));
        $content = (string) ($data['content'] ?? '');
        $status = trim((string) ($data['status'] ?? 'draft'));
        $createdBy = filter_var($data['created_by'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($title === '' || $slug === '' || $summary === '' || $content === '' || $createdBy === false || $createdBy === null) {
            throw new InvalidArgumentException('Title, slug, summary, content, and author are required.');
        }

        $status = in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft';
        $publishedAt = $status === 'published' ? 'CURRENT_TIMESTAMP' : 'NULL';

        $statement = $this->connection()->prepare(
            'INSERT INTO knowledge_articles (
                title, slug, category, summary, content, status, is_featured,
                created_by, updated_by, published_at, view_count
            ) VALUES (
                :title, :slug, :category, :summary, :content, :status, :is_featured,
                :created_by, :updated_by, ' . $publishedAt . ', 0
            )'
        );

        $statement->execute([
            'title' => $title,
            'slug' => $slug,
            'category' => $category !== '' ? $category : 'general',
            'summary' => $summary,
            'content' => $content,
            'status' => $status,
            'is_featured' => (int) (($data['is_featured'] ?? 0) == 1),
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $title = trim((string) ($data['title'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'general'));
        $summary = trim((string) ($data['summary'] ?? ''));
        $content = (string) ($data['content'] ?? '');
        $status = trim((string) ($data['status'] ?? 'draft'));
        $updatedBy = filter_var($data['updated_by'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($title === '' || $slug === '' || $summary === '' || $content === '' || $updatedBy === false || $updatedBy === null) {
            throw new InvalidArgumentException('Title, slug, summary, content, and editor are required.');
        }

        $status = in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft';
        $publishedValue = $status === 'published' ? 'CURRENT_TIMESTAMP' : 'NULL';

        $statement = $this->connection()->prepare(
            'UPDATE knowledge_articles
             SET title = :title,
                 slug = :slug,
                 category = :category,
                 summary = :summary,
                 content = :content,
                 status = :status,
                 is_featured = :is_featured,
                 updated_by = :updated_by,
                 published_at = CASE WHEN :status = :published_status THEN COALESCE(published_at, CURRENT_TIMESTAMP) ELSE NULL END
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'category' => $category !== '' ? $category : 'general',
            'summary' => $summary,
            'content' => $content,
            'status' => $status,
            'published_status' => 'published',
            'is_featured' => (int) (($data['is_featured'] ?? 0) == 1),
            'updated_by' => $updatedBy,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT a.*, u.name AS author_name, editor.name AS updated_by_name
             FROM knowledge_articles a
             LEFT JOIN users u ON u.id = a.created_by
             LEFT JOIN users editor ON editor.id = a.updated_by
             WHERE a.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $article = $statement->fetch();

        return $article === false ? null : $article;
    }

    /** @return array<string, mixed>|null */
    public function findPublishedBySlug(string $slug): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT a.*, u.name AS author_name
             FROM knowledge_articles a
             LEFT JOIN users u ON u.id = a.created_by
             WHERE a.slug = :slug AND a.status = :status
             LIMIT 1'
        );
        $statement->execute([
            'slug' => $slug,
            'status' => 'published',
        ]);
        $article = $statement->fetch();

        return $article === false ? null : $article;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public function paginate(array $filters, int $page, int $perPage, bool $includeDrafts = false): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));

        $conditions = [];
        $parameters = [];

        if (!$includeDrafts) {
            $conditions[] = 'a.status = :status';
            $parameters['status'] = 'published';
        } elseif (($filters['status'] ?? '') !== '') {
            $conditions[] = 'a.status = :status';
            $parameters['status'] = trim((string) $filters['status']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $conditions[] = '(a.title LIKE :search_title OR a.summary LIKE :search_summary OR a.content LIKE :search_content OR a.category LIKE :search_category)';
            $searchValue = '%' . $search . '%';
            $parameters['search_title'] = $searchValue;
            $parameters['search_summary'] = $searchValue;
            $parameters['search_content'] = $searchValue;
            $parameters['search_category'] = $searchValue;
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $conditions[] = 'a.category = :category';
            $parameters['category'] = $category;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $countStatement = $this->connection()->prepare('SELECT COUNT(*) FROM knowledge_articles a' . $where);
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->connection()->prepare(
            'SELECT a.*, u.name AS author_name
             FROM knowledge_articles a
             LEFT JOIN users u ON u.id = a.created_by' . $where . '
             ORDER BY a.is_featured DESC, a.published_at DESC, a.created_at DESC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /** @return list<string> */
    public function categories(bool $includeAllStatuses = false): array
    {
        $query = 'SELECT DISTINCT category FROM knowledge_articles';
        if (!$includeAllStatuses) {
            $query .= ' WHERE status = :status';
        }
        $query .= ' ORDER BY category ASC';

        $statement = $this->connection()->prepare($query);
        if (!$includeAllStatuses) {
            $statement->execute(['status' => 'published']);
        } else {
            $statement->execute();
        }

        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_filter(array_map('strval', $rows), static fn (string $value): bool => $value !== ''));
    }

    public function incrementViews(int $id): void
    {
        $statement = $this->connection()->prepare('UPDATE knowledge_articles SET view_count = view_count + 1 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}
