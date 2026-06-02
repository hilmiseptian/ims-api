<?php

declare(strict_types=1);

namespace App\Shared\Response;

use Psr\Http\Message\ResponseInterface;

class JsonResponse
{
    public static function success(
        ResponseInterface $response,
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ): ResponseInterface {
        $payload = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        return self::write($response, $payload, $status);
    }

    public static function error(
        ResponseInterface $response,
        string $message = 'Error',
        int $status = 400,
        array $errors = []
    ): ResponseInterface {
        $payload = ['success' => false, 'message' => $message];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        return self::write($response, $payload, $status);
    }

    public static function unauthorized(ResponseInterface $response): ResponseInterface
    {
        return self::write($response, ['success' => false, 'message' => 'Unauthorized'], 401);
    }

    public static function forbidden(ResponseInterface $response): ResponseInterface
    {
        return self::write($response, ['success' => false, 'message' => 'Forbidden'], 403);
    }

    public static function notFound(ResponseInterface $response, string $message = 'Not found'): ResponseInterface
    {
        return self::write($response, ['success' => false, 'message' => $message], 404);
    }

    private static function write(ResponseInterface $response, array $payload, int $status): ResponseInterface
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
