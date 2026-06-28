<?php
// ==================================================================
// SAMIN — M3: ApplicationController.php
// This is the backend controller for Application Tracking (Module 3).
// It handles all operations related to job applications (CRUD):
// - CRUD: LIST applications (students see their own, admins see all)
// - CRUD: APPLY for a job (students only, with 3 safety checks) — CREATE
// - CRUD: CHANGE application status (admins only) — UPDATE
// - CRUD: WITHDRAW an application (students only, own + pending only) — DELETE
// ==================================================================

// strict_types=1 means PHP enforces exact type matching
declare(strict_types=1);

// This namespace tells PHP where this class lives
namespace App\Modules\Applications;

// Import the Database connection class (built by Areeb)
use App\Config\Database;
// Import the JSON response helper (built by Areeb)
use App\Support\Json;
// Import PDO for safe database queries
use PDO;
// Import PDOException for catching database errors
use PDOException;
// Import the Request and Response interfaces from Slim 4 framework
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
    // ==================================================================
    // index() — LIST APPLICATIONS (role-aware) — CRUD: READ (list)
    // URL: GET /api/applications
    // Who can access: Any logged-in user (JwtMiddleware with no role restriction)
    //
    // This method is "role-aware" — it behaves differently based on who
    // is calling it:
    // - If a STUDENT calls it: returns only THEIR applications
    // - If an ADMIN calls it: returns ALL applications in the system
    // ==================================================================
    public function index(Request $request, Response $response): Response
    {
        // Get the JWT payload (user_id and role from the login token)
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);   // who is asking
        $role    = (string) ($payload['role'] ?? '');   // what role they have
        // Get optional status filter from the URL (e.g., ?status=pending)
        $status  = trim((string) ($request->getQueryParams()['status'] ?? ''));

        // Build the SQL query — JOIN 4 tables to get all the info we need:
        // - applications (my table) for the application data
        // - jobs (my table) for the job title, type, and deadline
        // - companies (Mariam's table) for the company name
        // - users (Areeb's table) for the applicant name and email
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
        // WHERE 1=1 is a trick — it's always true, so we can add AND clauses conditionally
        $args = [];

        // If the caller is a student, only show THEIR applications
        // (filter by their user_id from the JWT token)
        if ($role === 'student') {
            $sql .= ' AND a.user_id = :uid';
            $args[':uid'] = $userId;
        }
        // If a status filter was provided, add a WHERE clause
        if (in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'], true)) {
            $sql .= ' AND a.status = :status';
            $args[':status'] = $status;
        }
        // Sort by most recently applied first
        $sql .= ' ORDER BY a.applied_at DESC';

        try {
            // Prepare and execute the query using PDO (safe from SQL injection)
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute($args);
            // Fetch all matching rows
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Database error — return 500
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        // Success! Return the applications as JSON with status 200
        return Json::write($response, 200, [
            'success' => true,
            'data'    => $rows,
        ]);
    }

    // ==================================================================
    // create() — APPLY FOR A JOB — CRUD: CREATE
    // URL: POST /api/applications
    // Who can access: Students only (enforced by JwtMiddleware('student'))
    //
    // This method has 3 SAFETY CHECKS before inserting:
    // 1. Does the job exist?
    // 2. Is the job still active?
    // 3. Has this student already applied? (prevent duplicates)
    // ==================================================================
    public function create(Request $request, Response $response): Response
    {
        // Get the user_id from the JWT token (this is WHO is applying)
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        // Read the JSON body (contains job_id and cover_letter)
        $body    = $this->readJson($request);

        // Extract job_id and cover_letter from the request body
        $jobId       = (int) ($body['job_id'] ?? 0);
        $coverLetter = trim((string) ($body['cover_letter'] ?? ''));

        // VALIDATION: job_id must be a positive number
        if ($jobId <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['job_id' => 'job_id is required.'],
            ]);
        }
        // VALIDATION: cover_letter must be max 5000 characters
        if (mb_strlen($coverLetter) > 5000) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['cover_letter' => 'Cover letter must be 5000 characters or fewer.'],
            ]);
        }

        try {
            $pdo = Database::getConnection();

            // ---- SAFETY CHECK 1: Does the job exist? ----
            $stmt = $pdo->prepare(
                'SELECT job_id, is_active, deadline FROM jobs WHERE job_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            // If no job found, return 404
            if (!$job) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Job not found.',
                ]);
            }

            // ---- SAFETY CHECK 2: Is the job still active? ----
            // If the job was deactivated (is_active = 0), don't allow applications
            if ((int) $job['is_active'] !== 1) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'This job is no longer accepting applications.',
                ]);
            }

            // ---- SAFETY CHECK 3: Has this student already applied? ----
            // Check if an application already exists for this job + this student
            $stmt = $pdo->prepare(
                'SELECT application_id FROM applications
                 WHERE job_id = :jid AND user_id = :uid LIMIT 1'
            );
            $stmt->execute([':jid' => $jobId, ':uid' => $userId]);
            if ($stmt->fetch()) {
                // Already applied — return 409 Conflict
                return Json::write($response, 409, [
                    'success' => false,
                    'message' => 'You have already applied for this job.',
                ]);
            }

            // ---- ALL CHECKS PASSED — insert the application ----
            // The status is set to "pending" by default
            // If cover_letter is empty, store NULL (not an empty string)
            $stmt = $pdo->prepare(
                'INSERT INTO applications (job_id, user_id, cover_letter, status)
                 VALUES (:jid, :uid, :cl, "pending")'
            );
            $stmt->execute([
                ':jid' => $jobId,
                ':uid' => $userId,
                ':cl'  => $coverLetter !== '' ? $coverLetter : null,
            ]);
            // Get the auto-generated ID of the new application
            $id = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        // Success! Return 201 Created with the new application ID
        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Application submitted.',
            'data'    => ['application_id' => $id],
        ]);
    }

    // ==================================================================
    // updateStatus() — CHANGE AN APPLICATION'S STATUS — CRUD: UPDATE
    // URL: PUT /api/applications/{id}/status
    // Who can access: Admin / Super Admin (enforced by JwtMiddleware)
    //
    // The admin can change the status to: pending, reviewed, accepted, or rejected
    // ==================================================================
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        // Get the application ID from the URL
        $id     = (int) ($args['id'] ?? 0);
        // Read the JSON body (contains the new status)
        $body   = $this->readJson($request);
        // Extract the new status from the body
        $status = trim((string) ($body['status'] ?? ''));

        // Validate the application ID
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid application id.',
            ]);
        }
        // Validate the status — must be one of the 4 allowed values
        if (!in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'], true)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['status' => 'Status must be pending, reviewed, accepted or rejected.'],
            ]);
        }

        try {
            $pdo = Database::getConnection();
            // Check: does this application exist?
            $stmt = $pdo->prepare('SELECT application_id FROM applications WHERE application_id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                // Application not found — return 404
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Application not found.',
                ]);
            }

            // Update the status
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

        // Success! Return 200 with the updated status
        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Status updated.',
            'data'    => ['application_id' => $id, 'status' => $status],
        ]);
    }

    // ==================================================================
    // delete() — WITHDRAW AN APPLICATION — CRUD: DELETE
    // URL: DELETE /api/applications/{id}
    // Who can access: Students only (enforced by JwtMiddleware('student'))
    //
    // This method has 3 SECURITY CHECKS before deleting:
    // 1. Does the application exist?
    // 2. Does it belong to THIS student? (ownership check)
    // 3. Is it still "pending"? (can only withdraw pending applications)
    // ==================================================================
    public function delete(Request $request, Response $response, array $args): Response
    {
        // Get the application ID from the URL
        $id      = (int) ($args['id'] ?? 0);
        // Get the user_id from the JWT token (to check ownership)
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);

        // Validate the application ID
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid application id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();
            // Fetch the application to check ownership and status
            $stmt = $pdo->prepare(
                'SELECT user_id, status FROM applications WHERE application_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // ---- CHECK 1: Does the application exist? ----
            if (!$row) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Application not found.',
                ]);
            }

            // ---- CHECK 2: Does it belong to THIS student? ----
            // Compare the user_id in the database with the user_id from the JWT token
            // If they don't match, the student is trying to withdraw someone else's application
            if ((int) $row['user_id'] !== $userId) {
                return Json::write($response, 403, [
                    'success' => false,
                    'message' => 'You can only withdraw your own applications.',
                ]);
            }

            // ---- CHECK 3: Is the status still "pending"? ----
            // Once an admin has reviewed the application (status changed from "pending"),
            // the student can no longer withdraw it
            if ($row['status'] !== 'pending') {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Only pending applications can be withdrawn.',
                ]);
            }

            // ---- ALL CHECKS PASSED — delete the application ----
            // Note: this is a HARD DELETE (actually removes the row from the database)
            // Unlike jobs (which use soft delete), applications are fully deleted when withdrawn
            $stmt = $pdo->prepare('DELETE FROM applications WHERE application_id = :id');
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        // Success! Return 200
        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Application withdrawn.',
        ]);
    }

    // ==================================================================
    // readJson() — READ THE JSON BODY FROM THE HTTP REQUEST
    // Same helper as in JobController — converts raw JSON to a PHP array
    // ==================================================================
    private function readJson(Request $request): array
    {
        // Get the raw body string from the request
        $raw = (string) $request->getBody();
        // If the body is empty, return an empty array
        if ($raw === '') {
            return [];
        }
        // Parse the JSON string into a PHP array
        $data = json_decode($raw, true);
        // Return the array (or empty array if JSON parsing failed)
        return is_array($data) ? $data : [];
    }
}
