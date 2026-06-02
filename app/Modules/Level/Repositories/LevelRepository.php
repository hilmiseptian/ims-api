<?php

declare(strict_types=1);

namespace App\Modules\Level\Repositories;

use PDO;

class LevelRepository
{
    public function __construct(private readonly PDO $db) {}

    public function findAll(bool $activeOnly = false, string $search = ''): array
    {
        $conditions = ['deleted_at IS NULL'];
        $params     = [];

        if ($activeOnly) {
            $conditions[] = 'is_active = true';
        }

        if ($search !== '') {
            $conditions[] = '(name ILIKE :search OR description ILIKE :search)';
            $params[':search'] = "%{$search}%";
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM levels {$where}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT * FROM levels {$where} ORDER BY name ASC");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'meta' => [
                'total' => $total,
                'page'  => 1,
                'limit' => $total ?: 1,
                'pages' => 1,
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM levels WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT 1 FROM levels WHERE name = :name AND deleted_at IS NULL';
        $params = [':name' => $name];
        if ($excludeId) {
            $sql           .= ' AND id != :id';
            $params[':id']  = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function isUsedByUsers(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE level_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO levels (name, description, is_active)
             VALUES (:name, :description, :is_active)
             RETURNING id'
        );
        $stmt->execute([
            ':name'        => $data['name'],
            ':description' => $data['description'] ?? null,
            ':is_active'   => $data['is_active'] ? 'true' : 'false',
        ]);
        return $this->findById((int) $stmt->fetchColumn());
    }

    public function update(int $id, array $data): ?array
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'description', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[]            = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $fields[] = 'updated_at = NOW()';
        $stmt = $this->db->prepare(
            'UPDATE levels SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $stmt->execute($params);
        return $this->findById($id);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE levels SET deleted_at = NOW(), updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
