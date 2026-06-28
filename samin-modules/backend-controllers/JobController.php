<?php
// ==================================================================
// SAMIN — M2: JobController.php
// This is the backend controller for Job Management (Module 2).
// It handles all CRUD operations for job listings:
// - CRUD: LIST all active jobs (with search and filter)
// - CRUD: SHOW one job's full details
// - CRUD: CREATE a new job (admin only)
// - CRUD: UPDATE an existing job (admin only)
// - CRUD: DELETE (soft delete) a job (admin only)
// ==================================================================

// strict_types=1 means PHP enforces exact type matching (e.g., int vs string)
declare(strict_types=1);

// This namespace tells PHP where this class lives (like a folder path)
namespace App\Modules\Jobs;

// Import the Database connection class (built by Areeb)
use App\Config\Database;
// Import the JSON response helper (built by Areeb)
use App\Support\Json;
// Import DateTime for validating deadline dates
use DateTime;
// Import PDO (PHP Data Objects) for safe database queries
use PDO;
// Import PDOException for catching database errors
use PDOException;
// Import the Request and Response interfaces from Slim 4 framework
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 2 — Job & Internship Management (Owner: Samin)
 *
 * JobController — full CRUD for job/internship listings.
 *
 * GET endpoints are public (anyone can view jobs).
 * POST/PUT/DELETE require admin or superadmin via JwtMiddleware.
 */
final class JobController
{
    // ==================================================================
    // index() — LIST ALL ACTIVE JOBS (with optional search and filter) — CRUD: READ (list)
    // URL: GET /api/jobs
    // Who can access: Anyone (public, no login needed)
    // ==================================================================
    public function index(Request $request, Response $response): Response
    {
        // Read query parameters from the URL (e.g., ?search=engineer&type=internship)
        $params   = $request->getQueryParams();
        // Get the search term (or empty string if not provided)
        $search   = trim((string) ($params['search'] ?? ''));
        // Get the job type filter (or empty string if not provided)
        $type     = trim((string) ($params['type'] ?? ''));
        // Get the company_id filter (or 0 if not provided)
        $companyId = isset($params['company_id']) ? (int) $params['company_id'] : 0;

        // Build the SQL query — start with the base query
        // We JOIN with companies table to get the company name and location
        // We only show active jobs (is_active = 1)
        $sql = "SELECT j.job_id, j.company_id, j.posted_by, j.title, j.type,
                       j.description, j.requirements, j.deadline,
                       j.is_active, j.created_at,
                       c.name AS company_name, c.location AS company_location
                FROM jobs j
                JOIN companies c ON c.company_id = j.company_id
                WHERE j.is_active = 1";
        // $args will hold the values for the PDO placeholders (like :qt, :type)
        $args = [];

        // If a search term was provided, add a WHERE clause to search by title or company name
        if ($search !== '') {
            // We use two placeholders (:qt for title, :qc for company) because
            // PDO requires each placeholder to be used exactly once
            $sql .= ' AND (j.title LIKE :qt OR c.name LIKE :qc)';
            // Add wildcards (% means "any characters before/after")
            $like = '%' . $search . '%';
            $args[':qt'] = $like;  // :qt = search term for title
            $args[':qc'] = $like;  // :qc = search term for company name
        }
        // If a valid job type was provided, add a WHERE clause to filter by type
        if (in_array($type, ['internship', 'fulltime', 'parttime'], true)) {
            $sql .= ' AND j.type = :type';
            $args[':type'] = $type;
        }
        // If a company_id was provided, add a WHERE clause to filter by company
        if ($companyId > 0) {
            $sql .= ' AND j.company_id = :cid';
            $args[':cid'] = $companyId;
        }

        // Always sort by newest first (most recently created jobs appear at the top)
        $sql .= ' ORDER BY j.created_at DESC';

        try {
            // Prepare the SQL query using PDO (this prevents SQL injection)
            $stmt = Database::getConnection()->prepare($sql);
            // Execute the query with the placeholder values
            $stmt->execute($args);
            // Fetch all matching rows as associative arrays (column names as keys)
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If the database query fails, return a 500 error with the message
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        // Success! Return the jobs as JSON with status 200
        return Json::write($response, 200, [
            'success' => true,
            'data'    => $rows,
        ]);
    }

    // ==================================================================
    // show() — SHOW ONE JOB'S FULL DETAILS — CRUD: READ (single)
    // URL: GET /api/jobs/{id}
    // Who can access: Anyone (public, no login needed)
    // ==================================================================
    public function show(Request $request, Response $response, array $args): Response
    {
        // Get the job ID from the URL (e.g., /api/jobs/5 -> $id = 5)
        $id = (int) ($args['id'] ?? 0);
        // If the ID is invalid (0 or negative), return a 400 error
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid job id.',
            ]);
        }

        try {
            // Prepare a query that JOINs 3 tables:
            // - jobs (my table) for the job data
            // - companies (Mariam's table) for company name, industry, location
            // - users (Areeb's table) for the name of who posted the job
            $stmt = Database::getConnection()->prepare(
                "SELECT j.*, c.name AS company_name, c.industry AS company_industry,
                        c.location AS company_location, u.full_name AS posted_by_name
                 FROM jobs j
                 JOIN companies c ON c.company_id = j.company_id
                 JOIN users u ON u.user_id = j.posted_by
                 WHERE j.job_id = :id
                 LIMIT 1"
            );
            // Execute with the job ID
            $stmt->execute([':id' => $id]);
            // Fetch one row (or false if not found)
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Database error — return 500
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        // If no job was found with this ID, return 404
        if (!$row) {
            return Json::write($response, 404, [
                'success' => false,
                'message' => 'Job not found.',
            ]);
        }

        // Success! Return the job data as JSON with status 200
        return Json::write($response, 200, [
            'success' => true,
            'data'    => $row,
        ]);
    }

    // ==================================================================
    // create() — CREATE A NEW JOB
    // URL: POST /api/jobs
    // Who can access: Admin / Super Admin (enforced by JwtMiddleware in index.php)
    // ==================================================================
    public function create(Request $request, Response $response): Response
    {
        // Read the JSON body from the request (title, company_id, type, etc.)
        $body    = $this->readJson($request);
        // Get the JWT payload (contains user_id, email, role from the login token)
        // This is how we know WHO is creating the job
        $payload = (array) $request->getAttribute('jwt_payload');
        // Validate all fields — if any are invalid, return errors
        $errors  = $this->validate($body);
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            // Get the database connection
            $pdo = Database::getConnection();
            // Prepare the INSERT query using PDO placeholders
            // Note: posted_by comes from the JWT token (not from user input) — this is secure
            // is_active is set to 1 (the job is visible to students immediately)
            $stmt = $pdo->prepare(
                'INSERT INTO jobs (company_id, posted_by, title, type, description, requirements, deadline, is_active)
                 VALUES (:cid, :uid, :title, :type, :descr, :req, :deadline, 1)'
            );
            // Execute with the actual values
            $stmt->execute([
                ':cid'      => (int) $body['company_id'],           // company ID from the form
                ':uid'      => (int) $payload['user_id'],            // user ID from JWT token (who is creating this job)
                ':title'    => $body['title'],                       // job title
                ':type'     => $body['type'],                        // job type (internship/fulltime/parttime)
                ':descr'    => $body['description'],                 // job description
                ':req'      => $body['requirements'] ?? null,        // requirements (optional, can be NULL)
                ':deadline' => $body['deadline'],                    // application deadline
            ]);
            // Get the auto-generated ID of the newly created job
            $id = (int) $pdo->lastInsertId();

            // Fetch the new job with company name (to return to the frontend)
            $stmt = $pdo->prepare(
                "SELECT j.*, c.name AS company_name
                 FROM jobs j JOIN companies c ON c.company_id = j.company_id
                 WHERE j.job_id = :id"
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Database error — return 500
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        // Success! Return 201 Created with the new job data
        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Job created.',
            'data'    => $row,
        ]);
    }

    // ==================================================================
    // update() — UPDATE AN EXISTING JOB
    // URL: PUT /api/jobs/{id}
    // Who can access: Admin / Super Admin (enforced by JwtMiddleware)
    // ==================================================================
    public function update(Request $request, Response $response, array $args): Response
    {
        // Get the job ID from the URL
        $id = (int) ($args['id'] ?? 0);
        // Validate the ID
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid job id.',
            ]);
        }

        // Read the JSON body and validate all fields
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
            // First check: does this job exist?
            $stmt = $pdo->prepare('SELECT job_id FROM jobs WHERE job_id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                // Job not found — return 404
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Job not found.',
                ]);
            }

            // Update the job with the new values
            // Note: we don't update posted_by (the creator stays the same)
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

            // Fetch the updated job with company name (to return to the frontend)
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

        // Success! Return 200 with the updated job data
        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Job updated.',
            'data'    => $row,
        ]);
    }

    // ==================================================================
    // delete() — SOFT DELETE A JOB (deactivate, don't actually delete) — CRUD: DELETE
    // URL: DELETE /api/jobs/{id}
    // Who can access: Admin / Super Admin (enforced by JwtMiddleware)
    //
    // IMPORTANT: This does NOT run DELETE FROM jobs. Instead, it sets
    // is_active = 0. This is called a "soft delete". The job stays in
    // the database (for applications and audit) but is hidden from students.
    // ==================================================================
    public function delete(Request $request, Response $response, array $args): Response
    {
        // Get the job ID from the URL
        $id = (int) ($args['id'] ?? 0);
        // Validate the ID
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid job id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();
            // First check: does this job exist?
            $stmt = $pdo->prepare('SELECT job_id FROM jobs WHERE job_id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                // Job not found — return 404
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Job not found.',
                ]);
            }

            // SOFT DELETE: set is_active to 0 (instead of actually deleting the row)
            // This hides the job from students but keeps it in the database
            $stmt = $pdo->prepare('UPDATE jobs SET is_active = 0 WHERE job_id = :id');
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        // Success! Return 200 with a message
        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Job deactivated.',
        ]);
    }

    // ==================================================================
    // validate() — CHECK ALL FIELDS BEFORE CREATING OR UPDATING A JOB
    // Returns an array of error messages (empty array = all valid)
    // This is backend validation (the frontend also validates, but
    // someone could bypass the frontend and send a request directly)
    // ==================================================================
    private function validate(array $body): array
    {
        $errors = [];

        // company_id must be a non-empty number
        if (empty($body['company_id']) || !ctype_digit((string) $body['company_id'])) {
            $errors['company_id'] = 'company_id is required.';
        }
        // title must be non-empty and max 150 characters
        if (empty($body['title']) || mb_strlen((string) $body['title']) > 150) {
            $errors['title'] = 'Title is required (max 150 chars).';
        }
        // type must be one of the three allowed values
        if (empty($body['type']) || !in_array($body['type'], ['internship', 'fulltime', 'parttime'], true)) {
            $errors['type'] = 'Type must be internship, fulltime or parttime.';
        }
        // description must be non-empty
        if (empty($body['description'])) {
            $errors['description'] = 'Description is required.';
        }

        // deadline must be a valid date in YYYY-MM-DD format and must be in the future
        $deadline = (string) ($body['deadline'] ?? '');
        if ($deadline === '') {
            // No deadline provided
            $errors['deadline'] = 'Deadline is required.';
        } else {
            // Try to parse the date — if it fails, the format is wrong
            $dt = DateTime::createFromFormat('Y-m-d', $deadline);
            if (!$dt || $dt->format('Y-m-d') !== $deadline) {
                $errors['deadline'] = 'Deadline must be in YYYY-MM-DD format.';
            } elseif ($dt < new DateTime('today')) {
                // Date is valid but in the past
                $errors['deadline'] = 'Deadline must be a future date.';
            }
        }

        return $errors;
    }

    // ==================================================================
    // readJson() — READ THE JSON BODY FROM THE HTTP REQUEST
    // Converts the raw JSON string from the request body into a PHP array
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
