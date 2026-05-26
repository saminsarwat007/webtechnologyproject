<?php
declare(strict_types=1);

namespace App\Modules\Companies;

use App\Config\Database;
use App\Support\Json;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 4 — Company & External Source Management (Owner: Mariam)
 *
 * CompanyController — list / create / update / delete companies.
 *
 * GET requires JWT (any role); writes require admin or superadmin.
 */
final class CompanyController
{
    public function index(Request $request, Response $response): Response
    {
        try {
            $stmt = Database::getConnection()->query(
                'SELECT company_id, name, industry, location, description, created_at
                 FROM companies ORDER BY name ASC'
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
        $body    = $this->readJson($request);
        $errors  = $this->validate($body);

        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO companies (name, industry, location, description, created_by)
                 VALUES (:n, :i, :l, :d, :cb)'
            );
            $stmt->execute([
                ':n'  => $body['name'],
                ':i'  => $body['industry'],
                ':l'  => $body['location'],
                ':d'  => $body['description'] ?? null,
                ':cb' => (int) ($payload['user_id'] ?? 0),
            ]);
            $id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('SELECT * FROM companies WHERE company_id = :id');
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
            'message' => 'Company created.',
            'data'    => $row,
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid company id.',
            ]);
        }

        $body   = $this->readJson($request);
        $errors = $this->validate($body);
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT company_id FROM companies WHERE company_id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Company not found.',
                ]);
            }

            $stmt = $pdo->prepare(
                'UPDATE companies
                 SET name = :n, industry = :i, location = :l, description = :d
                 WHERE company_id = :id'
            );
            $stmt->execute([
                ':n'  => $body['name'],
                ':i'  => $body['industry'],
                ':l'  => $body['location'],
                ':d'  => $body['description'] ?? null,
                ':id' => $id,
            ]);

            $stmt = $pdo->prepare('SELECT * FROM companies WHERE company_id = :id');
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
            'message' => 'Company updated.',
            'data'    => $row,
        ]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid company id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM jobs WHERE company_id = :id');
            $stmt->execute([':id' => $id]);
            $linked = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];
            if ($linked > 0) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Cannot delete: company still has jobs attached.',
                ]);
            }

            $stmt = $pdo->prepare('DELETE FROM companies WHERE company_id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Company not found.',
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
            'message' => 'Company deleted.',
        ]);
    }

    private function validate(array $body): array
    {
        $errors = [];
        if (empty($body['name']) || mb_strlen((string) $body['name']) > 150) {
            $errors['name'] = 'Name is required (max 150 chars).';
        }
        if (empty($body['industry']) || mb_strlen((string) $body['industry']) > 100) {
            $errors['industry'] = 'Industry is required (max 100 chars).';
        }
        if (empty($body['location']) || mb_strlen((string) $body['location']) > 150) {
            $errors['location'] = 'Location is required (max 150 chars).';
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
