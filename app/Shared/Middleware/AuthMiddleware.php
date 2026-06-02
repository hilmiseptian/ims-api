<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Security\JwtService;
use App\Shared\Response\JsonResponse;
use App\Modules\Auth\Repositories\AuthRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use RuntimeException;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly AuthRepository $authRepository
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            $response = (new ResponseFactory())->createResponse();
            return JsonResponse::unauthorized($response);
        }

        try {
            $token = $this->jwtService->extractFromHeader($authHeader);
            $payload = $this->jwtService->decode($token);

            $user = $this->authRepository->findActiveUserById((int)$payload->sub);
            if (!$user) {
                $response = (new ResponseFactory())->createResponse();
                return JsonResponse::unauthorized($response);
            }

            // Attach user and permissions to request
            $request = $request
                ->withAttribute('auth_user', $user)
                ->withAttribute('auth_token_payload', $payload);

            return $handler->handle($request);
        } catch (RuntimeException $e) {
            $response = (new ResponseFactory())->createResponse();
            return JsonResponse::unauthorized($response);
        }
    }
}
