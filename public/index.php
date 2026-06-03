<?php

declare(strict_types=1);
set_time_limit(600); // Sets timeout to 600 seconds

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

if (empty($_ENV['JWT_SECRET']) || $_ENV['JWT_SECRET'] === 'default-secret') {
    throw new \RuntimeException('JWT_SECRET must be set to a strong value in .env');
}

// Build DI Container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add middleware
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// Error middleware
$errorMiddleware = $app->addErrorMiddleware(
    (bool)($_ENV['APP_DEBUG'] ?? false),
    true,
    true
);

$errorMiddleware->setDefaultErrorHandler(function ($request, $exception, $displayErrorDetails) use ($app) {
    $payload = [
        'success' => false,
        'message' => $displayErrorDetails ? $exception->getMessage() : 'An error occurred',
    ];
    $response = $app->getResponseFactory()->createResponse(500);
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

// ↓ CORS must be added LAST so it executes FIRST
$app->add(function ($request, $handler) use ($app) {
    $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:4200');
    $origin = $request->getHeaderLine('Origin');

    if ($request->getMethod() === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse(200);
        if (in_array($origin, $allowedOrigins)) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $response;
    }

    $response = $handler->handle($request);

    if (in_array($origin, $allowedOrigins)) {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    return $response;
});

// Wire PermissionMiddleware with DI container
\App\Shared\Middleware\PermissionMiddleware::setContainer($container);

// Register routes
(require __DIR__ . '/../routes/api.php')($app);

$app->run();