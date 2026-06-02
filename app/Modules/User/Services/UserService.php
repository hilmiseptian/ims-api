<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Repositories\UserRepository;
use App\Shared\Exceptions\{ValidationException, NotFoundException, ConflictException};

class UserService
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function getAll(int $page = 1, int $limit = 20, string $search = ''): array
    {
        return $this->userRepository->findAll($page, $limit, $search);
    }

    public function getById(int $id): array
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException("User not found");
        }
        return $user;
    }

    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if ($this->userRepository->existsByUsername($data['username'])) {
            throw new ConflictException('Username already taken');
        }
        if ($this->userRepository->existsByEmail($data['email'])) {
            throw new ConflictException('Email already taken');
        }

        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $data['is_active'] = $data['is_active'] ?? true;

        return $this->userRepository->create($data);
    }

    public function update(int $id, array $data): array
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException("User not found");
        }

        $errors = $this->validate($data, $id);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if (isset($data['username']) && $this->userRepository->existsByUsername($data['username'], $id)) {
            throw new ConflictException('Username already taken');
        }
        if (isset($data['email']) && $this->userRepository->existsByEmail($data['email'], $id)) {
            throw new ConflictException('Email already taken');
        }

        if (!empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        unset($data['password']);

        return $this->userRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException("User not found");
        }
        $this->userRepository->delete($id);
    }

    private function validate(array $data, ?int $userId = null): array
    {
        $errors = [];

        if ($userId === null) {
            // Create: all required
            if (empty($data['full_name'])) $errors['full_name'] = 'Full name is required';
            if (empty($data['username'])) $errors['username'] = 'Username is required';
            if (empty($data['email'])) $errors['email'] = 'Email is required';
            if (empty($data['password'])) $errors['password'] = 'Password is required';
            if (empty($data['level_id'])) $errors['level_id'] = 'Level is required';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (!empty($data['username']) && !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
            $errors['username'] = 'Username must be 3-50 alphanumeric characters or underscores';
        }

        return $errors;
    }
}
