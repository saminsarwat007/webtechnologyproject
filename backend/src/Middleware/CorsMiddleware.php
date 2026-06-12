<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

/**
 * CorsMiddleware
 *
 * - Permits the dev frontend (Vite, http://localhost:5173) plus any origin
 *   when CORS_ALLOW_ALL=1 is set in .env (useful for quick API testing).
 * - Handles OPTIONS preflight by short-circuiting with 200.
 * - Always echoes the requested headers/methods so the browser is happy.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    private array $allowedOrigins = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        //'https://your-frontend-domain.com',
    ];

    public function process(Request $request, Handler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');
        $allowAll = ($_ENV['CORS_ALLOW_ALL'] ?? '') === '1';

        if ($request->getMethod() === 'OPTIONS') {
            $response = new SlimResponse();
            return $this->withCorsHeaders($response, $origin, $allowAll)
                ->withStatus(200);
        }

        $response = $handler->handle($request);
        return $this->withCorsHeaders($response, $origin, $allowAll);
    }

    private function withCorsHeaders(Response $response, string $origin, bool $allowAll): Response
    {
        $allowedOrigin = '*';
        if (!$allowAll) {
            $allowedOrigin = in_array($origin, $this->allowedOrigins, true)
                ? $origin
                : $this->allowedOrigins[0];
        } elseif ($origin !== '') {
            $allowedOrigin = $origin;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', '86400');
    }
}
