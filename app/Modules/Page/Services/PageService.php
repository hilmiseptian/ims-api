<?php

declare(strict_types=1);

namespace App\Modules\Page\Services;

use App\Modules\Page\Repositories\PageRepository;
use App\Shared\Exceptions\{ValidationException, NotFoundException, ConflictException};

class PageService
{
    public function __construct(private readonly PageRepository $pageRepository) {}

    public function getAll(int $page = 1, int $limit = 20, bool $activeOnly = false): array
    {
        return $this->pageRepository->findAll($activeOnly);
    }

    public function getById(int $id): array
    {
        $page = $this->pageRepository->findById($id);
        if (!$page) throw new NotFoundException("Page not found");
        return $page;
    }

    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if (!empty($errors)) throw new ValidationException($errors);

        if ($this->pageRepository->existsByRoutePath($data['route_path'])) {
            throw new ConflictException('Route path already exists');
        }

        $data['is_active'] = $data['is_active'] ?? true;
        return $this->pageRepository->create($data);
    }

    public function update(int $id, array $data): array
    {
        $page = $this->pageRepository->findById($id);
        if (!$page) throw new NotFoundException("Page not found");

        $errors = $this->validate($data, true);
        if (!empty($errors)) throw new ValidationException($errors);

        if (isset($data['route_path']) && $this->pageRepository->existsByRoutePath($data['route_path'], $id)) {
            throw new ConflictException('Route path already exists');
        }

        return $this->pageRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $page = $this->pageRepository->findById($id);
        if (!$page) throw new NotFoundException("Page not found");
        $this->pageRepository->delete($id);
    }

    private function validate(array $data, bool $partial = false): array
    {
        $errors = [];
        if (!$partial) {
            if (empty($data['name'])) $errors['name'] = 'Page name is required';
            if (empty($data['route_path'])) $errors['route_path'] = 'Route path is required';
        }
        if (isset($data['route_path']) && !preg_match('/^\/[a-zA-Z0-9\-\/_]*$/', $data['route_path'])) {
            $errors['route_path'] = 'Route path must start with / and contain valid characters';
        }
        return $errors;
    }
}
