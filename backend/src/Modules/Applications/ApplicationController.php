<?php
declare(strict_types=1);

namespace App\Modules\Applications;

use App\Config\Database;
use App\Support\Json;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 3 — Application Tracking System (Owner: Samin)
 *
 * ApplicationController — students apply, admins moderate.
 *
 * - GET    /api/applications              role-aware listing
 * - POST   /api/applications              students only
 * - PUT    /api/applications/{id}/status  admins only
 * - DELETE /api/applications/{id}         students (own + pending only)
 */
final class ApplicationController
{
    public function index(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $role    = (string) ($payload['role'] ?? '');
        $status  = trim((string) ($request->getQueryParams()['status'] ?? ''));

        $sql = "SELECT a.application_id, a.job_id, a.user_id, a.cover_letter,
                       a.status, a.applied_at, a.updated_at,
                       j.title AS job_title, j.type AS job_type, j.deadline,
                       c.name AS company_name,
                       u.full_name AS applicant_name, u.email AS applicant_email
                FROM applications a
                JOIN jobs j ON j.job_id = a.job_id
                JOIN companies c ON c.company_id = j.company_id
                JOIN users u ON u.user_id = a.user_id
                WHERE 1=1";
        $args = [];

        if ($role === 'student') {
            $sql .= ' AND a.user_id = :uid';
            $args[':uid'] = $userId;
        }
        if (in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'], true)) {
            $sql .= ' AND a.status = :status';
            $args[':status'] = $status;
        }
        $sql .= ' ORDER BY a.applied_at DESC';

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

    public function create(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        $jobId       = (int) ($body['job_id'] ?? 0);
        $coverLetter = trim((string) ($body['cover_letter'] ?? ''));

        if ($jobId <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['job_id' => 'job_id is required.'],
            ]);
        }
        if (mb_strlen($coverLetter) > 5000) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['cover_letter' => 'Cover letter must be 5000 characters or fewer.'],
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare(
                'SELECT job_id, is_active, deadline FROM jobs WHERE job_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Job not found.',
                ]);
            }
            if ((int) $job['is_active'] !== 1) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'This job is no longer accepting applications.',
                ]);
            }

            $stmt = $pdo->prepare(
                'SELECT application_id FROM applications
                 WHERE job_id = :jid AND user_id = :uid LIMIT 1'
            );
            $stmt->execute([':jid' => $jobId, ':uid' => $userId]);
            if ($stmt->fetch()) {
                return Json::write($response, 409, [
                    'success' => false,
                    'message' => 'You have already applied for this job.',
                ]);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO applications (job_id, user_id, cover_letter, status)
                 VALUES (:jid, :uid, :cl, "pending")'
            );
            $stmt->execute([
                ':jid' => $jobId,
                ':uid' => $userId,
                ':cl'  => $coverLetter !== '' ? $coverLetter : null,
            ]);
            $id = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Application submitted.',
            'data'    => ['application_id' => $id],
        ]);
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $id     = (int) ($args['id'] ?? 0);
        $body   = $this->readJson($request);
        $status = trim((string) ($body['status'] ?? ''));

        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid application id.',
            ]);
        }
        if (!in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'], true)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['status' => 'Status must be pending, reviewed, accepted or rejected.'],
            ]);
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT application_id FROM applications WHERE application_id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Application not found.',
                ]);
            }

            $stmt = $pdo->prepare(
                'UPDATE applications SET status = :s WHERE application_id = :id'
            );
            $stmt->execute([':s' => $status, ':id' => $id]);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Status updated.',
            'data'    => ['application_id' => $id, 'status' => $status],
        ]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);

        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid application id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'SELECT user_id, status FROM applications WHERE application_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Application not found.',
                ]);
            }
            if ((int) $row['user_id'] !== $userId) {
                return Json::write($response, 403, [
                    'success' => false,
                    'message' => 'You can only withdraw your own applications.',
                ]);
            }
            if ($row['status'] !== 'pending') {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Only pending applications can be withdrawn.',
                ]);
            }

            $stmt = $pdo->prepare('DELETE FROM applications WHERE application_id = :id');
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Application withdrawn.',
        ]);
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
