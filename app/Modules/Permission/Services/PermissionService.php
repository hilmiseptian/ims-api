<?php

declare(strict_types=1);

namespace App\Modules\Permission\Services;

use App\Modules\Permission\Repositories\PermissionRepository;
use App\Modules\Level\Repositories\LevelRepository;
use App\Modules\User\Repositories\UserRepository;
use App\Shared\Exceptions\{NotFoundException, ValidationException};

class PermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly LevelRepository $levelRepository,
        private readonly UserRepository $userRepository
    ) {}

    public function getAllPermissions(): array
    {
        return $this->permissionRepository->getAllDefinedPermissionsFormatted();
    }

    public function getEffectivePermissions(int $userId): array
    {
        return $this->permissionRepository->getEffectivePermissions($userId);
    }

    public function getLevelPermissions(int $levelId): array
    {
        $level = $this->levelRepository->findById($levelId);
        if (!$level) {
            throw new NotFoundException('Level not found');
        }

        return [
            'level'                => $level,
            'all_permissions'      => $this->permissionRepository->getAllDefinedPermissions(),
            'assigned_permissions' => $this->permissionRepository->getLevelPermissionKeys($levelId),
        ];
    }

    public function updateLevelPermissions(int $levelId, array $permissionKeys): array
    {
        $level = $this->levelRepository->findById($levelId);
        if (!$level) {
            throw new NotFoundException('Level not found');
        }

        $this->validatePermissionKeys($permissionKeys);
        $this->permissionRepository->setLevelPermissions($levelId, $permissionKeys);

        return $this->getLevelPermissions($levelId);
    }

    public function getUserPermissions(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        $levelId = $this->permissionRepository->getUserLevelId($userId);

        return [
            'user'                  => $user,
            'all_permissions'       => $this->permissionRepository->getAllDefinedPermissions(),
            'level_permissions'     => $levelId
                ? $this->permissionRepository->getLevelPermissionKeys($levelId)
                : [],
            'user_additions'        => $this->permissionRepository->getUserAdditions($userId),
            'user_exclusions'       => $this->permissionRepository->getUserExclusions($userId),
            'effective_permissions' => $this->permissionRepository->getEffectivePermissions($userId),
        ];
    }

    public function updateUserAdditions(int $userId, array $permissionKeys): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        $this->validatePermissionKeys($permissionKeys);
        $this->permissionRepository->setUserAdditions($userId, $permissionKeys);

        return $this->getUserPermissions($userId);
    }

    public function updateUserExclusions(int $userId, array $permissionKeys): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        $this->validatePermissionKeys($permissionKeys);
        $this->permissionRepository->setUserExclusions($userId, $permissionKeys);

        return $this->getUserPermissions($userId);
    }

    public function getMatrix(): array
    {
        $allPerms     = $this->permissionRepository->getAllDefinedPermissions();
        $levelsResult = $this->levelRepository->findAll();
        $levels       = $levelsResult['data'];

        $matrix = [];
        foreach ($levels as $level) {
            $assigned = $this->permissionRepository->getLevelPermissionKeys((int) $level['id']);
            $row = [
                'level'       => $level,
                'permissions' => array_fill_keys($allPerms, false),
                'assigned'    => $assigned,
            ];
            foreach ($assigned as $key) {
                $row['permissions'][$key] = true;
            }
            $matrix[] = $row;
        }

        return [
            'all_permissions' => $allPerms,
            'matrix'          => $matrix,
        ];
    }

    private function validatePermissionKeys(array $keys): void
    {
        if (empty($keys)) {
            return;
        }

        $valid   = $this->permissionRepository->getAllDefinedPermissions();
        $invalid = array_diff($keys, $valid);

        if (!empty($invalid)) {
            throw new ValidationException([
                'permissions' => 'Invalid permission keys: ' . implode(', ', $invalid),
            ]);
        }
    }
}
