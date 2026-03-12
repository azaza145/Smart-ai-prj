<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Validator;
use App\Models\Application;
use App\Models\Job;
use App\Models\Recommendation;
use App\Models\Candidate;
use App\Models\CandidateProfile;
use App\Services\RecommendationService;

class RecruiterController
{
    private const TYPE_CONTRAT_OPTIONS = ['CDI', 'CDD', 'Stage', 'Freelance', 'Alternance', 'Intérim', 'Contrat pro', 'Apprentissage', 'Saisonnier', 'Portage', 'Autre'];

    private static function jobTemplates(): array
    {
        return [
            'developpeur' => ['title' => 'Développeur Full Stack', 'skills' => 'PHP, JavaScript, MySQL, HTML/CSS', 'type_contrat' => 'CDI'],
            'data' => ['title' => 'Data Analyst', 'skills' => 'SQL, Python, Excel, Visualisation', 'type_contrat' => 'CDI'],
            'commercial' => ['title' => 'Commercial / Business Developer', 'skills' => 'Vente, Négociation, CRM', 'type_contrat' => 'CDI'],
            'marketing' => ['title' => 'Chef de projet marketing', 'skills' => 'Marketing digital, SEO, Réseaux sociaux', 'type_contrat' => 'CDI'],
            'stage' => ['title' => 'Stagiaire Développement', 'skills' => 'PHP ou Python, Bases de données', 'type_contrat' => 'Stage'],
        ];
    }

    public function recommendations(): void
    {
        $jobs = Job::all();
        $appCounts = [];
        foreach ($jobs as $j) {
            $appCounts[(int)$j['id']] = Application::countByJob((int)$j['id']);
        }
        $lastRuns = [];
        foreach ($jobs as $j) {
            $lastRuns[(int)$j['id']] = \App\Models\PipelineLog::lastRecommendation((int)$j['id']);
        }
        $this->layout('recruiter/recommendations', [
            'title' => 'Recommandations IA',
            'jobs' => $jobs,
            'appCounts' => $appCounts,
            'lastRuns' => $lastRuns,
            'hero_title' => 'Recommandations <em style="color:var(--re);">IA</em>',
            'hero_sub' => 'Lancez l\'analyse et consultez le classement par poste',
        ]);
    }

    public function jobs(): void
    {
        $filters = [
            'title' => trim($_GET['q'] ?? ''),
            'skills' => trim($_GET['skills'] ?? ''),
            'type_contrat' => trim($_GET['type_contrat'] ?? ''),
        ];
        $jobs = Job::listWithFilters($filters);
        $appCounts = [];
        foreach ($jobs as $j) {
            $appCounts[(int)$j['id']] = Application::countByJob((int)$j['id']);
        }
        $typeContratList = array_merge(self::TYPE_CONTRAT_OPTIONS, Job::getDistinctTypeContrat());
        $typeContratList = array_values(array_unique($typeContratList));
        $this->layout('recruiter/jobs', [
            'title' => 'Postes',
            'jobs' => $jobs,
            'appCounts' => $appCounts,
            'filters' => $filters,
            'typeContratOptions' => $typeContratList,
        ]);
    }

    public function showCreateJob(): void
    {
        $templateKey = trim($_GET['template'] ?? '');
        $templates = self::jobTemplates();
        $prefill = null;
        if ($templateKey !== '' && isset($templates[$templateKey])) {
            $prefill = $templates[$templateKey];
        }
        $this->layout('recruiter/job_form', [
            'title' => 'Publier une offre',
            'job' => null,
            'formAction' => '/recruiter/jobs',
            'submitLabel' => 'Publier l\'offre',
            'hero_title' => 'Publier une <em style="color:var(--re);">offre</em>',
            'hero_sub' => 'Nouveau poste',
            'hero_desc' => 'Renseignez le titre et les compétences. Optionnel : département, type de contrat, description.',
            'templates' => $templates,
            'prefill' => $prefill,
            'typeContratOptions' => self::TYPE_CONTRAT_OPTIONS,
        ]);
    }

    public function createJob(): void
    {
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /recruiter/jobs');
            exit;
        }
        $v = new Validator($_POST);
        $v->required('title');
        if ($v->fails()) {
            $this->flash(implode(' ', $v->errors()), 'danger');
            header('Location: /recruiter/jobs/create');
            exit;
        }
        $skillsRaw = trim($_POST['skills_raw'] ?? $_POST['skills'] ?? '');
        Job::create([
            'title' => $_POST['title'],
            'department' => $_POST['department'] ?? null,
            'description' => $_POST['description'] ?? null,
            'requirements' => $_POST['requirements'] ?? null,
            'skills_raw' => $skillsRaw !== '' ? $skillsRaw : null,
            'type_contrat' => ($t = trim($_POST['type_contrat'] ?? '')) !== '' ? $t : null,
            'created_by' => Auth::id(),
        ]);
        $this->flash('Offre publiée.', 'success');
        header('Location: /recruiter/jobs');
        exit;
    }

    public function duplicateJob(int $id): void
    {
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /recruiter/jobs');
            exit;
        }
        $newId = Job::duplicate($id, Auth::id());
        if ($newId) {
            $this->flash('Poste dupliqué. Vous pouvez le modifier.', 'success');
            header('Location: /recruiter/jobs/' . $newId . '/edit');
            exit;
        }
        $this->flash('Impossible de dupliquer ce poste.', 'danger');
        header('Location: /recruiter/jobs');
        exit;
    }

    public function showEditJob(int $id): void
    {
        $job = Job::find($id);
        if (!$job) {
            $this->redirect404();
            return;
        }
        $this->layout('recruiter/job_form', [
            'title' => 'Modifier le poste',
            'job' => $job,
            'formAction' => '/recruiter/jobs/' . $id . '/edit',
            'submitLabel' => 'Enregistrer',
            'hero_title' => 'Modifier le <em style="color:var(--re);">poste</em>',
            'hero_sub' => $job['title'],
            'hero_desc' => 'Mettez à jour la description, les compétences et les exigences.',
            'templates' => [],
            'prefill' => null,
            'typeContratOptions' => self::TYPE_CONTRAT_OPTIONS,
        ]);
    }

    public function editJob(int $id): void
    {
        $job = Job::find($id);
        if (!$job) {
            $this->redirect404();
            return;
        }
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /recruiter/jobs/' . $id);
            exit;
        }
        $v = new Validator($_POST);
        $v->required('title');
        if ($v->fails()) {
            $this->flash(implode(' ', $v->errors()), 'danger');
            header('Location: /recruiter/jobs/' . $id . '/edit');
            exit;
        }
        $skillsRaw = trim($_POST['skills_raw'] ?? $_POST['skills'] ?? '');
        Job::update($id, [
            'title' => $_POST['title'],
            'department' => $_POST['department'] ?? null,
            'description' => $_POST['description'] ?? null,
            'requirements' => $_POST['requirements'] ?? null,
            'skills_raw' => $skillsRaw !== '' ? $skillsRaw : null,
            'type_contrat' => ($t = trim($_POST['type_contrat'] ?? '')) !== '' ? $t : null,
        ]);
        $this->flash('Poste mis à jour.', 'success');
        header('Location: /recruiter/jobs/' . $id);
        exit;
    }

    public function jobApplications(int $id): void
    {
        $job = Job::find($id);
        if (!$job) {
            $this->redirect404();
            return;
        }
        $applications = Application::getByJob($id);
        $this->layout('recruiter/applications', [
            'title' => 'Candidatures — ' . $job['title'],
            'job' => $job,
            'applications' => $applications,
            'hero_title' => 'Candidatures <em style="color:var(--re);">reçues</em>',
            'hero_sub' => $job['title'],
            'hero_desc' => 'Consultez les candidatures et modifiez le statut.',
        ]);
    }

    public function updateApplicationStatus(int $jobId, int $appId): void
    {
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /recruiter/jobs/' . $jobId . '/applications');
            exit;
        }
        $allowed = ['submitted', 'viewed', 'shortlisted', 'rejected'];
        $status = $_POST['status'] ?? '';
        if (!in_array($status, $allowed, true)) {
            $this->flash('Statut invalide.', 'danger');
            header('Location: /recruiter/jobs/' . $jobId . '/applications');
            exit;
        }
        $pdo = \App\Core\DB::getInstance();
        $stmt = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND job_id = ?");
        $stmt->execute([$appId, $jobId]);
        if (!$stmt->fetch()) {
            $this->flash('Candidature introuvable.', 'danger');
            header('Location: /recruiter/jobs/' . $jobId . '/applications');
            exit;
        }
        Application::updateStatus($appId, $status);
        $this->flash('Statut mis à jour.', 'success');
        header('Location: /recruiter/jobs/' . $jobId . '/applications');
        exit;
    }

    public function showJob(int $id): void
    {
        $job = Job::find($id);
        if (!$job) {
            $this->redirect404();
            return;
        }
        $lastRun = \App\Models\PipelineLog::lastRecommendation($id);
        $this->layout('recruiter/job_show', ['title' => $job['title'], 'job' => $job, 'lastRun' => $lastRun]);
    }

    public function recommend(int $id): void
    {
        if (!Csrf::verify()) {
            $this->flash('Invalid request.', 'danger');
            header('Location: /recruiter/jobs/' . $id);
            exit;
        }
        try {
            $svc = new RecommendationService();
            $svc->runForJob($id, (int) ($_POST['top_k'] ?? 200));
            $this->flash('Recommendations generated.', 'success');
        } catch (\PDOException $e) {
            if ($e->getCode() === '42S02' || strpos($e->getMessage(), '1146') !== false) {
                $this->flash('Table des recommandations absente. Exécutez le script scripts/create_recommendations_table_only.sql (voir la page pour plus d’infos).', 'danger');
            } else {
                $this->flash('Erreur base de données : ' . $e->getMessage(), 'danger');
            }
        } catch (\Throwable $e) {
            $this->flash('Échec : ' . $e->getMessage(), 'danger');
        }
        header('Location: /recruiter/jobs/' . $id . '/results');
        exit;
    }

    public function results(int $id): void
    {
        $job = Job::find($id);
        if (!$job) {
            $this->redirect404();
            return;
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'ville' => $_GET['ville'] ?? '',
            'min_score' => isset($_GET['min_score']) ? (float) $_GET['min_score'] : null,
            'experience_min' => isset($_GET['experience_min']) ? (int) $_GET['experience_min'] : null,
            'experience_max' => isset($_GET['experience_max']) ? (int) $_GET['experience_max'] : null,
        ];
        $perPage = (int) ($_GET['per_page'] ?? 20);
        $perPage = min(max($perPage, 10), 100);
        $recommendationsTableMissing = false;
        try {
            $data = Recommendation::getByJobPaginated($id, $page, $perPage, $filters);
        } catch (\PDOException $e) {
            if ($e->getCode() === '42S02' || strpos($e->getMessage(), '1146') !== false) {
                $recommendationsTableMissing = true;
                $data = [
                    'items' => [],
                    'total' => 0,
                    'page' => 1,
                    'per_page' => $perPage,
                    'total_pages' => 0,
                ];
            } else {
                throw $e;
            }
        }
        $villes = Candidate::getDistinctVilles();
        $this->layout('recruiter/results', [
            'title' => 'Recommendations - ' . $job['title'],
            'job' => $job,
            'items' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'total_pages' => $data['total_pages'],
            'per_page' => $perPage,
            'filters' => $filters,
            'villes' => $villes,
            'recommendations_table_missing' => $recommendationsTableMissing,
        ]);
    }

    public function candidates(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(max((int) ($_GET['per_page'] ?? 20), 10), 100);
        $filters = [
            'ville' => $_GET['ville'] ?? '',
            'experience_min' => isset($_GET['experience_min']) ? (int) $_GET['experience_min'] : null,
            'experience_max' => isset($_GET['experience_max']) ? (int) $_GET['experience_max'] : null,
        ];
        $data = Candidate::paginate($page, $perPage, $filters);
        $villes = Candidate::getDistinctVilles();
        $this->layout('recruiter/candidates', [
            'title' => 'Tous les candidats',
            'items' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'total_pages' => $data['total_pages'],
            'per_page' => $perPage,
            'filters' => $filters,
            'villes' => $villes,
            'hero_title' => 'Tous les <em style="color:var(--re);">candidats</em>',
            'hero_sub' => 'Consultez la base de candidats',
        ]);
    }

    public function showCandidate(int $id): void
    {
        $candidate = Candidate::find($id);
        if (!$candidate) {
            $this->redirect404();
            return;
        }
        $applications = Application::findByCandidate($id);
        $cvs = \App\Models\Cv::findByCandidate($id);
        $latestCv = \App\Models\Cv::getLatest($id);
        $profile = Candidate::getProfile($id);
        $fullName = trim(($candidate['prenom'] ?? '') . ' ' . ($candidate['nom'] ?? ''));
        $lastName = trim($candidate['nom'] ?? '');
        $heroTitle = $lastName !== '' ? (trim($candidate['prenom'] ?? '') . ' <em>' . htmlspecialchars($lastName) . '</em>') : htmlspecialchars($fullName);
        $this->layout('recruiter/candidate_overview', [
            'title' => $fullName,
            'candidate' => $candidate,
            'applications' => $applications,
            'profile' => $profile,
            'cvs' => $cvs,
            'latestCv' => $latestCv,
            'hero_title' => $heroTitle,
            'hero_sub' => 'Profil candidat',
            'hero_desc' => 'Consulter le profil complet et les candidatures soumises.',
        ]);
    }

    public function downloadCandidateCv(int $id, int $cvId): void
    {
        $candidate = Candidate::find($id);
        if (!$candidate) {
            $this->redirect404();
            return;
        }
        $pdo = \App\Core\DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? AND candidate_id = ?");
        $stmt->execute([$cvId, $id]);
        $cv = $stmt->fetch();
        if (!$cv) {
            $this->redirect404();
            return;
        }
        $basePath = dirname(__DIR__, 2);
        $fullPath = \App\Models\Cv::resolveFullPath($basePath, $cv['file_path'] ?? '');
        if (!$fullPath) {
            $this->flash('Fichier CV introuvable.', 'danger');
            header('Location: /recruiter/candidates/' . $id);
            exit;
        }
        $name = $cv['original_name'] ?: 'CV_' . $candidate['prenom'] . '_' . $candidate['nom'] . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^\w\s\-\.]/', '_', $name) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    public function viewCvImages(int $id, int $cvId): void
    {
        $candidate = Candidate::find($id);
        if (!$candidate) {
            $this->redirect404();
            return;
        }
        $pdo = \App\Core\DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? AND candidate_id = ?");
        $stmt->execute([$cvId, $id]);
        $cv = $stmt->fetch();
        if (!$cv) {
            $this->redirect404();
            return;
        }
        $basePath = dirname(__DIR__, 2);
        $fullPath = \App\Models\Cv::resolveFullPath($basePath, $cv['file_path'] ?? '');
        if (!$fullPath) {
            $this->redirect404();
            return;
        }
        
        // Generate images directory path
        $imagesDir = $basePath . '/storage/cv_images/' . $cvId;
        $imagesUrlBase = '/storage/cv_images/' . $cvId;
        
        // Check if images already exist
        $imageFiles = [];
        if (is_dir($imagesDir)) {
            $files = glob($imagesDir . '/*_page_*.png');
            if (!empty($files)) {
                foreach ($files as $file) {
                    $imageFiles[] = $imagesUrlBase . '/' . basename($file);
                }
                sort($imageFiles);
            }
        }
        
        // If no images exist, generate them
        if (empty($imageFiles) && is_file($basePath . '/python/pdf_to_images.py')) {
            try {
                $runner = new \App\Services\PythonRunner();
                $isWindows = (DIRECTORY_SEPARATOR === '\\' || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
                $pythonCmd = $_ENV['PYTHON_PATH'] ?? ($isWindows ? 'python' : 'python3');
                $result = $runner->runCommand([
                    $pythonCmd,
                    $basePath . '/python/pdf_to_images.py',
                    $fullPath,
                    $imagesDir,
                    '150',
                    'png'
                ]);
                
                if (!empty($result['output'])) {
                    $output = json_decode($result['output'], true);
                    if (isset($output['images']) && is_array($output['images'])) {
                        foreach ($output['images'] as $imgPath) {
                            $imageFiles[] = $imagesUrlBase . '/' . basename($imgPath);
                        }
                        sort($imageFiles);
                    }
                }
            } catch (\Throwable $e) {
                // If conversion fails, fall back to PDF download
            }
        }
        
        // Return JSON with image URLs
        header('Content-Type: application/json');
        echo json_encode([
            'success' => !empty($imageFiles),
            'images' => $imageFiles,
            'pdf_url' => '/recruiter/candidates/' . $id . '/cv/' . $cvId
        ]);
        exit;
    }

    public function serveCvImage(int $cvId, string $filename): void
    {
        $basePath = dirname(__DIR__, 2);
        $imagePath = $basePath . '/storage/cv_images/' . $cvId . '/' . $filename;
        
        // Security: only allow PNG/JPEG files
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            $this->redirect404();
            return;
        }
        
        // Check if file exists
        if (!is_file($imagePath)) {
            $this->redirect404();
            return;
        }
        
        // Verify CV exists and user has access
        $pdo = \App\Core\DB::getInstance();
        $stmt = $pdo->prepare("SELECT candidate_id FROM cvs WHERE id = ?");
        $stmt->execute([$cvId]);
        $cv = $stmt->fetch();
        if (!$cv) {
            $this->redirect404();
            return;
        }
        
        // Serve image
        $mimeType = $ext === 'png' ? 'image/png' : 'image/jpeg';
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($imagePath));
        header('Cache-Control: public, max-age=3600');
        readfile($imagePath);
        exit;
    }

    public function stats(): void
    {
        $jobsCount = count(Job::all());
        $candidatesCount = Candidate::count();
        $pdo = \App\Core\DB::getInstance();
        $applicationsCount = (int) $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
        $this->layout('recruiter/stats', [
            'title' => 'Statistiques',
            'jobsCount' => $jobsCount,
            'candidatesCount' => $candidatesCount,
            'applicationsCount' => $applicationsCount,
            'hero_title' => 'Statistiques',
            'hero_sub' => 'Vue d\'ensemble',
        ]);
    }

    public function candidateDetail(int $jobId, int $candidateId): void
    {
        $job = Job::find($jobId);
        $candidate = Candidate::find($candidateId);
        if (!$job || !$candidate) {
            $this->redirect404();
            return;
        }
        $cvs = \App\Models\Cv::findByCandidate($candidateId);
        $latestCv = \App\Models\Cv::getLatest($candidateId);
        $cvExtractedText = $latestCv['extracted_text'] ?? null;
        // Only extract if no extracted text exists, CV file exists, and not tried recently
        if (($cvExtractedText === null || $cvExtractedText === '') && !empty($cvs) && $latestCv && !empty($latestCv['file_path'])) {
            $lastAttempt = $latestCv['extraction_attempted_at'] ?? null;
            $shouldRetry = !$lastAttempt || (time() - strtotime($lastAttempt)) > 3600;
            if ($shouldRetry) {
                $projectRoot = dirname(__DIR__, 2);
                $fullPath = $projectRoot . '/' . $latestCv['file_path'];
                if (is_file($fullPath) && is_readable($fullPath)) {
                    try {
                        \App\Models\Cv::markExtractionAttempted((int) $latestCv['id']);
                        $runner = new \App\Services\PythonRunner();
                        $result = $runner->runExtractPdf($fullPath);
                        if (!empty($result['text'])) {
                            \App\Models\Cv::updateExtractedText((int) $latestCv['id'], $result['text']);
                            $cvExtractedText = $result['text'];
                        }
                    } catch (\Throwable $e) {
                        error_log('CV extraction failed for candidate ' . $candidateId . ': ' . $e->getMessage());
                    }
                }
            }
        }
        $profile = Candidate::getProfile($candidateId);
        $rec = null;
        $pdo = \App\Core\DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM recommendations WHERE job_id = ? AND candidate_id = ?");
        $stmt->execute([$jobId, $candidateId]);
        $rec = $stmt->fetch();
        $application = Application::findByJobAndCandidate($jobId, $candidateId);
        $applications = Application::findByCandidate($candidateId);
        $this->layout('recruiter/candidate_detail', [
            'title' => 'Candidate - ' . $candidate['prenom'] . ' ' . $candidate['nom'],
            'job' => $job,
            'candidate' => $candidate,
            'profile' => $profile,
            'cvs' => $cvs,
            'latestCv' => $latestCv,
            'recommendation' => $rec,
            'application' => $application,
            'applications' => $applications,
        ]);
    }

    /** Afficher le CV généré à partir du profil (modèle vert/blanc) — pour iframe quand aucun PDF déposé. */
    public function viewCandidateCvProfil(int $id): void
    {
        $candidate = Candidate::find($id);
        if (!$candidate) {
            $this->redirect404();
            return;
        }
        $profile = Candidate::getProfile($id);
        $profile = \App\Services\CandidateProfileSchema::normalizeCandidateProfile($profile);
        header('Content-Type: text/html; charset=UTF-8');
        echo $this->renderCvPdfHtmlFromProfile($profile);
        exit;
    }

    /** Télécharger le CV : PDF uploadé si présent, sinon CV généré à partir du profil. */
    public function downloadCandidateCvPdf(int $id): void
    {
        $candidate = Candidate::find($id);
        if (!$candidate) {
            $this->redirect404();
            return;
        }
        $latestCv = \App\Models\Cv::getLatest($id);
        $basePath = dirname(__DIR__, 2);
        $fullPath = null;
        if ($latestCv && !empty($latestCv['file_path'])) {
            $fullPath = \App\Models\Cv::resolveFullPath($basePath, $latestCv['file_path'] ?? '');
        }
        if ($fullPath && is_file($fullPath)) {
            $name = $latestCv['original_name'] ?: 'CV_' . $candidate['prenom'] . '_' . $candidate['nom'] . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . preg_replace('/[^\w\s\-\.]/', '_', $name) . '"');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit;
        }
        $profile = Candidate::getProfile($id);
        $profile = \App\Services\CandidateProfileSchema::normalizeCandidateProfile($profile);
        $html = $this->renderCvPdfHtmlFromProfile($profile);
        $filename = 'CV_' . preg_replace('/[^\w\s\-\.]/', '_', trim($candidate['prenom'] . ' ' . $candidate['nom'])) . '.pdf';
        if (!class_exists(\Dompdf\Dompdf::class)) {
            $this->flash('Export PDF indisponible. Affichage du CV en page.', 'info');
            header('Location: /recruiter/candidates/' . $id . '/cv-profil');
            exit;
        }
        $this->outputPdf($html, $filename);
    }

    /** Pré-remplir le profil à partir du CV : ré-extraction du PDF (avec fallback OCR) puis parsing → champs. */
    public function fillCandidateFromCv(int $id): void
    {
        if (!\App\Core\Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            $this->redirectToRefererOrJobs($id);
            return;
        }
        $candidate = Candidate::find($id);
        if (!$candidate) {
            $this->redirect404();
            return;
        }
        $text = null;
        $latestCv = \App\Models\Cv::getLatest($id);
        $projectRoot = dirname(__DIR__, 2);
        if ($latestCv && !empty($latestCv['file_path'])) {
            $fullPath = $projectRoot . '/' . $latestCv['file_path'];
            if (is_file($fullPath) && is_readable($fullPath)) {
                $runner = new \App\Services\PythonRunner();
                $result = $runner->runExtractPdf($fullPath);
                if (!empty($result['text'])) {
                    $text = $result['text'];
                    if (isset($latestCv['id'])) {
                        \App\Models\Cv::updateExtractedText((int) $latestCv['id'], $text);
                    }
                }
            }
        }
        if ($text === null || $text === '') {
            $text = \App\Models\Cv::getLatestExtractedText($id);
        }
        if ($text === null || $text === '') {
            $this->flash('Aucun CV ou texte extrait pour ce candidat.', 'danger');
            $this->redirectToRefererOrJobs($id);
            return;
        }
        $overwrite = !empty($_POST['overwrite']);
        $useOllama = !empty($_ENV['OLLAMA_CV_ENABLED']) && ($_ENV['OLLAMA_CV_ENABLED'] === '1' || $_ENV['OLLAMA_CV_ENABLED'] === 'true');
        $filled = [];
        try {
            if ($useOllama) {
                $extractor = new \App\Services\Ollama\CvExtractionService();
                $canonicalProfile = $extractor->extractFromText($text);
                $filled = Candidate::applyCanonicalProfile($id, $canonicalProfile, $overwrite);
            } else {
                $parsedData = [];
                if (isset($result) && is_array($result)) {
                    $parsedData = array_merge($result['structured'] ?? [], $result['parsed'] ?? []);
                }
                if (!empty($parsedData)) {
                    $filled = $overwrite
                        ? Candidate::mergeParsedIntoProfileOverwrite($id, $parsedData)
                        : Candidate::mergeParsedIntoProfile($id, $parsedData);
                }
            }
            if (!empty($filled)) {
                $this->flash('Profil ' . ($overwrite ? 'écrasé et ' : '') . 'rempli à partir du CV (' . count($filled) . ' champ(s) mis à jour).' . ($useOllama ? ' (Ollama)' : ''), 'success');
            } else {
                $this->flash($useOllama
                    ? 'Aucun champ extrait par Ollama. Vérifiez le modèle et le texte du CV.'
                    : 'Aucun champ vide à remplir (le profil est déjà complet ou le CV n\'a pas pu être parsé). Cochez « Écraser les champs existants » pour forcer le remplacement.', 'info');
            }
        } catch (\RuntimeException $e) {
            $this->flash($e->getMessage(), 'danger');
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if ((int) $e->getCode() === 23000 || strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
                $this->flash('Cet email est déjà utilisé par un autre candidat. Veuillez corriger l\'email dans le CV ou dans le profil.', 'danger');
            } else {
                $this->flash('Une erreur base de données s\'est produite. Veuillez réessayer.', 'danger');
            }
        } catch (\Throwable $e) {
            $this->flash('Une erreur inattendue s\'est produite lors de la mise à jour du profil.', 'danger');
        }
        $this->redirectToRefererOrJobs($id);
    }

    private function redirectToRefererOrJobs(int $candidateId): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && strpos($referer, '/recruiter/') !== false) {
            header('Location: ' . $referer);
        } else {
            header('Location: /recruiter/jobs');
        }
        exit;
    }

    /** Render PDF HTML from canonical profile only (never from raw CV). */
    private function renderCvPdfHtmlFromProfile(array $profile): string
    {
        ob_start();
        include dirname(__DIR__) . '/Views/recruiter/cv_pdf_from_profile.php';
        return (string) ob_get_clean();
    }

    private function outputPdf(string $html, string $filename): void
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            $this->flash('Export PDF indisponible (Dompdf non installé).', 'danger');
            header('Location: /recruiter/jobs');
            exit;
        }
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->getOptions()->setDpi(96);
        $dompdf->getOptions()->setDefaultFont('DejaVu Sans');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: private, no-cache, no-store');
        $output = $dompdf->output();
        header('Content-Length: ' . strlen($output));
        echo $output;
        exit;
    }

    private function layout(string $view, array $data = []): void
    {
        extract($data);
        $user = Auth::user();
        $sidebar = 'recruiter';
        require dirname(__DIR__) . '/Views/layouts/recruiter.php';
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
