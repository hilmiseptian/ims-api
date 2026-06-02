<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use PDO;

class AuthRepository
{
    public function __construct(private readonly PDO $db) {}

    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, l.name AS level_name 
             FROM users u
             LEFT JOIN levels l ON u.level_id = l.id
             WHERE (u.username = :id OR u.email = :id)
             LIMIT 1'
        );
        $stmt->execute([':id' => $identifier]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findActiveUserById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, l.name AS level_name
             FROM users u
             LEFT JOIN levels l ON u.level_id = l.id
             WHERE u.id = :id AND u.is_active = true
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
