<?php
declare(strict_types=1);

/**
 * CareerBridge — Slim 4 application bootstrap.
 *
 * Loads .env, registers global CORS, and wires every route from the blueprint.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Modules\Admin\AdminController;
use App\Modules\Applications\ApplicationController;
use App\Modules\Auth\AuthController;
use App\Modules\Companies\CompanyController;
use App\Modules\Forums\ForumController;
use App\Modules\Interviews\InterviewController;
use App\Modules\Jobs\JobController;
use App\Modules\Profile\ProfileController;
use App\Support\Json;
use App\Middleware\CorsMiddleware;
use App\Middleware\JwtMiddleware;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// ---- Load environment ----------------------------------------------------
$envPath = __DIR__ . '/..';
if (file_exists($envPath . '/.env')) {
    Dotenv::createImmutable($envPath)->load();
} else {
    // Fall back to .env.example for fresh checkouts so the app still boots.
    Dotenv::createImmutable($envPath, '.env.example')->safeLoad();
}

// ---- Build app ------------------------------------------------------------
$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->add(new CorsMiddleware());

$displayErrors = ($_ENV['APP_DEBUG'] ?? '0') === '1';
$errorMiddleware = $app->addErrorMiddleware($displayErrors, true, true);
$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    \Throwable $exception,
    bool $displayErrorDetails
) use ($app): Response {
    $response = $app->getResponseFactory()->createResponse();
    $status = method_exists($exception, 'getCode') && $exception->getCode() >= 400 && $exception->getCode() < 600
        ? (int) $exception->getCode()
        : 500;
    return Json::write($response, $status, [
        'success' => false,
        'message' => $displayErrorDetails ? $exception->getMessage() : 'Server error.',
    ]);
});

// ---- Health check ---------------------------------------------------------
$app->get('/', function (Request $request, Response $response): Response {
    return Json::write($response, 200, [
        'success' => true,
        'message' => 'CareerBridge API is running.',
        'data'    => ['version' => '1.0.0'],
    ]);
});

// ---- AUTH (no JWT) --------------------------------------------------------
$app->post('/api/auth/register', [AuthController::class, 'register']);
$app->post('/api/auth/login',    [AuthController::class, 'login']);

// ---- JOBS -----------------------------------------------------------------
$app->get('/api/jobs',           [JobController::class, 'index']);
$app->get('/api/jobs/{id}',      [JobController::class, 'show']);

$app->post('/api/jobs',          [JobController::class, 'create'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));
$app->put('/api/jobs/{id}',      [JobController::class, 'update'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));
$app->delete('/api/jobs/{id}',   [JobController::class, 'delete'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));

// ---- APPLICATIONS ---------------------------------------------------------
$app->get('/api/applications',                  [ApplicationController::class, 'index'])
    ->add(new JwtMiddleware());
$app->post('/api/applications',                 [ApplicationController::class, 'create'])
    ->add(new JwtMiddleware('student'));
$app->put('/api/applications/{id}/status',      [ApplicationController::class, 'updateStatus'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));
$app->delete('/api/applications/{id}',          [ApplicationController::class, 'delete'])
    ->add(new JwtMiddleware('student'));

// ---- PROFILE --------------------------------------------------------------
$app->get('/api/profile', [ProfileController::class, 'show'])
    ->add(new JwtMiddleware('student'));
$app->put('/api/profile', [ProfileController::class, 'upsert'])
    ->add(new JwtMiddleware('student'));

// ---- COMPANIES ------------------------------------------------------------
$app->get('/api/companies', [CompanyController::class, 'index'])
    ->add(new JwtMiddleware());
$app->post('/api/companies', [CompanyController::class, 'create'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));
$app->put('/api/companies/{id}', [CompanyController::class, 'update'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));
$app->delete('/api/companies/{id}', [CompanyController::class, 'delete'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));

// ---- ADMIN ----------------------------------------------------------------
$app->get('/api/admin/users', [AdminController::class, 'users'])
    ->add(new JwtMiddleware('superadmin'));
$app->get('/api/admin/analytics', [AdminController::class, 'analytics'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));

// ---- FORUMS (M7 — Monika) -------------------------------------------------
// IMPORTANT: declare the static `/api/forums/comments/{id}` BEFORE the
// dynamic `/api/forums/{id}` so Slim doesn't capture the literal segment
// as the post id.
$app->delete('/api/forums/comments/{id}', [ForumController::class, 'deleteComment'])
    ->add(new JwtMiddleware()); // own (student) OR admin — enforced inside controller

$app->get('/api/forums', [ForumController::class, 'index'])
    ->add(new JwtMiddleware());
$app->get('/api/forums/{id}', [ForumController::class, 'show'])
    ->add(new JwtMiddleware());
$app->post('/api/forums', [ForumController::class, 'create'])
    ->add(new JwtMiddleware('student'));
$app->put('/api/forums/{id}', [ForumController::class, 'update'])
    ->add(new JwtMiddleware('student'));
$app->delete('/api/forums/{id}', [ForumController::class, 'delete'])
    ->add(new JwtMiddleware()); // own (student) OR admin — enforced inside controller
$app->post('/api/forums/{id}/like', [ForumController::class, 'toggleLike'])
    ->add(new JwtMiddleware());
$app->post('/api/forums/{id}/comments', [ForumController::class, 'createComment'])
    ->add(new JwtMiddleware('student'));

// ---- INTERVIEWS (M8 — Monika) ---------------------------------------------
// Slot management (admin)
$app->get('/api/interviews/slots', [InterviewController::class, 'listSlots'])
    ->add(new JwtMiddleware());
$app->post('/api/interviews/slots', [InterviewController::class, 'createSlot'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));
$app->delete('/api/interviews/slots/{id}', [InterviewController::class, 'deleteSlot'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));

// Student bookings
$app->get('/api/interviews/my-sessions', [InterviewController::class, 'mySessions'])
    ->add(new JwtMiddleware('student'));
$app->post('/api/interviews/bookings', [InterviewController::class, 'bookSlot'])
    ->add(new JwtMiddleware('student'));
$app->put('/api/interviews/bookings/{id}', [InterviewController::class, 'updateBooking'])
    ->add(new JwtMiddleware('student'));

// Admin oversight + evaluation
$app->get('/api/interviews/admin/manage', [InterviewController::class, 'adminList'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));
$app->put('/api/interviews/admin/evaluate/{id}', [InterviewController::class, 'evaluate'])
    ->add(new JwtMiddleware(['admin', 'superadmin']));

// ---- Catch-all OPTIONS for CORS preflight ---------------------------------
$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    return $response;
});

// ---- 404 fallback ---------------------------------------------------------
$app->map(['GET','POST','PUT','DELETE','PATCH'], '/{routes:.+}',
    function (Request $request, Response $response): Response {
        return Json::write($response, 404, [
            'success' => false,
            'message' => 'Route not found.',
        ]);
    }
);

$app->run();
