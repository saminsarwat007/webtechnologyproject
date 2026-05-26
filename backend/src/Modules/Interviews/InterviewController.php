<?php
declare(strict_types=1);

namespace App\Modules\Interviews;

use App\Config\Database;
use App\Support\Json;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 8 — Mock Interview & Technical Prep Scheduler (Owner: Monika)
 *
 * Two tables:
 *   - interview_slots   : open availability windows posted by admins
 *   - mock_interviews   : a student's booking against a slot, plus
 *                         status / score / feedback after the session.
 *
 * Endpoints:
 *   GET    /api/interviews/slots                  any logged-in   list open slots
 *   POST   /api/interviews/slots                  admin           create a slot
 *   DELETE /api/interviews/slots/{id}             admin           remove an unbooked slot
 *
 *   GET    /api/interviews/my-sessions            student         my own bookings + scores
 *   POST   /api/interviews/bookings               student         book an open slot
 *   PUT    /api/interviews/bookings/{id}          student         change category / cancel
 *
 *   GET    /api/interviews/admin/manage           admin           every booking in the system
 *   PUT    /api/interviews/admin/evaluate/{id}    admin           submit score + feedback
 */
final class InterviewController
{
    // -----------------------------------------------------------------
    // SLOTS  (admin CRUD + public read)
    // -----------------------------------------------------------------

    /** GET /api/interviews/slots — any logged-in user. */
    public function listSlots(Request $request, Response $response): Response
    {
        $params  = $request->getQueryParams();
        $onlyOpen = !isset($params['all']) || $params['all'] !== '1';

        $sql = "SELECT s.slot_id, s.interviewer_id, s.scheduled_at, s.is_booked,
                       s.created_at, u.full_name AS interviewer_name
                FROM interview_slots s
                JOIN users u ON u.user_id = s.interviewer_id
                WHERE 1=1 ";
        if ($onlyOpen) {
            // Only future, unbooked slots are visible to students.
            $sql .= "  AND s.is_booked = 0
                       AND s.scheduled_at > NOW() ";
        }
        $sql .= "ORDER BY s.scheduled_at ASC";

        try {
            $rows = Database::getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        foreach ($rows as &$r) {
            $r['is_booked'] = (bool) $r['is_booked'];
        }

        return Json::write($response, 200, [
            'success' => true,
            'data'    => $rows,
        ]);
    }

    /** POST /api/interviews/slots — admin only. */
    public function createSlot(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        $scheduledAt = trim((string) ($body['scheduled_at']  ?? ''));
        $interviewer = isset($body['interviewer_id']) && $body['interviewer_id'] !== ''
            ? (int) $body['interviewer_id']
            : $userId;

        $errors = [];
        if ($scheduledAt === '') {
            $errors['scheduled_at'] = 'A scheduled date/time is required.';
        } else {
            $ts = strtotime($scheduledAt);
            if ($ts === false) {
                $errors['scheduled_at'] = 'Could not parse the scheduled date/time.';
            } elseif ($ts < time()) {
                $errors['scheduled_at'] = 'Scheduled date/time must be in the future.';
            }
        }
        if ($interviewer <= 0) {
            $errors['interviewer_id'] = 'Interviewer is required.';
        }
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo = Database::getConnection();

            // Sanity: confirm the chosen interviewer exists and is staff.
            $stmt = $pdo->prepare(
                "SELECT role FROM users WHERE user_id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $interviewer]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['interviewer_id' => 'Interviewer not found.'],
                ]);
            }
            if (!in_array((string) $row['role'], ['admin', 'superadmin'], true)) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['interviewer_id' => 'Interviewer must be an admin user.'],
                ]);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO interview_slots (interviewer_id, scheduled_at, is_booked)
                 VALUES (:i, :s, 0)'
            );
            $stmt->execute([
                ':i' => $interviewer,
                ':s' => date('Y-m-d H:i:s', strtotime($scheduledAt)),
            ]);
            $id  = (int) $pdo->lastInsertId();
            $row = $this->fetchSlotRow($pdo, $id);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Slot created.',
            'data'    => $row,
        ]);
    }

    /** DELETE /api/interviews/slots/{id} — admin only, unbooked slot. */
    public function deleteSlot(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid slot id.',
            ]);
        }

        try {
            $pdo  = Database::getConnection();
            $stmt = $pdo->prepare(
                'SELECT is_booked FROM interview_slots WHERE slot_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Slot not found.',
                ]);
            }
            if ((int) $row['is_booked'] === 1) {
                return Json::write($response, 409, [
                    'success' => false,
                    'message' => 'Cannot delete a slot that is already booked. Cancel the booking first.',
                ]);
            }

            $pdo->prepare('DELETE FROM interview_slots WHERE slot_id = :id')
                ->execute([':id' => $id]);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Slot deleted.',
        ]);
    }

    // -----------------------------------------------------------------
    // STUDENT BOOKINGS
    // -----------------------------------------------------------------

    /** GET /api/interviews/my-sessions — student. */
    public function mySessions(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);

        try {
            $stmt = Database::getConnection()->prepare(
                "SELECT m.interview_id, m.slot_id, m.student_id, m.job_category,
                        m.status, m.feedback_text, m.score, m.created_at, m.updated_at,
                        s.scheduled_at, s.interviewer_id,
                        u.full_name AS interviewer_name
                 FROM mock_interviews m
                 JOIN interview_slots s ON s.slot_id = m.slot_id
                 JOIN users u           ON u.user_id = s.interviewer_id
                 WHERE m.student_id = :uid
                 ORDER BY s.scheduled_at DESC"
            );
            $stmt->execute([':uid' => $userId]);
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

    /** POST /api/interviews/bookings — student. */
    public function bookSlot(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        $slotId      = isset($body['slot_id']) ? (int) $body['slot_id'] : 0;
        $jobCategory = trim((string) ($body['job_category'] ?? ''));

        $errors = [];
        if ($slotId <= 0) {
            $errors['slot_id'] = 'Please choose a slot.';
        }
        if ($jobCategory === '' || mb_strlen($jobCategory) > 100) {
            $errors['job_category'] = 'Job category is required (max 100 chars).';
        }
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Lock the slot row to prevent two students racing.
            $stmt = $pdo->prepare(
                'SELECT slot_id, is_booked, scheduled_at
                 FROM interview_slots
                 WHERE slot_id = :id
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->execute([':id' => $slotId]);
            $slot = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$slot) {
                $pdo->rollBack();
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Slot not found.',
                ]);
            }
            if ((int) $slot['is_booked'] === 1) {
                $pdo->rollBack();
                return Json::write($response, 409, [
                    'success' => false,
                    'message' => 'That slot has already been booked.',
                ]);
            }
            if (strtotime((string) $slot['scheduled_at']) < time()) {
                $pdo->rollBack();
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'You cannot book a slot in the past.',
                ]);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO mock_interviews (slot_id, student_id, job_category, status)
                 VALUES (:s, :u, :c, "pending")'
            );
            $stmt->execute([
                ':s' => $slotId,
                ':u' => $userId,
                ':c' => $jobCategory,
            ]);
            $bookingId = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'UPDATE interview_slots SET is_booked = 1 WHERE slot_id = :id'
            )->execute([':id' => $slotId]);

            $pdo->commit();

            $row = $this->fetchBookingRow($pdo, $bookingId);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Slot booked.',
            'data'    => $row,
        ]);
    }

    /** PUT /api/interviews/bookings/{id} — student. */
    public function updateBooking(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid booking id.',
            ]);
        }

        $jobCategory = isset($body['job_category']) ? trim((string) $body['job_category']) : null;
        $cancel      = isset($body['cancel']) && (bool) $body['cancel'];

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT m.interview_id, m.slot_id, m.student_id, m.status,
                        s.scheduled_at
                 FROM mock_interviews m
                 JOIN interview_slots s ON s.slot_id = m.slot_id
                 WHERE m.interview_id = :id
                 LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $pdo->rollBack();
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Booking not found.',
                ]);
            }
            if ((int) $row['student_id'] !== $userId) {
                $pdo->rollBack();
                return Json::write($response, 403, [
                    'success' => false,
                    'message' => 'You can only manage your own bookings.',
                ]);
            }
            if ($row['status'] !== 'pending') {
                $pdo->rollBack();
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Only pending bookings can be modified.',
                ]);
            }

            if ($cancel) {
                $pdo->prepare(
                    'UPDATE mock_interviews SET status = "cancelled" WHERE interview_id = :id'
                )->execute([':id' => $id]);
                // Free the slot up so other students can book it.
                $pdo->prepare(
                    'UPDATE interview_slots SET is_booked = 0 WHERE slot_id = :s'
                )->execute([':s' => (int) $row['slot_id']]);
            } else {
                if ($jobCategory === null || $jobCategory === '' || mb_strlen($jobCategory) > 100) {
                    $pdo->rollBack();
                    return Json::write($response, 400, [
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => ['job_category' => 'Job category is required (max 100 chars).'],
                    ]);
                }
                $pdo->prepare(
                    'UPDATE mock_interviews SET job_category = :c WHERE interview_id = :id'
                )->execute([':c' => $jobCategory, ':id' => $id]);
            }

            $pdo->commit();
            $updated = $this->fetchBookingRow($pdo, $id);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => $cancel ? 'Booking cancelled.' : 'Booking updated.',
            'data'    => $updated,
        ]);
    }

    // -----------------------------------------------------------------
    // ADMIN MANAGE + EVALUATE
    // -----------------------------------------------------------------

    /** GET /api/interviews/admin/manage — admin. */
    public function adminList(Request $request, Response $response): Response
    {
        try {
            $rows = Database::getConnection()
                ->query(
                    "SELECT m.interview_id, m.slot_id, m.student_id, m.job_category,
                            m.status, m.feedback_text, m.score, m.created_at, m.updated_at,
                            s.scheduled_at, s.interviewer_id,
                            u.full_name AS student_name,
                            iu.full_name AS interviewer_name
                     FROM mock_interviews m
                     JOIN interview_slots s ON s.slot_id = m.slot_id
                     JOIN users u           ON u.user_id = m.student_id
                     JOIN users iu          ON iu.user_id = s.interviewer_id
                     ORDER BY s.scheduled_at DESC"
                )
                ->fetchAll(PDO::FETCH_ASSOC);
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

    /** PUT /api/interviews/admin/evaluate/{id} — admin. */
    public function evaluate(Request $request, Response $response, array $args): Response
    {
        $id   = (int) ($args['id'] ?? 0);
        $body = $this->readJson($request);

        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid booking id.',
            ]);
        }

        $score    = isset($body['score']) && $body['score'] !== '' ? (int) $body['score'] : null;
        $feedback = isset($body['feedback_text']) ? trim((string) $body['feedback_text']) : '';
        $newStatus = isset($body['status']) ? (string) $body['status'] : 'completed';

        $errors = [];
        if ($score === null || $score < 0 || $score > 100) {
            $errors['score'] = 'Score must be an integer between 0 and 100.';
        }
        if ($feedback === '') {
            $errors['feedback_text'] = 'Feedback is required.';
        } elseif (mb_strlen($feedback) > 5000) {
            $errors['feedback_text'] = 'Feedback must be 5000 characters or fewer.';
        }
        if (!in_array($newStatus, ['completed', 'cancelled'], true)) {
            $errors['status'] = 'Status must be completed or cancelled.';
        }
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo  = Database::getConnection();
            $stmt = $pdo->prepare(
                'SELECT interview_id FROM mock_interviews WHERE interview_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetchColumn()) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Booking not found.',
                ]);
            }

            $stmt = $pdo->prepare(
                'UPDATE mock_interviews
                 SET score = :sc, feedback_text = :fb, status = :st
                 WHERE interview_id = :id'
            );
            $stmt->execute([
                ':sc' => $score,
                ':fb' => $feedback,
                ':st' => $newStatus,
                ':id' => $id,
            ]);

            $row = $this->fetchBookingRow($pdo, $id);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Booking evaluated.',
            'data'    => $row,
        ]);
    }

    // -----------------------------------------------------------------
    // helpers
    // -----------------------------------------------------------------

    private function fetchSlotRow(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT s.slot_id, s.interviewer_id, s.scheduled_at, s.is_booked,
                    s.created_at, u.full_name AS interviewer_name
             FROM interview_slots s
             JOIN users u ON u.user_id = s.interviewer_id
             WHERE s.slot_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['is_booked'] = (bool) $row['is_booked'];
        return $row;
    }

    private function fetchBookingRow(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT m.interview_id, m.slot_id, m.student_id, m.job_category,
                    m.status, m.feedback_text, m.score, m.created_at, m.updated_at,
                    s.scheduled_at, s.interviewer_id,
                    u.full_name AS student_name,
                    iu.full_name AS interviewer_name
             FROM mock_interviews m
             JOIN interview_slots s ON s.slot_id = m.slot_id
             JOIN users u           ON u.user_id = m.student_id
             JOIN users iu          ON iu.user_id = s.interviewer_id
             WHERE m.interview_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
