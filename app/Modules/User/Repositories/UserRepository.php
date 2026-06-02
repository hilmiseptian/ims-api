<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use PDO;

class UserRepository
{
    public function __construct(private readonly PDO $db) {}

    public function findAll(int $page = 1, int $limit = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = '';

        if ($search) {
            $where = "WHERE (u.full_name ILIKE :search OR u.username ILIKE :search OR u.email ILIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $stmt = $this->db->prepare(
            "SELECT u.id, u.full_name, u.username, u.email, u.level_id, u.is_active, u.created_at, u.updated_at,
                    l.name AS level_name
             FROM users u
             LEFT JOIN levels l ON u.level_id = l.id
             {$where}
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users u {$where}"
        );
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'data' => $stmt->fetchAll(),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int)ceil($total / $limit),
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, l.name AS level_name
             FROM users u
             LEFT JOIN levels l ON u.level_id = l.id
             WHERE u.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if ($user) {
            unset($user['password_hash']);
        }
        return $user ?: null;
    }

    public function existsByUsername(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE username = :username';
        $params = [':username' => $username];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email';
        $params = [':email' => $email];
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
            'INSERT INTO users (level_id, full_name, username, email, password_hash, is_active)
             VALUES (:level_id, :full_name, :username, :email, :password_hash, :is_active)
             RETURNING id'
        );
        $stmt->execute([
            ':level_id' => $data['level_id'],
            ':full_name' => $data['full_name'],
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':is_active' => $data['is_active'] ? 'true' : 'false',
        ]);
        $id = (int)$stmt->fetchColumn();
        return $this->findById($id);
    }

    public function update(int $id, array $data): ?array
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['level_id', 'full_name', 'username', 'email', 'is_active'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (isset($data['password_hash'])) {
            $fields[] = 'password_hash = :password_hash';
            $params[':password_hash'] = $data['password_hash'];
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
