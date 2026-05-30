<?php
declare(strict_types=1);

namespace App\Modules\Labels;

use App\Config\Database;
use App\Support\Json;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 8 — Label & Tag Management (Owner: Monika)
 *
 * LabelController — full CRUD for forum post labels.
 *
 * - GET    /api/labels         any logged-in user
 * - POST   /api/labels         student or admin (any logged-in)
 * - PUT    /api/labels/{id}    admin / superadmin only
 * - DELETE /api/labels/{id}    admin / superadmin (rejected if posts reference it)
 */
final class LabelController
{
    public function index(Request $request, Response $response): Response
    {
        try {
            $stmt = Database::getConnection()->query(
                'SELECT l.label_id, l.name, l.created_by, l.created_at,
                        u.full_name AS created_by_name,
                        (SELECT COUNT(*) FROM posts p
                          WHERE p.label_id = l.label_id AND p.is_deleted = 0) AS post_count
                 FROM labels l
                 JOIN users u ON u.user_id = l.created_by
                 ORDER BY l.name ASC'
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'data'    => $rows,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        $name   = trim((string) ($body['name'] ?? ''));
        $errors = $this->validateName($name);
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare('SELECT label_id FROM labels WHERE name = :n LIMIT 1');
            $stmt->execute([':n' => $name]);
            if ($stmt->fetch()) {
                return Json::write($response, 409, [
                    'success' => false,
                    'message' => 'A label with this name already exists.',
                    'errors'  => ['name' => 'Label name must be unique.'],
                ]);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO labels (name, created_by) VALUES (:n, :cb)'
            );
            $stmt->execute([':n' => $name, ':cb' => $userId]);
            $id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'SELECT l.*, u.full_name AS created_by_name
                 FROM labels l JOIN users u ON u.user_id = l.created_by
                 WHERE l.label_id = :id'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Label created.',
            'data'    => $row,
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid label id.',
            ]);
        }

        $body   = $this->readJson($request);
        $name   = trim((string) ($body['name'] ?? ''));
        $errors = $this->validateName($name);
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT label_id FROM labels WHERE label_id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Label not found.',
                ]);
            }

            $stmt = $pdo->prepare(
                'SELECT label_id FROM labels WHERE name = :n AND label_id <> :id LIMIT 1'
            );
            $stmt->execute([':n' => $name, ':id' => $id]);
            if ($stmt->fetch()) {
                return Json::write($response, 409, [
                    'success' => false,
                    'message' => 'A label with this name already exists.',
                    'errors'  => ['name' => 'Label name must be unique.'],
                ]);
            }

            $stmt = $pdo->prepare('UPDATE labels SET name = :n WHERE label_id = :id');
            $stmt->execute([':n' => $name, ':id' => $id]);

            $stmt = $pdo->prepare(
                'SELECT l.*, u.full_name AS created_by_name
                 FROM labels l JOIN users u ON u.user_id = l.created_by
                 WHERE l.label_id = :id'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Label updated.',
            'data'    => $row,
        ]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid label id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();

            // Reject if any live posts still reference this label.
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM posts WHERE label_id = :id AND is_deleted = 0'
            );
            $stmt->execute([':id' => $id]);
            $linked = (int) $stmt->fetchColumn();
            if ($linked > 0) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Cannot delete: label is still attached to ' . $linked . ' post(s).',
                ]);
            }

            $stmt = $pdo->prepare('DELETE FROM labels WHERE label_id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Label not found.',
                ]);
            }
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Label deleted.',
        ]);
    }

    private function validateName(string $name): array
    {
        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Label name is required.';
        } elseif (mb_strlen($name) > 60) {
            $errors['name'] = 'Label name must be 60 characters or fewer.';
        }
        return $errors;
    }

    private function readJson(Request $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
