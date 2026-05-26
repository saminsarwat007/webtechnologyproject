<?php
declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Json — tiny helper to write a uniform JSON response body.
 *
 * Standard envelope:
 *   { "success": bool, "message": string, "data"?: mixed, "errors"?: object }
 */
final class Json
{
    public static function write(Response $response, int $status, array $body): Response
    {
        $response->getBody()->write(json_encode(
            $body,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
