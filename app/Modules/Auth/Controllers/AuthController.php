<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Services\AuthService;
use App\Shared\ValidationException;
use App\Shared\UnauthorizedException;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = $request->getParsedBody() ?? [];
            $login = trim((string)($body['login'] ?? $body['username'] ?? $body['email'] ?? ''));
            $password = (string)($body['password'] ?? '');

            $result = $this->authService->login($login, $password);
            return JsonResponse::success($response, $result, 'Login successful');
        } catch (ValidationException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422, $e->getErrors());
        } catch (UnauthorizedException $e) {
            return JsonResponse::unauthorized($response);
        }
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('auth_user');
        $result = $this->authService->getCurrentUser($user);
        return JsonResponse::success($response, $result, 'Authenticated user');
    }

    public function refresh(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // In JWT-stateless setup, just re-authenticate via /me
        $user = $request->getAttribute('auth_user');
        $result = $this->authService->getCurrentUser($user);
        return JsonResponse::success($response, $result, 'Session refreshed');
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Stateless JWT - client should discard the token
        return JsonResponse::success($response, null, 'Logged out successfully');
    }
}
