<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Validator;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\ImportLog;
use App\Models\PipelineLog;
use App\Services\CsvImporter;
use App\Services\NormalizationService;
use App\Services\RecommendationService;

class AdminController
{
    public function stats(): void
    {
        $stats = [
            'candidates' => Candidate::count(),
            'jobs' => Job::count(),
            'users' => User::count(),
            'last_import' => ImportLog::last(),
            'last_normalization' => PipelineLog::lastNormalization(),
            'last_recommendation' => PipelineLog::lastRecommendation(),
        ];
        $villes = Candidate::getDistinctVilles();
        $villeCounts = [];
        $pdo = \App\Core\DB::getInstance();
        $stmt = $pdo->query("SELECT ville, COUNT(*) as cnt FROM candidates WHERE ville IS NOT NULL AND ville != '' GROUP BY ville ORDER BY cnt DESC LIMIT 15");
        while ($row = $stmt->fetch()) {
            $villeCounts[$row['ville']] = (int) $row['cnt'];
        }
        $skillsRaw = $pdo->query("SELECT competences_techniques_raw FROM candidates WHERE competences_techniques_raw IS NOT NULL AND competences_techniques_raw != '' LIMIT 2000")->fetchAll(\PDO::FETCH_COLUMN);
        $skillFreq = [];
        foreach ($skillsRaw as $raw) {
            $parts = preg_split('/[\s,;|\/]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parts as $s) {
                $s = trim($s, ".\t\n\r\0\x0B");
                if (strlen($s) > 1) {
                    $skillFreq[$s] = ($skillFreq[$s] ?? 0) + 1;
                }
            }
        }
        arsort($skillFreq);
        $topSkills = array_slice($skillFreq, 0, 20, true);
        $users = array_slice(User::all(), 0, 10);
        $activities = $this->buildActivityFeed($stats);
        $this->layout('admin/stats', [
            'title' => 'Tableau de bord',
            'stats' => $stats,
            'villeCounts' => $villeCounts,
            'topSkills' => $topSkills,
            'users' => $users,
            'activities' => $activities,
        ]);
    }

    private function buildActivityFeed(array $stats): array
    {
        $activities = [];
        if (!empty($stats['last_import'])) {
            $d = $stats['last_import'];
            $ts = strtotime($d['started_at'] ?? '');
            $activities[] = [
                'icon' => '📄',
                'bg' => 'var(--ca-l)',
                'msg' => 'Import CSV : ' . ($d['rows_processed'] ?? 0) . ' traités, ' . ($d['rows_inserted'] ?? 0) . ' insérés',
                'time' => $this->timeAgo($d['started_at'] ?? ''),
                'sort_ts' => $ts ?: 0,
            ];
        }
        if (!empty($stats['last_normalization'])) {
            $d = $stats['last_normalization'];
            $ts = strtotime($d['started_at'] ?? '');
            $activities[] = [
                'icon' => '⚙️',
                'bg' => 'var(--ad-l)',
                'msg' => 'Normalisation : ' . ($d['status'] ?? 'terminée'),
                'time' => $this->timeAgo($d['started_at'] ?? ''),
                'sort_ts' => $ts ?: 0,
            ];
        }
        if (!empty($stats['last_recommendation'])) {
            $d = $stats['last_recommendation'];
            $ts = strtotime($d['started_at'] ?? '');
            $activities[] = [
                'icon' => '🧠',
                'bg' => 'var(--re-l)',
                'msg' => 'Analyse IA relancée',
                'time' => $this->timeAgo($d['started_at'] ?? ''),
                'sort_ts' => $ts ?: 0,
            ];
        }
        usort($activities, function ($a, $b) {
            return ($b['sort_ts'] ?? 0) <=> ($a['sort_ts'] ?? 0);
        });
        return array_slice($activities, 0, 8);
    }

    private function timeAgo(string $datetime): string
    {
        $ts = strtotime($datetime);
        if (!$ts) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'À l\'instant';
        }
        if ($diff < 3600) {
            return 'Il y a ' . (int) floor($diff / 60) . ' min';
        }
        if ($diff < 86400) {
            return 'Il y a ' . (int) floor($diff / 3600) . ' h';
        }
        if ($diff < 604800) {
            return 'Il y a ' . (int) floor($diff / 86400) . ' j';
        }
        return date('d/m/Y H:i', $ts);
    }

    public function users(): void
    {
        $users = User::all();
        $this->layout('admin/users', ['title' => 'Users', 'users' => $users]);
    }

    public function createUser(): void
    {
        if (!Csrf::verify()) {
            $this->flash('Invalid request.', 'danger');
            header('Location: /admin/users');
            exit;
        }
        $v = new Validator($_POST);
        $v->required('name', 'email', 'password', 'role')->email('email')->min('password', 6)->unique('email', 'users', 'email');
        if ($v->fails()) {
            $_SESSION['form_errors'] = $v->errors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/users');
            return;
        }
        User::create([
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'role' => $_POST['role'],
        ]);
        $this->flash('User created.', 'success');
        header('Location: /admin/users');
        exit;
    }

    public function editUser(int $id): void
    {
        $user = User::find($id);
        if (!$user) {
            $this->redirect404();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify()) {
                $this->flash('Invalid request.', 'danger');
                header('Location: /admin/users');
                exit;
            }
            $v = new Validator($_POST);
            $v->required('name', 'email', 'role')->email('email')->unique('email', 'users', 'email', $id);
            if (!empty($_POST['password'])) {
                $v->min('password', 6);
            }
            if ($v->fails()) {
                $this->layout('admin/user_edit', ['title' => 'Edit User', 'user' => $user, 'errors' => $v->errors(), 'old' => $_POST]);
                return;
            }
            $data = ['name' => $_POST['name'], 'email' => $_POST['email'], 'role' => $_POST['role'], 'status' => $_POST['status'] ?? 'active'];
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            User::update($id, $data);
            $this->flash('User updated.', 'success');
            header('Location: /admin/users');
            exit;
        }
        $this->layout('admin/user_edit', ['title' => 'Edit User', 'user' => $user]);
    }

    public function importCsv(): void
    {
        $defaultPath = $_ENV['CSV_DATASET_PATH'] ?? (dirname(__DIR__, 2) . '/dataset_cvs_5000.csv');
        $csvPath = null;
        if (!empty($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $csvPath = $_FILES['csv_file']['tmp_name'];
        }
        if ($csvPath === null && is_file($defaultPath)) {
            $csvPath = $defaultPath;
        }
        if ($csvPath === null || !is_readable($csvPath)) {
            $this->flash('Aucun fichier CSV : uploadez un fichier ou configurez le chemin par défaut.', 'danger');
            header('Location: /admin/import-csv');
            exit;
        }
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /admin/import-csv');
            exit;
        }
        $logPath = is_uploaded_file($csvPath) ? ('upload:' . ($_FILES['csv_file']['name'] ?? 'file.csv')) : $csvPath;
        try {
            $importer = new CsvImporter();
            if (is_file(dirname(__DIR__, 2) . '/python/import_csv_to_mysql.py')) {
                $logId = ImportLog::create($logPath);
                try {
                    $result = $importer->importViaPython($csvPath);
                    ImportLog::complete(
                        $logId,
                        $result['rows_processed'] ?? 0,
                        $result['rows_inserted'] ?? 0,
                        $result['rows_updated'] ?? 0,
                        $result['rows_failed'] ?? 0,
                        $result['error_log'] ?? ''
                    );
                    $this->flash(sprintf('Import terminé. Traités : %d, Insérés : %d, Mis à jour : %d, Échecs : %d', $result['rows_processed'] ?? 0, $result['rows_inserted'] ?? 0, $result['rows_updated'] ?? 0, $result['rows_failed'] ?? 0), 'success');
                } catch (\Throwable $e) {
                    ImportLog::fail($logId, $e->getMessage());
                    throw $e;
                }
            } else {
                $result = $importer->importFromPath($csvPath);
                $this->flash(sprintf('Import terminé. Traités : %d, Insérés : %d, Mis à jour : %d, Échecs : %d', $result['rows_processed'], $result['rows_inserted'], $result['rows_updated'], $result['rows_failed']), 'success');
            }
        } catch (\Throwable $e) {
            $this->flash('Échec import : ' . $e->getMessage(), 'danger');
        }
        header('Location: /admin/import-csv');
        exit;
    }

    public function showImportCsv(): void
    {
        $last = ImportLog::last();
        $csvPath = $_ENV['CSV_DATASET_PATH'] ?? (dirname(__DIR__, 2) . '/dataset_cvs_5000.csv');
        $csvExists = is_file($csvPath);
        $this->layout('admin/import_csv', ['title' => 'Import CSV', 'lastImport' => $last, 'csvPath' => $csvPath, 'csvExists' => $csvExists]);
    }

    public function runNormalization(): void
    {
        if (!Csrf::verify()) {
            $this->flash('Invalid request.', 'danger');
            header('Location: /admin/stats');
            exit;
        }
        try {
            $svc = new NormalizationService();
            $svc->runNormalization();
            $this->flash('Normalization completed.', 'success');
        } catch (\Throwable $e) {
            $this->flash('Normalization failed: ' . $e->getMessage(), 'danger');
        }
        header('Location: /admin/stats');
        exit;
    }

    public function jobs(): void
    {
        $jobs = Job::all();
        $this->layout('admin/jobs', ['title' => 'Jobs', 'jobs' => $jobs]);
    }

    public function createJob(): void
    {
        if (!Csrf::verify()) {
            $this->flash('Invalid request.', 'danger');
            header('Location: /admin/jobs');
            exit;
        }
        $v = new Validator($_POST);
        $v->required('title');
        if ($v->fails()) {
            $this->flash(implode(' ', $v->errors()), 'danger');
            header('Location: /admin/jobs');
            return;
        }
        Job::create([
            'title' => $_POST['title'],
            'department' => $_POST['department'] ?? null,
            'description' => $_POST['description'] ?? null,
            'requirements' => $_POST['requirements'] ?? null,
            'created_by' => Auth::id(),
        ]);
        $this->flash('Job created.', 'success');
        header('Location: /admin/jobs');
        exit;
    }

    public function editJob(int $id): void
    {
        $job = Job::find($id);
        if (!$job) {
            $this->redirect404();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify()) {
                header('Location: /admin/jobs');
                exit;
            }
            $v = new Validator($_POST);
            $v->required('title');
            if (!$v->fails()) {
                Job::update($id, [
                    'title' => $_POST['title'],
                    'department' => $_POST['department'] ?? null,
                    'description' => $_POST['description'] ?? null,
                    'requirements' => $_POST['requirements'] ?? null,
                ]);
                $this->flash('Job updated.', 'success');
            }
            header('Location: /admin/jobs');
            exit;
        }
        $this->layout('admin/job_edit', ['title' => 'Edit Job', 'job' => $job]);
    }

    public function deleteJob(int $id): void
    {
        if (!Csrf::verify()) {
            header('Location: /admin/jobs');
            exit;
        }
        Job::delete($id);
        $this->flash('Job deleted.', 'success');
        header('Location: /admin/jobs');
        exit;
    }

    public function recommendJob(int $id): void
    {
        if (!Csrf::verify()) {
            $this->flash('Invalid request.', 'danger');
            header('Location: /admin/jobs');
            exit;
        }
        try {
            $svc = new RecommendationService();
            $svc->runForJob($id);
            $this->flash('Recommendations generated.', 'success');
        } catch (\Throwable $e) {
            $this->flash('Recommendation failed: ' . $e->getMessage(), 'danger');
        }
        header('Location: /admin/jobs');
        exit;
    }

    private function layout(string $view, array $data = []): void
    {
        extract($data);
        $user = Auth::user();
        $sidebar = 'admin';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    private function flash(string $msg, string $type): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = ['message' => $msg, 'type' => $type];
    }

    private function redirect404(): void
    {
        http_response_code(404);
        echo '404 Not Found';
        exit;
    }
}
