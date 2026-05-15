<?php
declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;
use Throwable;

/**
 * JwtMiddleware
 *
 * Verifies a Bearer token using firebase/php-jwt.
 *
 * Usage in routes:
 *   $app->post('/api/jobs',  [JobController::class, 'create'])
 *       ->add(new JwtMiddleware(['admin','superadmin']));
 *
 * On success it attaches the decoded payload as the request attribute
 * "jwt_payload" (an array) so controllers can read user_id / role.
 */
final class JwtMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    private array $requiredRoles;

    /**
     * @param string|string[]|null $requiredRoles  Allowed role(s) for this route.
     *                                             Null = any authenticated user.
     */
    public function __construct($requiredRoles = null)
    {
        if ($requiredRoles === null) {
            $this->requiredRoles = [];
        } elseif (is_string($requiredRoles)) {
            $this->requiredRoles = [$requiredRoles];
        } else {
            $this->requiredRoles = array_values($requiredRoles);
        }
    }

    public function process(Request $request, Handler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return $this->json(401, [
                'success' => false,
                'message' => 'Authorization header missing or malformed.',
            ]);
        }

        $token = trim(substr($authHeader, 7));
        if ($token === '') {
            return $this->json(401, [
                'success' => false,
                'message' => 'Bearer token is empty.',
            ]);
        }

        $secret = $_ENV['JWT_SECRET'] ?? '';
        if ($secret === '') {
            return $this->json(500, [
                'success' => false,
                'message' => 'Server JWT secret is not configured.',
            ]);
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $payload = (array) $decoded;
        } catch (Throwable $e) {
            return $this->json(401, [
                'success' => false,
                'message' => 'Invalid or expired token.',
            ]);
        }

        if (!isset($payload['user_id'], $payload['role'])) {
            return $this->json(401, [
                'success' => false,
                'message' => 'Token payload is missing required claims.',
            ]);
        }

        if (!empty($this->requiredRoles)
            && !in_array($payload['role'], $this->requiredRoles, true)) {
            return $this->json(403, [
                'success' => false,
                'message' => 'Forbidden: insufficient role.',
            ]);
        }

        $request = $request->withAttribute('jwt_payload', $payload);
        return $handler->handle($request);
    }

    private function json(int $status, array $body): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
