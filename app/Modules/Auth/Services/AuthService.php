<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Repositories\AuthRepository;
use App\Modules\Permission\Services\PermissionService;
use App\Shared\Security\JwtService;
use App\Shared\Exceptions\UnauthorizedException;
use App\Shared\Exceptions\ValidationException;

class AuthService
{
    public function __construct(
        private readonly AuthRepository $authRepository,
        private readonly PermissionService $permissionService,
        private readonly JwtService $jwtService
    ) {}

    public function login(string $login, string $password): array
    {
        if (empty($login) || empty($password)) {
            throw new ValidationException([
                'login' => 'Username/email and password are required',
            ]);
        }

        $user = $this->authRepository->findByUsernameOrEmail($login);

        if (!$user) {
            throw new UnauthorizedException('Invalid credentials');
        }

        if (!$user['is_active']) {
            throw new UnauthorizedException('Account is inactive');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new UnauthorizedException('Invalid credentials');
        }

        $permissions = $this->permissionService->getEffectivePermissions((int)$user['id']);

        $token = $this->jwtService->generate([
            'sub' => $user['id'],
            'username' => $user['username'],
            'level_id' => $user['level_id'],
        ]);

        return [
            'token' => $token,
            'user' => $this->sanitizeUser($user),
            'permissions' => $permissions,
        ];
    }

    public function getCurrentUser(array $user): array
    {
        $permissions = $this->permissionService->getEffectivePermissions((int)$user['id']);
        return [
            'user' => $this->sanitizeUser($user),
            'permissions' => $permissions,
        ];
    }

    private function sanitizeUser(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }
}
