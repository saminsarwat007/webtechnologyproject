<?php
declare(strict_types=1);

namespace App\Modules\Auth;

use App\Config\Database;
use App\Support\Json;
use Firebase\JWT\JWT;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 1 — User Authentication & Access Control (Owner: Areeb)
 *
 * AuthController — register & login.
 *
 * Both endpoints return a JWT signed with HS256 valid for 24h.
 * Payload claims: user_id, email, role, exp.
 */
final class AuthController
{
    public function register(Request $request, Response $response): Response
    {
        $body = $this->readJson($request);

        $errors    = [];
        $fullName  = $this->str($body['full_name']  ?? '');
        $email     = strtolower($this->str($body['email'] ?? ''));
        $password  = (string) ($body['password'] ?? '');
        $role      = $this->str($body['role'] ?? 'student');

        if ($fullName === '' || mb_strlen($fullName) > 100) {
            $errors['full_name'] = 'Full name is required (max 100 chars).';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors['email'] = 'A valid email is required.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if (!in_array($role, ['student', 'admin'], true)) {
            $errors['role'] = 'Role must be student or admin.';
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

            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['email' => 'Email is already registered.'],
                ]);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role)
                 VALUES (:fn, :em, :pw, :ro)'
            );
            $stmt->execute([
                ':fn' => $fullName,
                ':em' => $email,
                ':pw' => $hash,
                ':ro' => $role,
            ]);
            $userId = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        $user = [
            'user_id'   => $userId,
            'full_name' => $fullName,
            'email'     => $email,
            'role'      => $role,
        ];
        $token = $this->issueToken($user);

        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Account created.',
            'data'    => ['token' => $token, 'user' => $user],
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        $body = $this->readJson($request);

        $errors   = [];
        $email    = strtolower($this->str($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        if ($password === '') {
            $errors['password'] = 'Password is required.';
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
                'SELECT user_id, full_name, email, password_hash, role
                 FROM users WHERE email = :em LIMIT 1'
            );
            $stmt->execute([':em' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return Json::write($response, 401, [
                'success' => false,
                'message' => 'Invalid email or password.',
            ]);
        }

        $user = [
            'user_id'   => (int) $row['user_id'],
            'full_name' => $row['full_name'],
            'email'     => $row['email'],
            'role'      => $row['role'],
        ];
        $token = $this->issueToken($user);

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Login successful.',
            'data'    => ['token' => $token, 'user' => $user],
        ]);
    }

    /** @param array{user_id:int,email:string,role:string,full_name:string} $user */
    private function issueToken(array $user): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? '';
        $now    = time();
        $payload = [
            'user_id'   => $user['user_id'],
            'email'     => $user['email'],
            'role'      => $user['role'],
            'full_name' => $user['full_name'],
            'iat'       => $now,
            'exp'       => $now + 86400, // 24h
        ];
        return JWT::encode($payload, $secret, 'HS256');
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

    private function str($v): string
    {
        return trim((string) $v);
    }
}
