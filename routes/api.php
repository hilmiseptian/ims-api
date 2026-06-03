<?php
// routes/api.php  — complete file

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

        // ── Public ──────────────────────────────────────────────────────────
        $api->post('/auth/login', [AuthController::class, 'login']);

        // ── Protected ───────────────────────────────────────────────────────
        $api->group('', function (RouteCollectorProxy $protected) {

            // Auth
            $protected->get('/auth/me',       [AuthController::class, 'me']);
            $protected->post('/auth/refresh', [AuthController::class, 'refresh']);
            $protected->post('/auth/logout',  [AuthController::class, 'logout']);

            // Dashboard
            $protected->get('/dashboard/stats', [DashboardController::class, 'stats'])
                ->add(new PermissionMiddleware('dashboard.view'));

            // Users
            $protected->group('/users', function (RouteCollectorProxy $g) {
                $g->get('',       [UserController::class, 'index'])->add(new PermissionMiddleware('users.view'));
                $g->get('/{id}',  [UserController::class, 'show'])->add(new PermissionMiddleware('users.view'));
                $g->post('',      [UserController::class, 'store'])->add(new PermissionMiddleware('users.create'));
                $g->put('/{id}',  [UserController::class, 'update'])->add(new PermissionMiddleware('users.update'));
                $g->delete('/{id}', [UserController::class, 'destroy'])->add(new PermissionMiddleware('users.delete'));
            });

            // Levels
            $protected->group('/levels', function (RouteCollectorProxy $g) {
                $g->get('/active', [LevelController::class, 'active'])               // ← NEW: active-only list
                    ->add(new PermissionMiddleware('levels.view'));
                $g->get('',       [LevelController::class, 'index'])->add(new PermissionMiddleware('levels.view'));
                $g->get('/{id}',  [LevelController::class, 'show'])->add(new PermissionMiddleware('levels.view'));
                $g->post('',      [LevelController::class, 'store'])->add(new PermissionMiddleware('levels.create'));
                $g->put('/{id}',  [LevelController::class, 'update'])->add(new PermissionMiddleware('levels.update'));
                $g->delete('/{id}', [LevelController::class, 'destroy'])->add(new PermissionMiddleware('levels.delete'));
            });

            // Pages
            $protected->group('/pages', function (RouteCollectorProxy $g) {
                $g->get('/menu', [PageController::class, 'menu']);                    // ← NEW: sidebar menu
                $g->get('',      [PageController::class, 'index'])->add(new PermissionMiddleware('pages.view'));
                $g->get('/{id}', [PageController::class, 'show'])->add(new PermissionMiddleware('pages.view'));
                $g->post('',     [PageController::class, 'store'])->add(new PermissionMiddleware('pages.create'));
                $g->put('/{id}', [PageController::class, 'update'])->add(new PermissionMiddleware('pages.update'));
                $g->delete('/{id}', [PageController::class, 'destroy'])->add(new PermissionMiddleware('pages.delete'));
            });

            // Permissions — FIXED: removed extra /permissions/ prefix
            $protected->group('/permissions', function (RouteCollectorProxy $g) {
                $g->get('',                           [PermissionController::class, 'allPermissions']);
                $g->get('/my',                        [PermissionController::class, 'myPermissions']);
                $g->get('/matrix',                    [PermissionController::class, 'matrix'])
                    ->add(new PermissionMiddleware('permissions.view'));
                $g->get('/levels/{levelId}',          [PermissionController::class, 'getLevelPermissions'])
                    ->add(new PermissionMiddleware('permissions.view'));
                $g->put('/levels/{levelId}',          [PermissionController::class, 'updateLevelPermissions'])
                    ->add(new PermissionMiddleware('permissions.update'));
                $g->get('/users/{userId}',            [PermissionController::class, 'getUserPermissions'])
                    ->add(new PermissionMiddleware('permissions.view'));
                $g->put('/users/{userId}/additions',  [PermissionController::class, 'updateUserAdditions'])
                    ->add(new PermissionMiddleware('permissions.update'));
                $g->put('/users/{userId}/exclusions', [PermissionController::class, 'updateUserExclusions'])
                    ->add(new PermissionMiddleware('permissions.update'));
            });
        })->add(AuthMiddleware::class);
    });
};