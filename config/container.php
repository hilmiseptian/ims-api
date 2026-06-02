<?php

declare(strict_types=1);

use App\Shared\Database\DatabaseConnection;
use App\Shared\Security\JwtService;
use App\Modules\Auth\Repositories\AuthRepository;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Services\UserService;
use App\Modules\User\Controllers\UserController;
use App\Modules\Level\Repositories\LevelRepository;
use App\Modules\Level\Services\LevelService;
use App\Modules\Level\Controllers\LevelController;
use App\Modules\Page\Repositories\PageRepository;
use App\Modules\Page\Services\PageService;
use App\Modules\Page\Controllers\PageController;
use App\Modules\Permission\Repositories\PermissionRepository;
use App\Modules\Permission\Services\PermissionService;
use App\Modules\Permission\Controllers\PermissionController;

return [
    // Database
    PDO::class => function () {
        return DatabaseConnection::getInstance();
    },

    // Shared Services
    JwtService::class => function () {
        return new JwtService(
            $_ENV['JWT_SECRET'] ?? 'default-secret',
            (int)($_ENV['JWT_EXPIRY'] ?? 86400)
        );
    },

    // Dashboard
    DashboardController::class => DI\autowire(),

    // Auth
    AuthRepository::class => DI\autowire(),
    AuthService::class => DI\autowire(),
    AuthController::class => DI\autowire(),

    // User
    UserRepository::class => DI\autowire(),
    UserService::class => DI\autowire(),
    UserController::class => DI\autowire(),

    // Level
    LevelRepository::class => DI\autowire(),
    LevelService::class => DI\autowire(),
    LevelController::class => DI\autowire(),

    // Page
    PageRepository::class => DI\autowire(),
    PageService::class => DI\autowire(),
    PageController::class => DI\autowire(),

    // Permission
    PermissionRepository::class => DI\autowire(),
    PermissionService::class => DI\autowire(),
    PermissionController::class => DI\autowire(),
];
