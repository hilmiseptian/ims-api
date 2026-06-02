<?php

declare(strict_types=1);

namespace App\Modules\Page\Controllers;

use App\Modules\Page\Services\PageService;
use App\Shared\Response\JsonResponse;
use App\Shared\Exceptions\{ValidationException, NotFoundException, ConflictException};
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PageController
{
    public function __construct(private readonly PageService $pageService) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $activeOnly = isset($params['active']) && $params['active'] === 'true';
        return JsonResponse::success($response, $this->pageService->getAll(
            (int)($params['page'] ?? 1),
            (int)($params['limit'] ?? 20),
            $activeOnly
        ), 'Pages retrieved');
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->pageService->getById((int)$args['id']), 'Page retrieved');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        }
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $page = $this->pageService->create($request->getParsedBody() ?? []);
            return JsonResponse::success($response, $page, 'Page created', 201);
        } catch (ValidationException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422, $e->getErrors());
        } catch (ConflictException $e) {
            return JsonResponse::error($response, $e->getMessage(), 409);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $page = $this->pageService->update((int)$args['id'], $request->getParsedBody() ?? []);
            return JsonResponse::success($response, $page, 'Page updated');
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
            $this->pageService->delete((int)$args['id']);
            return JsonResponse::success($response, null, 'Page deleted');
        } catch (NotFoundException $e) {
            return JsonResponse::notFound($response, $e->getMessage());
        }
    }
}
