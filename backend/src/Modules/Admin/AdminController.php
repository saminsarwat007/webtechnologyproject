<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Config\Database;
use App\Support\Json;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Modules 5 & 6 — Reporting & Analytics + System Administration.
 *
 * - analytics() : Module 5 dashboard stats   (Owner: Mariam)
 * - users()     : Module 6 superadmin user directory (Owner: Areeb)
 */
final class AdminController
{
    public function users(Request $request, Response $response): Response
    {
        try {
            $stmt = Database::getConnection()->query(
                'SELECT user_id, full_name, email, role, created_at
                 FROM users
                 ORDER BY created_at DESC'
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

    public function analytics(Request $request, Response $response): Response
    {
        try {
            $pdo = Database::getConnection();

            $totalJobs        = (int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
            $activeJobs       = (int) $pdo->query('SELECT COUNT(*) FROM jobs WHERE is_active = 1')->fetchColumn();
            $totalApps        = (int) $pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();
            $pendingCount     = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
            $reviewedCount    = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'reviewed'")->fetchColumn();
            $acceptedCount    = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'accepted'")->fetchColumn();
            $rejectedCount    = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'")->fetchColumn();
            $appsThisWeek     = (int) $pdo->query(
                'SELECT COUNT(*) FROM applications
                 WHERE YEARWEEK(applied_at, 1) = YEARWEEK(CURDATE(), 1)'
            )->fetchColumn();
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'data'    => [
                'total_jobs'              => $totalJobs,
                'active_jobs'             => $activeJobs,
                'total_applications'      => $totalApps,
                'pending_count'           => $pendingCount,
                'reviewed_count'          => $reviewedCount,
                'accepted_count'          => $acceptedCount,
                'rejected_count'          => $rejectedCount,
                'applications_this_week'  => $appsThisWeek,
            ],
        ]);
    }
}
