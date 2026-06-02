<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Modules\User\Services\UserService;
use App\Shared\Response\JsonResponse;
use App\Shared\Exceptions\{ValidationException, NotFoundException, ConflictException};
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController
{
    public function __construct(private readonly UserService $userService) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $result = $this->userService->getAll(
            (int)($params['page'] ?? 1),
            (int)($params['limit'] ?? 20),
            (string)($params['search'] ?? '')
        );
        return JsonResponse::success($response, $result, 'Users retrieved');
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $user = $this->userService->getById((int)$args['id']);
            return JsonResponse::success($response, $user, 'User retrieved');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        }
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $user = $this->userService->create($data);
            return JsonResponse::success($response, $user, 'User created', 201);
        } catch (ValidationException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422, $e->getErrors());
        } catch (ConflictException $e) {
            return JsonResponse::error($response, $e->getMessage(), 409);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $user = $this->userService->update((int)$args['id'], $data);
            return JsonResponse::success($response, $user, 'User updated');
        } catch (ValidationException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        } catch (ConflictException $e) {
            return JsonResponse::error($response, $e->getMessage(), 409);
        }
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->userService->delete((int)$args['id']);
            return JsonResponse::success($response, null, 'User deleted');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        }
    }
}
