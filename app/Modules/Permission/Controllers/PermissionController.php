<?php

declare(strict_types=1);

namespace App\Modules\Permission\Controllers;

use App\Modules\Permission\Services\PermissionService;
use App\Shared\Response\JsonResponse;
use App\Shared\Exceptions\{ValidationException, NotFoundException};
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PermissionController
{
    public function __construct(private readonly PermissionService $permissionService) {}

    public function allPermissions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $permissions = $this->permissionService->getAllPermissions();
        return JsonResponse::success($response, $permissions, 'Permissions retrieved');
    }

    public function myPermissions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user        = $request->getAttribute('auth_user');
        $permissions = $this->permissionService->getEffectivePermissions((int) $user['id']);
        return JsonResponse::success($response, ['permissions' => $permissions], 'Permissions retrieved');
    }

    public function getLevelPermissions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $data = $this->permissionService->getLevelPermissions((int) $args['levelId']);
            return JsonResponse::success($response, $data, 'Level permissions retrieved');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        }
    }

    public function updateLevelPermissions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $body = $request->getParsedBody() ?? [];
            $data = $this->permissionService->updateLevelPermissions((int) $args['levelId'], $body['permissions'] ?? []);
            return JsonResponse::success($response, $data, 'Level permissions updated');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        } catch (ValidationException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422, $e->getErrors());
        }
    }

    public function getUserPermissions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $data = $this->permissionService->getUserPermissions((int) $args['userId']);
            return JsonResponse::success($response, $data, 'User permissions retrieved');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        }
    }

    public function updateUserAdditions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $body = $request->getParsedBody() ?? [];
            $data = $this->permissionService->updateUserAdditions((int) $args['userId'], $body['permissions'] ?? []);
            return JsonResponse::success($response, $data, 'User additional permissions updated');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        } catch (ValidationException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422, $e->getErrors());
        }
    }

    public function updateUserExclusions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $body = $request->getParsedBody() ?? [];
            $data = $this->permissionService->updateUserExclusions((int) $args['userId'], $body['permissions'] ?? []);
            return JsonResponse::success($response, $data, 'User permission exclusions updated');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        } catch (ValidationException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422, $e->getErrors());
        }
    }

    public function matrix(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->permissionService->getMatrix();
        return JsonResponse::success($response, $data, 'Permission matrix retrieved');
    }
}
