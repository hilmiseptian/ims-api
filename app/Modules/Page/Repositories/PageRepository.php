<?php

declare(strict_types=1);

namespace App\Modules\Page\Repositories;

use PDO;

class PageRepository
{
    public function __construct(private readonly PDO $db) {}

    public function findAll(bool $activeOnly = false): array
    {
        $where = $activeOnly
            ? 'WHERE is_active = true'
            : '';

        $stmt = $this->db->query(
            "SELECT *
         FROM pages
         {$where}
         ORDER BY sort_order ASC, name ASC"
        );

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'meta' => [
                'total' => count($data),
                'page' => 1,
                'per_page' => count($data),
                'last_page' => 1,
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function existsByRoutePath(string $routePath, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM pages WHERE route_path = :route_path';
        $params = [':route_path' => $routePath];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function create(array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pages (name, route_path, description, sort_order, is_active)
             VALUES (:name, :route_path, :description, :sort_order, :is_active)
             RETURNING id'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':route_path' => $data['route_path'],
            ':description' => $data['description'] ?? null,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':is_active' => $data['is_active'] ? 'true' : 'false',
        ]);
        return $this->findById((int)$stmt->fetchColumn());
    }

    public function update(int $id, array $data): ?array
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'route_path', 'description', 'sort_order', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) return $this->findById($id);

        $fields[] = 'updated_at = NOW()';
        $stmt = $this->db->prepare(
            'UPDATE pages SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $stmt->execute($params);
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM pages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
