<?php
// app/Modules/Page/Repositories/PageRepository.php

declare(strict_types=1);

namespace App\Modules\Page\Repositories;

use PDO;

class PageRepository
{
    public function __construct(private readonly PDO $db) {}

    public function findAll(bool $activeOnly = false, string $search = ''): array
    {
        $conditions = [];
        $params     = [];

        if ($activeOnly) {
            $conditions[] = 'is_active = TRUE';
        }
        if ($search !== '') {
            $conditions[] = '(name ILIKE :search OR description ILIKE :search)';
            $params[':search'] = "%{$search}%";
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM pages {$where}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM pages {$where} ORDER BY sort_order ASC, name ASC"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'meta' => [
                'total'     => $total,
                'page'      => 1,
                'per_page'  => $total ?: 1,
                'last_page' => 1,
            ],
        ];
    }

    /**
     * Returns pages visible in sidebar for a given set of effective permissions.
     * Supports parent-child hierarchy.
     */
    public function findMenuPages(array $effectivePermissions): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, route_path, icon, sort_order, permission_key, parent_id
             FROM pages
             WHERE is_active = TRUE
             ORDER BY parent_id ASC NULLS FIRST, sort_order ASC"
        );
        $stmt->execute();
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter by permission
        return array_values(array_filter($all, function (array $page) use ($effectivePermissions) {
            return empty($page['permission_key'])
                || in_array($page['permission_key'], $effectivePermissions, true);
        }));
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function existsByRoutePath(string $routePath, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT 1 FROM pages WHERE route_path = :route_path';
        $params = [':route_path' => $routePath];
        if ($excludeId) {
            $sql           .= ' AND id != :id';
            $params[':id']  = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function create(array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pages (name, route_path, description, sort_order, icon, permission_key, parent_id, is_active)
             VALUES (:name, :route_path, :description, :sort_order, :icon, :permission_key, :parent_id, :is_active)
             RETURNING id'
        );
        $stmt->execute([
            ':name'           => $data['name'],
            ':route_path'     => $data['route_path'],
            ':description'    => $data['description'] ?? null,
            ':sort_order'     => $data['sort_order'] ?? 0,
            ':icon'           => $data['icon'] ?? 'article',
            ':permission_key' => $data['permission_key'] ?? null,
            ':parent_id'      => $data['parent_id'] ?? null,
            ':is_active'      => ($data['is_active'] ?? true) ? 'true' : 'false',
        ]);
        return $this->findById((int) $stmt->fetchColumn());
    }

    public function update(int $id, array $data): ?array
    {
        $fields = [];
        $params = [':id' => $id];

        $updatable = [
            'name',
            'route_path',
            'description',
            'sort_order',
            'icon',
            'permission_key',
            'parent_id',
            'is_active'
        ];

        foreach ($updatable as $field) {
            if (array_key_exists($field, $data)) {
                $fields[]            = "{$field} = :{$field}";
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
        $stmt = $this->db->prepare('DELETE FROM pages WHERE id = :id AND is_system = FALSE');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function isSystem(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT is_system FROM pages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}