<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\User\Controllers\UserController;
use App\Modules\Level\Controllers\LevelController;
use App\Modules\Page\Controllers\PageController;
use App\Modules\Permission\Controllers\PermissionController;
use App\Shared\Middleware\AuthMiddleware;
use App\Shared\Middleware\PermissionMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/api', function (RouteCollectorProxy $api) {

        // Auth routes (public)
        $api->group('/auth', function (RouteCollectorProxy $auth) {
            $auth->post('/login', [AuthController::class, 'login']);
        });

        // Protected routes
        $api->group('', function (RouteCollectorProxy $protected) {

            // Dashboard
            $protected->get('/dashboard/stats', [DashboardController::class, 'stats'])
                ->add(new PermissionMiddleware('dashboard.view'));

            // Auth - current user
            $protected->get('/auth/me', [AuthController::class, 'me']);
            $protected->post('/auth/refresh', [AuthController::class, 'refresh']);
            $protected->post('/auth/logout', [AuthController::class, 'logout']);

            // User CRUD
            $protected->group('/users', function (RouteCollectorProxy $users) {
                $users->get('', [UserController::class, 'index'])
                    ->add(new PermissionMiddleware('users.view'));
                $users->get('/{id}', [UserController::class, 'show'])
                    ->add(new PermissionMiddleware('users.view'));
                $users->post('', [UserController::class, 'store'])
                    ->add(new PermissionMiddleware('users.create'));
                $users->put('/{id}', [UserController::class, 'update'])
                    ->add(new PermissionMiddleware('users.update'));
                $users->delete('/{id}', [UserController::class, 'destroy'])
                    ->add(new PermissionMiddleware('users.delete'));
            });

            // Level CRUD
            $protected->group('/levels', function (RouteCollectorProxy $levels) {
                $levels->get('', [LevelController::class, 'index'])
                    ->add(new PermissionMiddleware('levels.view'));
                $levels->get('/{id}', [LevelController::class, 'show'])
                    ->add(new PermissionMiddleware('levels.view'));
                $levels->post('', [LevelController::class, 'store'])
                    ->add(new PermissionMiddleware('levels.create'));
                $levels->put('/{id}', [LevelController::class, 'update'])
                    ->add(new PermissionMiddleware('levels.update'));
                $levels->delete('/{id}', [LevelController::class, 'destroy'])
                    ->add(new PermissionMiddleware('levels.delete'));
            });

            // Page CRUD
            $protected->group('/pages', function (RouteCollectorProxy $pages) {
                $pages->get('', [PageController::class, 'index'])
                    ->add(new PermissionMiddleware('pages.view'));
                $pages->get('/{id}', [PageController::class, 'show'])
                    ->add(new PermissionMiddleware('pages.view'));
                $pages->post('', [PageController::class, 'store'])
                    ->add(new PermissionMiddleware('pages.create'));
                $pages->put('/{id}', [PageController::class, 'update'])
                    ->add(new PermissionMiddleware('pages.update'));
                $pages->delete('/{id}', [PageController::class, 'destroy'])
                    ->add(new PermissionMiddleware('pages.delete'));
            });

            // Permissions
            $protected->group('/permissions', function (RouteCollectorProxy $permission) {
                $permission->get('', [PermissionController::class, 'allPermissions']);
                $permission->get('/permissions/my', [PermissionController::class, 'myPermissions']);
                $permission->get('/permissions/matrix', [PermissionController::class, 'matrix'])
                    ->add(new PermissionMiddleware('permissions.view'));
                $permission->get('/permissions/levels/{levelId}', [PermissionController::class, 'getLevelPermissions'])
                    ->add(new PermissionMiddleware('permissions.view'));
                $permission->put('/permissions/levels/{levelId}', [PermissionController::class, 'updateLevelPermissions'])
                    ->add(new PermissionMiddleware('permissions.update'));
                $permission->get('/permissions/users/{userId}', [PermissionController::class, 'getUserPermissions'])
                    ->add(new PermissionMiddleware('permissions.view'));
                $permission->put('/permissions/users/{userId}/additions', [PermissionController::class, 'updateUserAdditions'])
                    ->add(new PermissionMiddleware('permissions.update'));
                $permission->put('/permissions/users/{userId}/exclusions', [PermissionController::class, 'updateUserExclusions'])
                    ->add(new PermissionMiddleware('permissions.update'));
            });
        })->add(AuthMiddleware::class);
    });
};
