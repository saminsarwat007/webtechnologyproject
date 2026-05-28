<?php
declare(strict_types=1);

namespace App\Modules\Profile;

use App\Config\Database;
use App\Support\Json;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 5 — Reporting & Analytics (Owner: Mariam)
 *
 * ProfileController — student-only profile read/upsert.
 * Provides the data surface that the student dashboard reports on.
 */
final class ProfileController
{
    public function show(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);

        try {
            $stmt = Database::getConnection()->prepare(
                'SELECT sp.profile_id, sp.user_id, sp.matric_no, sp.programme,
                        sp.cgpa, sp.skills, sp.resume_text,
                        u.full_name, u.email
                 FROM student_profiles sp
                 JOIN users u ON u.user_id = sp.user_id
                 WHERE sp.user_id = :uid LIMIT 1'
            );
            $stmt->execute([':uid' => $userId]);
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
                'message' => 'Profile not yet set up.',
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'data'    => $row,
        ]);
    }

    public function upsert(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        $errors    = [];
        $matricNo  = trim((string) ($body['matric_no'] ?? ''));
        $programme = trim((string) ($body['programme'] ?? ''));
        $cgpaRaw   = $body['cgpa'] ?? null;
        $skills    = trim((string) ($body['skills'] ?? ''));
        $resume    = trim((string) ($body['resume_text'] ?? ''));

        if ($matricNo === '' || mb_strlen($matricNo) > 20) {
            $errors['matric_no'] = 'Matric number is required (max 20 chars).';
        }
        if ($programme === '' || mb_strlen($programme) > 100) {
            $errors['programme'] = 'Programme is required (max 100 chars).';
        }

        $cgpa = null;
        if ($cgpaRaw !== null && $cgpaRaw !== '') {
            if (!is_numeric($cgpaRaw)) {
                $errors['cgpa'] = 'CGPA must be a number.';
            } else {
                $cgpa = (float) $cgpaRaw;
                if ($cgpa < 0 || $cgpa > 4) {
                    $errors['cgpa'] = 'CGPA must be between 0.00 and 4.00.';
                }
            }
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
                'SELECT profile_id FROM student_profiles
                 WHERE matric_no = :mn AND user_id <> :uid LIMIT 1'
            );
            $stmt->execute([':mn' => $matricNo, ':uid' => $userId]);
            if ($stmt->fetch()) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['matric_no' => 'Matric number is already taken.'],
                ]);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO student_profiles (user_id, matric_no, programme, cgpa, skills, resume_text)
                 VALUES (:uid, :mn, :pr, :cg, :sk, :rt)
                 ON DUPLICATE KEY UPDATE
                    matric_no   = VALUES(matric_no),
                    programme   = VALUES(programme),
                    cgpa        = VALUES(cgpa),
                    skills      = VALUES(skills),
                    resume_text = VALUES(resume_text)'
            );
            $stmt->execute([
                ':uid' => $userId,
                ':mn'  => $matricNo,
                ':pr'  => $programme,
                ':cg'  => $cgpa,
                ':sk'  => $skills !== '' ? $skills : null,
                ':rt'  => $resume !== '' ? $resume : null,
            ]);

            $stmt = $pdo->prepare(
                'SELECT sp.profile_id, sp.user_id, sp.matric_no, sp.programme,
                        sp.cgpa, sp.skills, sp.resume_text,
                        u.full_name, u.email
                 FROM student_profiles sp
                 JOIN users u ON u.user_id = sp.user_id
                 WHERE sp.user_id = :uid LIMIT 1'
            );
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Profile saved.',
            'data'    => $row,
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
