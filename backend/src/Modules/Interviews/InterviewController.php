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
 * Module 8 — Mock Interview & Technical Prep Scheduler
 *
 * Admin: create / delete availability slots, view all bookings, evaluate sessions.
 * Student: browse open slots, book a slot, edit/cancel own bookings, view own history.
 */
final class InterviewController
{
    // ===========================================================
    // SLOTS
    // ===========================================================

    /** GET /api/interviews/slots — list every slot with the interviewer's name. */
    public function listSlots(Request $request, Response $response): Response
    {
        try {
            $stmt = Database::getConnection()->query(
                'SELECT s.slot_id, s.interviewer_id, s.scheduled_at, s.is_booked,
                        u.full_name AS interviewer_name
                 FROM interview_slots s
                 LEFT JOIN users u ON u.user_id = s.interviewer_id
                 ORDER BY s.scheduled_at ASC'
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

    /** POST /api/interviews/slots — admin creates a new availability window. */
    public function createSlot(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        $scheduledAt = trim((string) ($body['scheduled_at'] ?? ''));
        if ($scheduledAt === '') {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['scheduled_at' => 'Date and time are required.'],
            ]);
        }

        // datetime-local sends "2026-06-01T14:30" — normalise for MySQL.
        $normalised = str_replace('T', ' ', $scheduledAt);
        $ts = strtotime($normalised);
        if ($ts === false) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid date/time format.',
                'errors'  => ['scheduled_at' => 'Use a valid date and time.'],
            ]);
        }
        if ($ts <= time()) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Slot must be in the future.',
                'errors'  => ['scheduled_at' => 'Slot must be in the future.'],
            ]);
        }

        try {
            $pdo  = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO interview_slots (interviewer_id, scheduled_at, is_booked)
                 VALUES (:i, :s, FALSE)'
            );
            $stmt->execute([
                ':i' => $userId,
                ':s' => date('Y-m-d H:i:s', $ts),
            ]);
            $id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'SELECT s.slot_id, s.interviewer_id, s.scheduled_at, s.is_booked,
                        u.full_name AS interviewer_name
                 FROM interview_slots s
                 LEFT JOIN users u ON u.user_id = s.interviewer_id
                 WHERE s.slot_id = :id'
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
            'message' => 'Slot created.',
            'data'    => $row,
        ]);
    }

    /** DELETE /api/interviews/slots/{id} — admin deletes an unbooked slot. */
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
            $pdo = Database::getConnection();

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
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Cannot delete a slot that is already booked.',
                ]);
            }

            $stmt = $pdo->prepare('DELETE FROM interview_slots WHERE slot_id = :id');
            $stmt->execute([':id' => $id]);
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

    // ===========================================================
    // BOOKINGS — Student
    // ===========================================================

    /** GET /api/interviews/mysessions — student's own bookings with slot info. */
    public function mySessions(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);

        try {
            $stmt = Database::getConnection()->prepare(
                'SELECT m.interview_id, m.slot_id, m.student_id, m.job_category,
                        m.status, m.feedback_text, m.score,
                        s.scheduled_at,
                        u.full_name AS interviewer_name
                 FROM mock_interviews m
                 JOIN interview_slots s ON s.slot_id = m.slot_id
                 LEFT JOIN users u ON u.user_id = s.interviewer_id
                 WHERE m.student_id = :u
                 ORDER BY s.scheduled_at DESC'
            );
            $stmt->execute([':u' => $userId]);
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

    /** POST /api/interviews/bookings — student books an open slot. */
    public function createBooking(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        $slotId      = (int) ($body['slot_id'] ?? 0);
        $jobCategory = trim((string) ($body['job_category'] ?? ''));

        $errors = [];
        if ($slotId <= 0)            $errors['slot_id']      = 'A valid slot is required.';
        if ($jobCategory === '')     $errors['job_category'] = 'Job category is required.';
        if (mb_strlen($jobCategory) > 100) {
            $errors['job_category'] = 'Job category must be 100 characters or fewer.';
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

            $stmt = $pdo->prepare(
                'SELECT is_booked FROM interview_slots WHERE slot_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $slotId]);
            $slot = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$slot) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Slot not found.',
                ]);
            }
            if ((int) $slot['is_booked'] === 1) {
                return Json::write($response, 409, [
                    'success' => false,
                    'message' => 'This slot is already booked.',
                ]);
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO mock_interviews
                   (slot_id, student_id, job_category, status)
                 VALUES (:s, :u, :c, "pending")'
            );
            $stmt->execute([
                ':s' => $slotId,
                ':u' => $userId,
                ':c' => $jobCategory,
            ]);
            $id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'UPDATE interview_slots SET is_booked = TRUE WHERE slot_id = :id'
            );
            $stmt->execute([':id' => $slotId]);

            $pdo->commit();

            $stmt = $pdo->prepare(
                'SELECT m.interview_id, m.slot_id, m.student_id, m.job_category,
                        m.status, m.feedback_text, m.score,
                        s.scheduled_at,
                        u.full_name AS interviewer_name
                 FROM mock_interviews m
                 JOIN interview_slots s ON s.slot_id = m.slot_id
                 LEFT JOIN users u ON u.user_id = s.interviewer_id
                 WHERE m.interview_id = :id'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (Database::getConnection()->inTransaction()) {
                Database::getConnection()->rollBack();
            }
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Booking created.',
            'data'    => $row,
        ]);
    }

    /** PUT /api/interviews/bookings/{id} — student updates own pending booking. */
    public function updateBooking(Request $request, Response $response, array $args): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);

        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid booking id.',
            ]);
        }

        $body        = $this->readJson($request);
        $newCategory = array_key_exists('job_category', $body)
            ? trim((string) $body['job_category']) : null;
        $newStatus   = array_key_exists('status', $body)
            ? trim((string) $body['status']) : null;

        if ($newCategory === null && $newStatus === null) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Nothing to update.',
            ]);
        }

        // Students may only set status to "cancelled" themselves.
        if ($newStatus !== null && $newStatus !== 'cancelled') {
            return Json::write($response, 403, [
                'success' => false,
                'message' => 'Students may only cancel a booking through this endpoint.',
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare(
                'SELECT interview_id, slot_id, student_id, status
                 FROM mock_interviews WHERE interview_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Booking not found.',
                ]);
            }
            if ((int) $row['student_id'] !== $userId) {
                return Json::write($response, 403, [
                    'success' => false,
                    'message' => 'You can only edit your own bookings.',
                ]);
            }
            if ($row['status'] !== 'pending') {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Only pending bookings can be modified.',
                ]);
            }

            $pdo->beginTransaction();

            if ($newCategory !== null && $newCategory !== '') {
                $stmt = $pdo->prepare(
                    'UPDATE mock_interviews SET job_category = :c WHERE interview_id = :id'
                );
                $stmt->execute([':c' => $newCategory, ':id' => $id]);
            }

            if ($newStatus === 'cancelled') {
                $stmt = $pdo->prepare(
                    'UPDATE mock_interviews SET status = "cancelled" WHERE interview_id = :id'
                );
                $stmt->execute([':id' => $id]);

                // Free the slot up again.
                $stmt = $pdo->prepare(
                    'UPDATE interview_slots SET is_booked = FALSE WHERE slot_id = :s'
                );
                $stmt->execute([':s' => (int) $row['slot_id']]);
            }

            $pdo->commit();

            $stmt = $pdo->prepare(
                'SELECT m.interview_id, m.slot_id, m.student_id, m.job_category,
                        m.status, m.feedback_text, m.score,
                        s.scheduled_at,
                        u.full_name AS interviewer_name
                 FROM mock_interviews m
                 JOIN interview_slots s ON s.slot_id = m.slot_id
                 LEFT JOIN users u ON u.user_id = s.interviewer_id
                 WHERE m.interview_id = :id'
            );
            $stmt->execute([':id' => $id]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (Database::getConnection()->inTransaction()) {
                Database::getConnection()->rollBack();
            }
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Booking updated.',
            'data'    => $updated,
        ]);
    }

    // ===========================================================
    // ADMIN
    // ===========================================================

    /** GET /api/interviews/admin/manage — admin sees every booking. */
    public function adminManage(Request $request, Response $response): Response
    {
        try {
            $stmt = Database::getConnection()->query(
                'SELECT m.interview_id, m.slot_id, m.student_id, m.job_category,
                        m.status, m.feedback_text, m.score,
                        s.scheduled_at,
                        st.full_name AS student_name,
                        iv.full_name AS interviewer_name
                 FROM mock_interviews m
                 JOIN interview_slots s ON s.slot_id = m.slot_id
                 LEFT JOIN users st ON st.user_id = m.student_id
                 LEFT JOIN users iv ON iv.user_id = s.interviewer_id
                 ORDER BY s.scheduled_at DESC'
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

    /** PUT /api/interviews/admin/evaluate/{id} — admin submits score + feedback. */
    public function adminEvaluate(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid booking id.',
            ]);
        }

        $body         = $this->readJson($request);
        $score        = $body['score'] ?? null;
        $feedbackText = trim((string) ($body['feedback_text'] ?? ''));

        $errors = [];
        if ($score === null || $score === '' || !is_numeric($score)) {
            $errors['score'] = 'Score is required.';
        } else {
            $score = (int) $score;
            if ($score < 0 || $score > 100) {
                $errors['score'] = 'Score must be between 0 and 100.';
            }
        }
        if ($feedbackText === '') {
            $errors['feedback_text'] = 'Feedback is required.';
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

            $stmt = $pdo->prepare(
                'SELECT interview_id, status FROM mock_interviews WHERE interview_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Booking not found.',
                ]);
            }
            if ($row['status'] === 'cancelled') {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Cannot evaluate a cancelled booking.',
                ]);
            }

            $stmt = $pdo->prepare(
                'UPDATE mock_interviews
                    SET score = :sc, feedback_text = :ft, status = "completed"
                  WHERE interview_id = :id'
            );
            $stmt->execute([
                ':sc' => $score,
                ':ft' => $feedbackText,
                ':id' => $id,
            ]);

            $stmt = $pdo->prepare(
                'SELECT m.interview_id, m.slot_id, m.student_id, m.job_category,
                        m.status, m.feedback_text, m.score,
                        s.scheduled_at,
                        st.full_name AS student_name,
                        iv.full_name AS interviewer_name
                 FROM mock_interviews m
                 JOIN interview_slots s ON s.slot_id = m.slot_id
                 LEFT JOIN users st ON st.user_id = m.student_id
                 LEFT JOIN users iv ON iv.user_id = s.interviewer_id
                 WHERE m.interview_id = :id'
            );
            $stmt->execute([':id' => $id]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Evaluation submitted.',
            'data'    => $updated,
        ]);
    }

    // ===========================================================
    // Helpers
    // ===========================================================

    private function readJson(Request $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '') return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
