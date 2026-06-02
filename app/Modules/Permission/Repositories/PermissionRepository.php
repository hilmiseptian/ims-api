<?php

declare(strict_types=1);

namespace App\Modules\Permission\Repositories;

use PDO;

class PermissionRepository
{
    public function __construct(private readonly PDO $db) {}

    public function getLevelPermissions(int $levelId): array
    {
        $stmt = $this->db->prepare(
            'SELECT lp.id, lp.level_id, lp.permission_key, p.name AS page_name
             FROM level_permissions lp
             LEFT JOIN pages p ON lp.page_id = p.id
             WHERE lp.level_id = :level_id'
        );
        $stmt->execute([':level_id' => $levelId]);
        return $stmt->fetchAll();
    }

    public function getLevelPermissionKeys(int $levelId): array
    {
        $stmt = $this->db->prepare(
            'SELECT permission_key FROM level_permissions WHERE level_id = :level_id'
        );
        $stmt->execute([':level_id' => $levelId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function setLevelPermissions(int $levelId, array $permissionKeys): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('DELETE FROM level_permissions WHERE level_id = :level_id');
            $stmt->execute([':level_id' => $levelId]);

            if (!empty($permissionKeys)) {
                $insert = $this->db->prepare(
                    'INSERT INTO level_permissions (level_id, permission_key) VALUES (:level_id, :key)'
                );
                foreach (array_unique($permissionKeys) as $key) {
                    $insert->execute([':level_id' => $levelId, ':key' => $key]);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getUserAdditions(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT permission_key FROM user_permissions WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function setUserAdditions(int $userId, array $permissionKeys): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('DELETE FROM user_permissions WHERE user_id = :user_id');
            $stmt->execute([':user_id' => $userId]);

            if (!empty($permissionKeys)) {
                $insert = $this->db->prepare(
                    'INSERT INTO user_permissions (user_id, permission_key) VALUES (:user_id, :key)'
                );
                foreach (array_unique($permissionKeys) as $key) {
                    $insert->execute([':user_id' => $userId, ':key' => $key]);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getUserExclusions(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT permission_key FROM user_permission_exclusions WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function setUserExclusions(int $userId, array $permissionKeys): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('DELETE FROM user_permission_exclusions WHERE user_id = :user_id');
            $stmt->execute([':user_id' => $userId]);

            if (!empty($permissionKeys)) {
                $insert = $this->db->prepare(
                    'INSERT INTO user_permission_exclusions (user_id, permission_key) VALUES (:user_id, :key)'
                );
                foreach (array_unique($permissionKeys) as $key) {
                    $insert->execute([':user_id' => $userId, ':key' => $key]);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getEffectivePermissions(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT lp.permission_key
             FROM level_permissions lp
             INNER JOIN users u ON u.level_id = lp.level_id
             WHERE u.id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        $levelPerms = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->db->prepare(
            'SELECT permission_key FROM user_permissions WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        $additions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->db->prepare(
            'SELECT permission_key FROM user_permission_exclusions WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        $exclusions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $effective = array_unique(array_merge($levelPerms, $additions));
        $effective = array_values(array_diff($effective, $exclusions));

        sort($effective);
        return $effective;
    }

    public function getUserLevelId(int $userId): ?int
    {
        $stmt = $this->db->prepare('SELECT level_id FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int) $result : null;
    }

    public function getAllDefinedPermissions(): array
    {
        return array_column($this->getAllDefinedPermissionsFormatted(), 'name');
    }

    public function getAllDefinedPermissionsFormatted(): array
    {
        $definitions = [
            ['module' => 'dashboard',   'action' => 'view',   'description' => 'View dashboard'],
            ['module' => 'users',       'action' => 'view',   'description' => 'View user list'],
            ['module' => 'users',       'action' => 'create', 'description' => 'Create users'],
            ['module' => 'users',       'action' => 'update', 'description' => 'Edit users'],
            ['module' => 'users',       'action' => 'delete', 'description' => 'Delete users'],
            ['module' => 'levels',      'action' => 'view',   'description' => 'View levels'],
            ['module' => 'levels',      'action' => 'create', 'description' => 'Create levels'],
            ['module' => 'levels',      'action' => 'update', 'description' => 'Edit levels'],
            ['module' => 'levels',      'action' => 'delete', 'description' => 'Delete levels'],
            ['module' => 'pages',       'action' => 'view',   'description' => 'View pages'],
            ['module' => 'pages',       'action' => 'create', 'description' => 'Create pages'],
            ['module' => 'pages',       'action' => 'update', 'description' => 'Edit pages'],
            ['module' => 'pages',       'action' => 'delete', 'description' => 'Delete pages'],
            ['module' => 'permissions', 'action' => 'view',   'description' => 'View permissions'],
            ['module' => 'permissions', 'action' => 'update', 'description' => 'Manage permissions'],
        ];

        $result = [];
        foreach ($definitions as $i => $def) {
            $result[] = [
                'id'          => $i + 1,
                'name'        => "{$def['module']}.{$def['action']}",
                'module'      => $def['module'],
                'action'      => $def['action'],
                'description' => $def['description'],
            ];
        }
        return $result;
    }
}
