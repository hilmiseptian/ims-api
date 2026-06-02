<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Shared\Response\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DashboardController
{
  public function __construct(private readonly PDO $db) {}

  public function stats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $stats = [
      'users'       => (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
      'levels'      => (int) $this->db->query('SELECT COUNT(*) FROM levels WHERE deleted_at IS NULL')->fetchColumn(),
      'pages'       => (int) $this->db->query('SELECT COUNT(*) FROM pages')->fetchColumn(),
      'permissions' => (int) $this->db->query('SELECT COUNT(DISTINCT permission_key) FROM level_permissions')->fetchColumn(),
    ];

    return JsonResponse::success($response, $stats, 'Dashboard stats retrieved');
  }
}
