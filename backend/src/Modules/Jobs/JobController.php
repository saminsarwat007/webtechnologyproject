<?php
declare(strict_types=1);

namespace App\Modules\Jobs;

use App\Config\Database;
use App\Support\Json;
use DateTime;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 2 — Job & Internship Management (Owner: Samin)
 *
 * JobController — full CRUD for job/internship listings.
 *
 * GET endpoints are public.
 * POST/PUT/DELETE require admin or superadmin via JwtMiddleware.
 */
final class JobController
{
    public function index(Request $request, Response $response): Response
    {
        $params   = $request->getQueryParams();
        $search   = trim((string) ($params['search'] ?? ''));
        $type     = trim((string) ($params['type'] ?? ''));
        $companyId = isset($params['company_id']) ? (int) $params['company_id'] : 0;

        $sql = "SELECT j.job_id, j.company_id, j.posted_by, j.title, j.type,
                       j.description, j.requirements, j.deadline,
                       j.is_active, j.created_at,
                       c.name AS company_name, c.location AS company_location
                FROM jobs j
                JOIN companies c ON c.company_id = j.company_id
                WHERE j.is_active = 1";
        $args = [];

        if ($search !== '') {
            // Native PDO prepares require each placeholder used exactly once.
            $sql .= ' AND (j.title LIKE :qt OR c.name LIKE :qc)';
            $like = '%' . $search . '%';
            $args[':qt'] = $like;
            $args[':qc'] = $like;
        }
        if (in_array($type, ['internship', 'fulltime', 'parttime'], true)) {
            $sql .= ' AND j.type = :type';
            $args[':type'] = $type;
        }
        if ($companyId > 0) {
            $sql .= ' AND j.company_id = :cid';
            $args[':cid'] = $companyId;
        }

        $sql .= ' ORDER BY j.created_at DESC';

        try {
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute($args);
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

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid job id.',
            ]);
        }

        try {
            $stmt = Database::getConnection()->prepare(
                "SELECT j.*, c.name AS company_name, c.industry AS company_industry,
                        c.location AS company_location, u.full_name AS posted_by_name
                 FROM jobs j
                 JOIN companies c ON c.company_id = j.company_id
                 JOIN users u ON u.user_id = j.posted_by
                 WHERE j.job_id = :id
                 LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        if (!$row) {
            return Json::write($response, 404, [
                'success' => false,
                'message' => 'Job not found.',
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'data'    => $row,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $body    = $this->readJson($request);
        $payload = (array) $request->getAttribute('jwt_payload');
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
                'INSERT INTO jobs (company_id, posted_by, title, type, description, requirements, deadline, is_active)
                 VALUES (:cid, :uid, :title, :type, :descr, :req, :deadline, 1)'
            );
            $stmt->execute([
                ':cid'      => (int) $body['company_id'],
                ':uid'      => (int) $payload['user_id'],
                ':title'    => $body['title'],
                ':type'     => $body['type'],
                ':descr'    => $body['description'],
                ':req'      => $body['requirements'] ?? null,
                ':deadline' => $body['deadline'],
            ]);
            $id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "SELECT j.*, c.name AS company_name
                 FROM jobs j JOIN companies c ON c.company_id = j.company_id
                 WHERE j.job_id = :id"
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
            'message' => 'Job created.',
            'data'    => $row,
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid job id.',
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
            $stmt = $pdo->prepare('SELECT job_id FROM jobs WHERE job_id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Job not found.',
                ]);
            }

            $stmt = $pdo->prepare(
                'UPDATE jobs
                 SET company_id = :cid, title = :title, type = :type,
                     description = :descr, requirements = :req, deadline = :deadline
                 WHERE job_id = :id'
            );
            $stmt->execute([
                ':cid'      => (int) $body['company_id'],
                ':title'    => $body['title'],
                ':type'     => $body['type'],
                ':descr'    => $body['description'],
                ':req'      => $body['requirements'] ?? null,
                ':deadline' => $body['deadline'],
                ':id'       => $id,
            ]);

            $stmt = $pdo->prepare(
                "SELECT j.*, c.name AS company_name
                 FROM jobs j JOIN companies c ON c.company_id = j.company_id
                 WHERE j.job_id = :id"
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
            'message' => 'Job updated.',
            'data'    => $row,
        ]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid job id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT job_id FROM jobs WHERE job_id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Job not found.',
                ]);
            }

            $stmt = $pdo->prepare('UPDATE jobs SET is_active = 0 WHERE job_id = :id');
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Job deactivated.',
        ]);
    }

    private function validate(array $body): array
    {
        $errors = [];

        if (empty($body['company_id']) || !ctype_digit((string) $body['company_id'])) {
            $errors['company_id'] = 'company_id is required.';
        }
        if (empty($body['title']) || mb_strlen((string) $body['title']) > 150) {
            $errors['title'] = 'Title is required (max 150 chars).';
        }
        if (empty($body['type']) || !in_array($body['type'], ['internship', 'fulltime', 'parttime'], true)) {
            $errors['type'] = 'Type must be internship, fulltime or parttime.';
        }
        if (empty($body['description'])) {
            $errors['description'] = 'Description is required.';
        }

        $deadline = (string) ($body['deadline'] ?? '');
        if ($deadline === '') {
            $errors['deadline'] = 'Deadline is required.';
        } else {
            $dt = DateTime::createFromFormat('Y-m-d', $deadline);
            if (!$dt || $dt->format('Y-m-d') !== $deadline) {
                $errors['deadline'] = 'Deadline must be in YYYY-MM-DD format.';
            } elseif ($dt < new DateTime('today')) {
                $errors['deadline'] = 'Deadline must be a future date.';
            }
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
