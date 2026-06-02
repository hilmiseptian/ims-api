<?php

declare(strict_types=1);

namespace App\Modules\Level\Controllers;

use App\Modules\Level\Services\LevelService;
use App\Shared\Response\JsonResponse;
use App\Shared\Exceptions\{ValidationException, NotFoundException, ConflictException};
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LevelController
{
    public function __construct(private readonly LevelService $levelService) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params     = $request->getQueryParams();
        $activeOnly = isset($params['active']) && $params['active'] === 'true';
        $search     = (string) ($params['search'] ?? '');

        $result = $this->levelService->getAll($activeOnly, $search);
        return JsonResponse::success($response, $result, 'Levels retrieved');
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->levelService->getById((int) $args['id']), 'Level retrieved');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        }
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $level = $this->levelService->create($request->getParsedBody() ?? []);
            return JsonResponse::success($response, $level, 'Level created', 201);
        } catch (ValidationException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422, $e->getErrors());
        } catch (ConflictException $e) {
            return JsonResponse::error($response, $e->getMessage(), 409);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $level = $this->levelService->update((int) $args['id'], $request->getParsedBody() ?? []);
            return JsonResponse::success($response, $level, 'Level updated');
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
            $this->levelService->delete((int) $args['id']);
            return JsonResponse::success($response, null, 'Level deleted');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        }
    }
}
