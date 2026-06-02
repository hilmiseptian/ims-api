<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Response\JsonResponse;
use App\Modules\Permission\Services\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use DI\Container;

class PermissionMiddleware implements MiddlewareInterface
{
    private static ?Container $container = null;

    public function __construct(private readonly string $requiredPermission) {}

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $user = $request->getAttribute('auth_user');

        if (!$user) {
            $response = (new ResponseFactory())->createResponse();
            return JsonResponse::unauthorized($response);
        }

        /** @var PermissionService $permissionService */
        $permissionService = self::$container->get(PermissionService::class);
        $permissions = $permissionService->getEffectivePermissions((int)$user['id']);

        if (!in_array($this->requiredPermission, $permissions, true)) {
            $response = (new ResponseFactory())->createResponse();
            return JsonResponse::forbidden($response);
        }

        // Pass permissions down for UI use
        $request = $request->withAttribute('user_permissions', $permissions);

        return $handler->handle($request);
    }
}
