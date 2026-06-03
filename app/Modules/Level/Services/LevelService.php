<?php

declare(strict_types=1);

namespace App\Modules\Level\Services;

use App\Modules\Level\Repositories\LevelRepository;
use App\Shared\Exceptions\{ValidationException, NotFoundException, ConflictException};

class LevelService
{
    public function __construct(private readonly LevelRepository $levelRepository) {}

    public function getAllActive(): array
    {
        $result = $this->levelRepository->findAll(activeOnly: true);
        return $result['data'];
    }

    public function getAll(bool $activeOnly = false, string $search = ''): array
    {
        return $this->levelRepository->findAll($activeOnly, $search);
    }

    public function getById(int $id): array
    {
        $level = $this->levelRepository->findById($id);
        if (!$level) {
            throw new NotFoundException('Level not found');
        }
        return $level;
    }

    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if ($this->levelRepository->existsByName($data['name'])) {
            throw new ConflictException('Level name already exists');
        }

        $data['is_active'] = $data['is_active'] ?? true;
        return $this->levelRepository->create($data);
    }

    public function update(int $id, array $data): array
    {
        $level = $this->levelRepository->findById($id);
        if (!$level) {
            throw new NotFoundException('Level not found');
        }

        $errors = $this->validate($data, true);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if (isset($data['name']) && $this->levelRepository->existsByName($data['name'], $id)) {
            throw new ConflictException('Level name already exists');
        }

        return $this->levelRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $level = $this->levelRepository->findById($id);
        if (!$level) {
            throw new NotFoundException('Level not found');
        }

        $this->levelRepository->softDelete($id);
    }

    private function validate(array $data, bool $partial = false): array
    {
        $errors = [];

        if (!$partial && empty($data['name'])) {
            $errors['name'] = 'Level name is required';
        }

        if (isset($data['name']) && strlen($data['name']) > 100) {
            $errors['name'] = 'Level name must not exceed 100 characters';
        }

        return $errors;
    }
}
