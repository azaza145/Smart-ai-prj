<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Router;
use App\Core\Middleware;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    DB::loadEnv($envPath);
}
DB::loadEnv(dirname(__DIR__) . '/env.example');

$router = new Router();

// Guest
$router->get('/login', [\App\Controllers\AuthController::class, 'showLogin'], [Middleware::guest()]);
$router->post('/login', [\App\Controllers\AuthController::class, 'login'], [Middleware::guest()]);
$router->get('/register', [\App\Controllers\AuthController::class, 'showRegister'], [Middleware::guest()]);
$router->post('/register', [\App\Controllers\AuthController::class, 'register'], [Middleware::guest()]);
$router->get('/forgot-password', [\App\Controllers\AuthController::class, 'showForgotPassword'], [Middleware::guest()]);
$router->post('/forgot-password', [\App\Controllers\AuthController::class, 'forgotPassword'], [Middleware::guest()]);
$router->get('/reset-password', [\App\Controllers\AuthController::class, 'showResetPassword'], [Middleware::guest()]);
$router->post('/reset-password', [\App\Controllers\AuthController::class, 'resetPassword'], [Middleware::guest()]);

// Auth required
$router->get('/logout', [\App\Controllers\AuthController::class, 'logout'], [Middleware::auth()]);
$router->post('/logout', [\App\Controllers\AuthController::class, 'logout'], [Middleware::auth()]);

// Admin
$router->get('/admin/stats', [\App\Controllers\AdminController::class, 'stats'], [Middleware::auth(), Middleware::admin()]);
$router->get('/admin/users', [\App\Controllers\AdminController::class, 'users'], [Middleware::auth(), Middleware::admin()]);
$router->post('/admin/users', [\App\Controllers\AdminController::class, 'createUser'], [Middleware::auth(), Middleware::admin()]);
$router->get('/admin/users/{id}', [\App\Controllers\AdminController::class, 'editUser'], [Middleware::auth(), Middleware::admin()]);
$router->post('/admin/users/{id}', [\App\Controllers\AdminController::class, 'editUser'], [Middleware::auth(), Middleware::admin()]);
$router->get('/admin/import-csv', [\App\Controllers\AdminController::class, 'showImportCsv'], [Middleware::auth(), Middleware::admin()]);
$router->post('/admin/import-csv', [\App\Controllers\AdminController::class, 'importCsv'], [Middleware::auth(), Middleware::admin()]);
$router->post('/admin/run-normalization', [\App\Controllers\AdminController::class, 'runNormalization'], [Middleware::auth(), Middleware::admin()]);
$router->get('/admin/jobs', [\App\Controllers\AdminController::class, 'jobs'], [Middleware::auth(), Middleware::admin()]);
$router->post('/admin/jobs', [\App\Controllers\AdminController::class, 'createJob'], [Middleware::auth(), Middleware::admin()]);
$router->get('/admin/jobs/{id}', [\App\Controllers\AdminController::class, 'editJob'], [Middleware::auth(), Middleware::admin()]);
$router->post('/admin/jobs/{id}', [\App\Controllers\AdminController::class, 'editJob'], [Middleware::auth(), Middleware::admin()]);
$router->post('/admin/jobs/{id}/delete', [\App\Controllers\AdminController::class, 'deleteJob'], [Middleware::auth(), Middleware::admin()]);
$router->post('/admin/jobs/{id}/recommend', [\App\Controllers\AdminController::class, 'recommendJob'], [Middleware::auth(), Middleware::admin()]);

// Recruiter (literal paths before /recruiter/jobs/{id} so "create" is not matched as id)
$router->get('/recruiter/recommendations', [\App\Controllers\RecruiterController::class, 'recommendations'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/candidates', [\App\Controllers\RecruiterController::class, 'candidates'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/candidates/{id}/cv/{cvId}', [\App\Controllers\RecruiterController::class, 'downloadCandidateCv'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/candidates/{id}/cv/{cvId}/images', [\App\Controllers\RecruiterController::class, 'viewCvImages'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/storage/cv_images/{cvId}/{filename}', [\App\Controllers\RecruiterController::class, 'serveCvImage'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/candidates/{id}', [\App\Controllers\RecruiterController::class, 'showCandidate'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/stats', [\App\Controllers\RecruiterController::class, 'stats'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/jobs', [\App\Controllers\RecruiterController::class, 'jobs'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/jobs/create', [\App\Controllers\RecruiterController::class, 'showCreateJob'], [Middleware::auth(), Middleware::recruiter()]);
$router->post('/recruiter/jobs', [\App\Controllers\RecruiterController::class, 'createJob'], [Middleware::auth(), Middleware::recruiter()]);
$router->post('/recruiter/jobs/{id}/duplicate', [\App\Controllers\RecruiterController::class, 'duplicateJob'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/jobs/{id}/edit', [\App\Controllers\RecruiterController::class, 'showEditJob'], [Middleware::auth(), Middleware::recruiter()]);
$router->post('/recruiter/jobs/{id}/edit', [\App\Controllers\RecruiterController::class, 'editJob'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/jobs/{id}/applications', [\App\Controllers\RecruiterController::class, 'jobApplications'], [Middleware::auth(), Middleware::recruiter()]);
$router->post('/recruiter/jobs/{jobId}/applications/{appId}/status', [\App\Controllers\RecruiterController::class, 'updateApplicationStatus'], [Middleware::auth(), Middleware::recruiter()]);
$router->post('/recruiter/jobs/{id}/recommend', [\App\Controllers\RecruiterController::class, 'recommend'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/jobs/{id}/results', [\App\Controllers\RecruiterController::class, 'results'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/jobs/{id}', [\App\Controllers\RecruiterController::class, 'showJob'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/jobs/{jobId}/candidates/{candidateId}', [\App\Controllers\RecruiterController::class, 'candidateDetail'], [Middleware::auth(), Middleware::recruiter()]);
$router->get('/recruiter/candidates/{id}/cv-pdf', [\App\Controllers\RecruiterController::class, 'downloadCandidateCvPdf'], [Middleware::auth(), Middleware::recruiter()]);
$router->post('/recruiter/candidates/{id}/fill-from-cv', [\App\Controllers\RecruiterController::class, 'fillCandidateFromCv'], [Middleware::auth(), Middleware::recruiter()]);

// Candidate
$router->get('/candidate/profile', [\App\Controllers\CandidateController::class, 'profile'], [Middleware::auth(), Middleware::candidate()]);
$router->post('/candidate/profile', [\App\Controllers\CandidateController::class, 'profile'], [Middleware::auth(), Middleware::candidate()]);
$router->post('/candidate/upload-cv', [\App\Controllers\CandidateController::class, 'uploadCv'], [Middleware::auth(), Middleware::candidate()]);
$router->post('/candidate/upload-document', [\App\Controllers\CandidateController::class, 'uploadDocument'], [Middleware::auth(), Middleware::candidate()]);
$router->get('/candidate/profile/documents/{id}/download', [\App\Controllers\CandidateController::class, 'downloadDocument'], [Middleware::auth(), Middleware::candidate()]);
$router->post('/candidate/profile/documents/{id}/delete', [\App\Controllers\CandidateController::class, 'deleteDocument'], [Middleware::auth(), Middleware::candidate()]);
$router->get('/candidate/jobs', [\App\Controllers\CandidateController::class, 'jobs'], [Middleware::auth(), Middleware::candidate()]);
$router->get('/candidate/jobs/{id}/apply', [\App\Controllers\CandidateController::class, 'showApply'], [Middleware::auth(), Middleware::candidate()]);
$router->post('/candidate/jobs/{id}/apply', [\App\Controllers\CandidateController::class, 'submitApply'], [Middleware::auth(), Middleware::candidate()]);
$router->get('/candidate/applications', [\App\Controllers\CandidateController::class, 'applications'], [Middleware::auth(), Middleware::candidate()]);
$router->get('/candidate/results', [\App\Controllers\CandidateController::class, 'results'], [Middleware::auth(), Middleware::candidate()]);
$router->get('/candidate/profile/generate-cv', [\App\Controllers\CandidateController::class, 'generateCvPdf'], [Middleware::auth(), Middleware::candidate()]);
$router->get('/candidate/profile/download-cv-pdf', [\App\Controllers\CandidateController::class, 'downloadCvPdf'], [Middleware::auth(), Middleware::candidate()]);
$router->get('/candidate/profile/download-cv-word', [\App\Controllers\CandidateController::class, 'downloadCvWord'], [Middleware::auth(), Middleware::candidate()]);

// Demo (no auth)
$router->get('/recruteia-demo', [\App\Controllers\DemoController::class, 'showDemo']);

// Home (landing when guest; redirect to dashboard when logged in)
$router->get('/', [\App\Controllers\HomeController::class, 'index']);

$router->dispatch();
