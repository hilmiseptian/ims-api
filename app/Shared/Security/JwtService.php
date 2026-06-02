<?php

declare(strict_types=1);

namespace App\Shared\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use RuntimeException;
use stdClass;

class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $expiry = 86400
    ) {}

    public function generate(array $payload): string
    {
        $now = time();
        $data = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->expiry,
            'nbf' => $now,
        ]);
        return JWT::encode($data, $this->secret, self::ALGORITHM);
    }

    public function decode(string $token): stdClass
    {
        try {
            return JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (ExpiredException $e) {
            throw new RuntimeException('Token has expired', 401);
        } catch (SignatureInvalidException $e) {
            throw new RuntimeException('Invalid token signature', 401);
        } catch (\Exception $e) {
            throw new RuntimeException('Invalid token', 401);
        }
    }

    public function extractFromHeader(string $authHeader): string
    {
        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new RuntimeException('Invalid authorization header format', 401);
        }
        return substr($authHeader, 7);
    }
}
