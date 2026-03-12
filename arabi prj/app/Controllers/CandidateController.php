<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\Job;
use App\Models\Recommendation;

class CandidateController
{
    public function profile(): void
    {
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $candidate = $this->ensureCandidateRecord();
        }
        $profile = $candidate ? CandidateProfile::get($candidate['id']) : null;
        $cvs = $candidate ? Cv::findByCandidate($candidate['id']) : [];
        // Réattribuer les CV d'un autre candidat (même email, ex. import CSV) au candidat connecté
        if ($candidate && empty($cvs)) {
            $email = trim($candidate['email'] ?? '');
            if ($email !== '') {
                $pdo = \App\Core\DB::getInstance();
                $stmt = $pdo->prepare("SELECT id, user_id FROM candidates WHERE LOWER(TRIM(email)) = LOWER(?) AND id != ?");
                $stmt->execute([$email, $candidate['id']]);
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $otherId = (int) $row['id'];
                    $otherUserId = isset($row['user_id']) ? (int) $row['user_id'] : null;
                    if ($otherUserId === null || $otherUserId === (int) $user['id']) {
                        $reassigned = Cv::reassignToCandidate($otherId, $candidate['id']);
                        if ($reassigned > 0) {
                            $cvs = Cv::findByCandidate($candidate['id']);
                            break;
                        }
                    }
                }
            }
        }
        try {
            $documents = $candidate ? CandidateDocument::findByCandidate($candidate['id']) : [];
        } catch (\Throwable $e) {
            $documents = [];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::verify()) {
            $allowed = ['nom', 'prenom', 'email', 'telephone', 'age', 'ville', 'experience_annees', 'poste_actuel', 'entreprise_actuelle', 'education_niveau', 'diplome', 'universite', 'annee_diplome', 'competences_techniques_raw', 'competences_langues_raw', 'langues_niveau_raw', 'experience_detail_raw', 'projets_raw', 'certifications_raw', 'disponibilite', 'pretention_salaire', 'formations_json', 'experiences_json'];
            $data = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $_POST)) {
                    $v = $_POST[$k];
                    if (is_string($v)) {
                        $v = trim($v);
                    }
                    if ($k === 'age' || $k === 'experience_annees') {
                        $v = $v !== '' && is_numeric($v) ? (int) $v : null;
                    }
                    if (($k === 'formations_json' || $k === 'experiences_json') && is_string($v) && $v !== '') {
                        $decoded = json_decode($v, true);
                        $v = (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) ? $v : null;
                    }
                    $data[$k] = $v === '' ? null : $v;
                }
            }
            // La BDD impose NOT NULL sur nom/prenom : envoyer '' au lieu de null si vide
            if (array_key_exists('nom', $data) && $data['nom'] === null) {
                $data['nom'] = '';
            }
            if (array_key_exists('prenom', $data) && $data['prenom'] === null) {
                $data['prenom'] = '';
            }
            if (array_key_exists('email', $data) && $data['email'] === null) {
                $data['email'] = '';
            }
            // Email de contact (profil) : validation format si renseigné
            if ($candidate && array_key_exists('email', $data) && $data['email'] !== null && (string)$data['email'] !== '') {
                if (!filter_var(trim((string)$data['email']), FILTER_VALIDATE_EMAIL)) {
                    $this->flash('Adresse email de contact invalide.', 'danger');
                    header('Location: /candidate/profile');
                    exit;
                }
            }
            if ($candidate) {
                if (empty($data['nom']) && empty($data['prenom']) && isset($data['nom'])) {
                    $this->flash('Le nom et le prénom sont recommandés.', 'danger');
                } else {
                    Candidate::updateProfile($candidate['id'], $user['id'], $data);
                    $canonical = \App\Services\CandidateProfileSchema::mapFormToProfile($_POST);
                    Candidate::saveProfile($candidate['id'], $canonical);
                    $this->flash('Profil enregistré avec succès. Vos modifications ont bien été prises en compte.', 'success');
                }
            }
            header('Location: /candidate/profile');
            exit;
        }

        $profileProgress = $this->computeProfileProgress($candidate, $cvs);
        $trackSteps = $this->trackSteps($candidate, $cvs, $profileProgress);
        $matchingJobs = [];
        if ($candidate) {
            try {
                $matchingJobs = array_slice(Recommendation::getByCandidate($candidate['id']), 0, 5);
            } catch (\Throwable $e) {
                // Table recommendations may not exist yet; show empty list
            }
        }
        $jobsCount = \App\Models\Job::count();
        $allJobs = \App\Models\Job::all();
        $suggestedJobs = array_slice($allJobs, 0, 6);
        $applications = $candidate ? Application::findByCandidate($candidate['id']) : [];
        $applicationsCount = count($applications);
        $appliedJobIds = array_column($applications, 'job_id');
        $bestScore = count($matchingJobs) > 0 ? round((float)$matchingJobs[0]['score'] * 100) . '%' : '—';
        $this->layout('candidate/profile', [
            'title' => 'Mon profil',
            'candidate' => $candidate,
            'profile' => $profile,
            'cvs' => $cvs,
            'documents' => $documents,
            'profileProgress' => $profileProgress,
            'trackSteps' => $trackSteps,
            'matchingJobs' => $matchingJobs,
            'suggestedJobs' => $suggestedJobs,
            'applicationsCount' => $applicationsCount,
            'appliedJobIds' => $appliedJobIds,
            'jobsCount' => $jobsCount,
            'hero_title' => 'Complétez votre profil pour <em style="color:var(--ca);">maximiser</em> vos chances',
            'hero_sub' => 'Bienvenue',
            'hero_desc' => 'Notre IA analyse votre profil et le compare aux postes disponibles. Un profil complet améliore votre score de correspondance.',
            'hero_stats' => [
                ['value' => $profileProgress . '%', 'label' => 'Profil complété'],
                ['value' => (string)$jobsCount, 'label' => 'Postes actifs'],
                ['value' => $bestScore, 'label' => 'Meilleur score IA'],
            ],
            'hero_color' => '--ca',
        ]);
    }

    private function computeProfileProgress(?array $candidate, array $cvs): int
    {
        if (!$candidate) {
            return 0;
        }
        $fields = [
            'prenom', 'nom', 'telephone', 'ville', 'age', 'poste_actuel', 'entreprise_actuelle',
            'experience_annees', 'experience_detail_raw', 'education_niveau', 'diplome', 'universite', 'annee_diplome',
            'competences_techniques_raw', 'competences_langues_raw', 'langues_niveau_raw',
            'projets_raw', 'certifications_raw', 'disponibilite', 'pretention_salaire',
        ];
        $filled = 0;
        foreach ($fields as $f) {
            $v = $candidate[$f] ?? '';
            if (is_string($v) && trim($v) !== '') {
                $filled++;
            } elseif ($f === 'experience_annees' && isset($candidate['experience_annees']) && (int)$candidate['experience_annees'] >= 0) {
                $filled++;
            } elseif ($f === 'age' && isset($candidate['age']) && (int)$candidate['age'] > 0) {
                $filled++;
            }
        }
        $total = count($fields);
        $pct = $total > 0 ? (int) round(($filled / $total) * 100) : 0;
        if (!empty($cvs)) {
            $pct = min(100, $pct + 5);
        }
        return min(100, $pct);
    }

    private function trackSteps(?array $candidate, array $cvs, int $profileProgress): array
    {
        $steps = [
            ['id' => 1, 'label' => 'Compte créé', 'sub' => 'Inscription', 'done' => true],
            ['id' => 2, 'label' => 'Profil', 'sub' => $profileProgress >= 100 ? 'Complété à 100%' : 'Complétez à 100%', 'done' => $profileProgress >= 100, 'current' => $profileProgress < 100],
            ['id' => 3, 'label' => 'CV soumis', 'sub' => !empty($cvs) ? 'Reçu' : 'En attente', 'done' => !empty($cvs), 'current' => $profileProgress >= 100 && empty($cvs)],
            ['id' => 4, 'label' => 'Candidature', 'sub' => 'Shortlist ou résultat', 'done' => false, 'current' => false],
        ];
        if ($candidate) {
            $apps = Application::findByCandidate($candidate['id']);
            $hasResult = false;
            foreach ($apps as $a) {
                if (in_array($a['status'] ?? '', ['shortlisted', 'rejected'], true)) {
                    $hasResult = true;
                    break;
                }
            }
            $steps[3]['done'] = $hasResult;
            $steps[3]['sub'] = $hasResult ? 'Résultat disponible' : 'En attente';
            $steps[3]['current'] = !$hasResult && !empty($cvs);
        }
        return $steps;
    }

    public function uploadCv(): void
    {
        if (!Csrf::verify()) {
            $this->flash('Invalid request.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $candidate = $this->ensureCandidateRecord();
        }
        if (!$candidate) {
            $this->flash('Profile required first.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }

        $maxSize = ((int) ($_ENV['MAX_CV_SIZE_MB'] ?? 5)) * 1024 * 1024;
        $allowedMime = $_ENV['MAX_CV_MIME'] ?? 'application/pdf';
        if (empty($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('Veuillez sélectionner un fichier PDF.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        if ($_FILES['cv']['size'] > $maxSize) {
            $this->flash('Fichier trop volumineux (max ' . round($maxSize / 1024 / 1024) . ' Mo).', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['cv']['tmp_name']);
        if ($mime !== 'application/pdf') {
            $this->flash('Seuls les fichiers PDF sont autorisés.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $storageDir = dirname(__DIR__, 2) . '/storage/cv';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION) ?: 'pdf';
        $filename = 'c' . $candidate['id'] . '_' . date('YmdHis') . '.' . $ext;
        $path = $storageDir . '/' . $filename;
        if (!move_uploaded_file($_FILES['cv']['tmp_name'], $path)) {
            $this->flash('Échec du téléchargement. Veuillez réessayer.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $relativePath = 'storage/cv/' . $filename;
        $cvId = Cv::create($candidate['id'], $relativePath, $_FILES['cv']['name'], null);

        // Extraction texte + pré-remplissage automatique (formation, expériences, compétences)
        $filled = [];
        $text = null;
        $result = [];
        $pythonPath = dirname(__DIR__, 2) . '/' . $relativePath;
        if (is_file(dirname(__DIR__, 2) . '/python/extract_pdf_text.py')) {
            try {
                $runner = new \App\Services\PythonRunner();
                $result = $runner->runExtractPdf($pythonPath);
                if (!empty($result['text'])) {
                    $text = $result['text'];
                    Cv::updateExtractedText($cvId, $text);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        // Ollama extraction si activé (meilleure qualité)
        $useOllama = !empty($_ENV['OLLAMA_CV_ENABLED']) && ($_ENV['OLLAMA_CV_ENABLED'] === '1' || $_ENV['OLLAMA_CV_ENABLED'] === 'true');
        if ($useOllama && $text) {
            try {
                $extractor = new \App\Services\Ollama\CvExtractionService();
                $canonicalProfile = $extractor->extractFromText($text);
                $filled = Candidate::applyCanonicalProfile($candidate['id'], $canonicalProfile, false);
            } catch (\Throwable $e) {
                // fallback Python parsed
            }
        }
        if (empty($filled) && !empty($result)) {
            $parsedData = array_merge($result['structured'] ?? [], $result['parsed'] ?? []);
            if (!empty($parsedData)) {
                $filled = Candidate::mergeParsedIntoProfile($candidate['id'], $parsedData);
            }
        }
        if (!empty($filled)) {
            $this->flash('CV téléchargé avec succès! Les champs vides ont été pré-remplis à partir du CV (email, téléphone, compétences, etc.).', 'success');
        } else {
            $this->flash('CV téléchargé avec succès!', 'success');
        }
        header('Location: /candidate/profile');
        exit;
    }

    /** Candidat consulte son propre CV (PDF inline dans l'onglet). */
    public function viewOwnCv(int $cvId): void
    {
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $this->redirect404();
            return;
        }
        $pdo = \App\Core\DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? AND candidate_id = ?");
        $stmt->execute([$cvId, $candidate['id']]);
        $cv = $stmt->fetch();
        if (!$cv) {
            $this->redirect404();
            return;
        }
        $basePath = dirname(__DIR__, 2);
        $fullPath = Cv::resolveFullPath($basePath, $cv['file_path'] ?? '');
        if (!$fullPath) {
            $this->flash('Fichier CV introuvable.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $name = $cv['original_name'] ?: 'CV_' . $candidate['prenom'] . '_' . $candidate['nom'] . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^\w\s\-\.]/', '_', $name) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    /** Supprimer un CV PDF déposé. */
    public function deleteCv(int $cvId): void
    {
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $this->redirect404();
            return;
        }
        $cv = Cv::findByIdAndCandidate($cvId, (int) $candidate['id']);
        if (!$cv) {
            $this->flash('CV introuvable.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $basePath = dirname(__DIR__, 2);
        $fullPath = Cv::resolveFullPath($basePath, $cv['file_path'] ?? '');
        if ($fullPath && is_file($fullPath)) {
            @unlink($fullPath);
        }
        Cv::delete($cvId);
        $this->flash('CV supprimé.', 'success');
        header('Location: /candidate/profile');
        exit;
    }

    public function uploadDocument(): void
    {
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $this->flash('Profil requis.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $docType = in_array($_POST['doc_type'] ?? '', ['certificat', 'preuve', 'autre'], true) ? $_POST['doc_type'] : 'preuve';
        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('Veuillez sélectionner un fichier (PDF ou image).', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $maxSize = 10 * 1024 * 1024; // 10 MB
        if ($_FILES['document']['size'] > $maxSize) {
            $this->flash('Fichier trop volumineux (max 10 Mo).', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION) ?: '');
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExt, true)) {
            $this->flash('Format accepté : PDF, JPG, PNG, WebP.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $storageDir = dirname(__DIR__, 2) . '/storage/documents/' . $candidate['id'];
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $filename = 'doc_' . date('YmdHis') . '_' . substr(md5((string)mt_rand()), 0, 8) . '.' . $ext;
        $path = $storageDir . '/' . $filename;
        if (!move_uploaded_file($_FILES['document']['tmp_name'], $path)) {
            $this->flash('Échec de l\'envoi.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $relativePath = 'storage/documents/' . $candidate['id'] . '/' . $filename;
        CandidateDocument::create($candidate['id'], $relativePath, $_FILES['document']['name'], $docType);
        $this->flash('Document ajouté (certificat/preuve).', 'success');
        header('Location: /candidate/profile');
        exit;
    }

    public function deleteDocument(string $id): void
    {
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            header('Location: /candidate/profile');
            exit;
        }
        $docId = (int) $id;
        $doc = CandidateDocument::find($docId);
        if ($doc && (int)$doc['candidate_id'] === (int)$candidate['id']) {
            $fullPath = dirname(__DIR__, 2) . '/' . $doc['file_path'];
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            CandidateDocument::delete($docId, $candidate['id']);
            $this->flash('Document supprimé.', 'success');
        }
        header('Location: /candidate/profile');
        exit;
    }

    public function downloadDocument(string $id): void
    {
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            header('Location: /candidate/profile');
            exit;
        }
        $docId = (int) $id;
        $doc = CandidateDocument::find($docId);
        if (!$doc || (int)$doc['candidate_id'] !== (int)$candidate['id']) {
            $this->flash('Document introuvable.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $fullPath = dirname(__DIR__, 2) . '/' . $doc['file_path'];
        if (!is_file($fullPath)) {
            $this->flash('Fichier absent.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $mimes = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($doc['original_name']) . '"');
        readfile($fullPath);
        exit;
    }

    public function jobs(): void
    {
        $jobs = Job::all();
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        $appliedIds = [];
        if ($candidate) {
            $apps = Application::findByCandidate($candidate['id']);
            $appliedIds = array_column($apps, 'job_id');
        }
        $this->layout('candidate/jobs', [
            'title' => 'Offres disponibles',
            'jobs' => $jobs,
            'appliedIds' => $appliedIds,
            'hero_title' => 'Offres <em style="color:var(--ca);">disponibles</em>',
            'hero_sub' => 'Consultez les offres',
            'hero_desc' => 'Postulez en un clic avec votre profil. Suivez l\'état de vos candidatures dans "Mes candidatures".',
        ]);
    }

    public function showApply(string $id): void
    {
        $jobId = (int) $id;
        $job = Job::find($jobId);
        if (!$job) {
            $this->flash('Offre introuvable.', 'danger');
            header('Location: /candidate/jobs');
            exit;
        }
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $candidate = $this->ensureCandidateRecord();
        }
        if (!$candidate) {
            $this->flash('Complétez d\'abord votre profil.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $existing = Application::findByJobAndCandidate($jobId, $candidate['id']);
        if ($existing) {
            $this->flash('Vous avez déjà postulé à cette offre.', 'info');
            header('Location: /candidate/jobs');
            exit;
        }
        $this->layout('candidate/apply', [
            'title' => 'Postuler : ' . $job['title'],
            'job' => $job,
            'candidate' => $candidate,
            'hero_title' => 'Postuler : ' . htmlspecialchars($job['title']),
            'hero_sub' => 'Formulaire de candidature',
            'hero_desc' => 'Renseignez votre lettre de motivation (optionnel). Vos informations de profil sont déjà transmises.',
        ]);
    }

    public function submitApply(string $id): void
    {
        if (!Csrf::verify()) {
            $this->flash('Requête invalide.', 'danger');
            header('Location: /candidate/jobs');
            exit;
        }
        $jobId = (int) $id;
        $job = Job::find($jobId);
        if (!$job) {
            $this->flash('Offre introuvable.', 'danger');
            header('Location: /candidate/jobs');
            exit;
        }
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $candidate = $this->ensureCandidateRecord();
        }
        if (!$candidate) {
            $this->flash('Complétez d\'abord votre profil.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $existing = Application::findByJobAndCandidate($jobId, $candidate['id']);
        if ($existing) {
            $this->flash('Vous avez déjà postulé à cette offre.', 'info');
            header('Location: /candidate/applications');
            exit;
        }
        $coverLetter = isset($_POST['cover_letter']) ? trim((string) $_POST['cover_letter']) : null;
        Application::create($jobId, $candidate['id'], $coverLetter ?: null);
        $this->flash('Candidature envoyée avec succès.', 'success');
        header('Location: /candidate/applications');
        exit;
    }

    public function applications(): void
    {
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $candidate = $this->ensureCandidateRecord();
        }
        $list = $candidate ? Application::findByCandidate($candidate['id']) : [];
        $this->layout('candidate/applications', [
            'title' => 'Mes candidatures',
            'applications' => $list,
            'hero_title' => 'Mes <em style="color:var(--ca);">candidatures</em>',
            'hero_sub' => 'Suivi',
            'hero_desc' => 'Consultez l\'état de vos candidatures par offre.',
        ]);
    }

    public function results(): void
    {
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $candidate = $this->ensureCandidateRecord();
        }
        $scores = [];
        if ($candidate) {
            try {
                $scores = Recommendation::getByCandidate($candidate['id']);
            } catch (\Throwable $e) {
                // Table recommendations may not exist yet
            }
        }
        $this->layout('candidate/results', [
            'title' => 'Résultats IA',
            'scores' => $scores,
            'hero_title' => 'Vos <em style="color:var(--ca);">résultats IA</em>',
            'hero_sub' => 'Score de correspondance par poste',
            'hero_desc' => 'L\'IA a analysé votre profil pour chaque poste. Plus le score est élevé, plus votre profil correspond à l\'offre.',
        ]);
    }

    public function generateCvPdf(): void
    {
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $candidate = $this->ensureCandidateRecord();
        }
        if (!$candidate) {
            $this->flash('Aucun profil à exporter.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $cvs = Cv::findByCandidate($candidate['id']);
        $profileProgress = $this->computeProfileProgress($candidate, $cvs);
        $cvData = $this->buildCvData($candidate);
        $cvData['profileComplete'] = $profileProgress;
        require dirname(__DIR__) . '/Views/candidate/generate_cv.php';
    }

    /** Export CV PDF from canonical profile (same source as recruiter preview/PDF). */
    public function downloadCvPdf(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $this->flash('Aucun profil à exporter.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $profile = Candidate::getProfile($candidate['id']);
        $html = $this->renderCvPdfHtmlFromProfile($profile);
        $filename = 'CV_' . preg_replace('/[^\p{L}\p{N}\s\-]/u', '_', trim(($candidate['prenom'] ?? '') . '_' . ($candidate['nom'] ?? ''))) . '.pdf';
        if (trim($filename) === 'CV_.pdf') {
            $filename = 'CV.pdf';
        }
        $this->outputPdf($html, $filename);
    }

    private function renderCvPdfHtmlFromProfile(array $profile): string
    {
        ob_start();
        include dirname(__DIR__) . '/Views/recruiter/cv_pdf_from_profile.php';
        return (string) ob_get_clean();
    }

    private function outputPdf(string $html, string $filename): void
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>PDF indisponible</title></head><body style="font-family:sans-serif;padding:2rem;"><h1>Export PDF indisponible</h1><p>Installez la dépendance dans le conteneur : <code>docker compose exec php composer require dompdf/dompdf</code></p><p><a href="/candidate/profile/generate-cv">Retour à la page CV</a></p></body></html>';
            exit;
        }
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->getOptions()->setDpi(96);
        $dompdf->getOptions()->setDefaultFont('DejaVu Sans');
        $dompdf->getOptions()->setIsFontSubsettingEnabled(false);
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

    public function downloadCvWord(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        $user = Auth::user();
        $candidate = Candidate::findByUserId($user['id']);
        if (!$candidate) {
            $this->flash('Aucun profil à exporter.', 'danger');
            header('Location: /candidate/profile');
            exit;
        }
        $filename = 'CV_' . preg_replace('/[^\p{L}\p{N}\s\-]/u', '_', trim(($candidate['prenom'] ?? '') . '_' . ($candidate['nom'] ?? ''))) . '.docx';
        if (trim($filename) === 'CV_.docx') {
            $filename = 'CV.docx';
        }
        $this->outputWord($candidate, $filename);
    }

    private function outputWord(array $c, string $filename): void
    {
        if (!class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Word indisponible</title></head><body style="font-family:sans-serif;padding:2rem;"><h1>Export Word indisponible</h1><p>Installez la dépendance dans le conteneur : <code>docker compose exec php composer require phpoffice/phpword</code></p><p><a href="/candidate/profile/generate-cv">Retour à la page CV</a></p></body></html>';
            exit;
        }
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $name = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
        $initials = strtoupper(mb_substr($c['prenom'] ?? 'X', 0, 1) . mb_substr($c['nom'] ?? 'X', 0, 1));
        if (strlen($initials) < 2) $initials = 'CV';
        $role = $c['poste_actuel'] ?? 'Candidat';
        $expYears = (int)($c['experience_annees'] ?? 0);
        $roleLine = $role . ($expYears > 0 ? ' · ' . $expYears . ' an(s) d\'expérience' : '');
        $white = ['color' => 'FFFFFF', 'size' => 9];
        $whiteBold = ['color' => 'FFFFFF', 'size' => 11, 'bold' => true];
        $whiteSmall = ['color' => 'FFFFFF', 'size' => 8];
        $accent = ['color' => '1a6b4a', 'size' => 10, 'bold' => true];
        $gray = ['color' => '555555', 'size' => 9];
        $headingStyle = ['color' => '1a6b4a', 'size' => 9, 'bold' => true];

        $table = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $table->addRow();
        $cellLeft = $table->addCell(2500, ['bgColor' => '1a6b4a', 'valign' => 'top']);
        $cellLeft->addText($initials, array_merge($whiteBold, ['size' => 18]), ['align' => 'center', 'spaceAfter' => 100]);
        $cellLeft->addText($name, $whiteBold, ['align' => 'center', 'spaceAfter' => 50]);
        $cellLeft->addText($roleLine, $whiteSmall, ['align' => 'center', 'spaceAfter' => 300]);
        $cellLeft->addText('CONTACT', $whiteSmall, ['spaceAfter' => 150]);
        if (!empty($c['email'])) $cellLeft->addText('✉ ' . $c['email'], $whiteSmall, ['spaceAfter' => 80]);
        if (!empty($c['telephone'])) $cellLeft->addText('☎ ' . $c['telephone'], $whiteSmall, ['spaceAfter' => 80]);
        if (!empty($c['ville'])) $cellLeft->addText('📍 ' . $c['ville'], $whiteSmall, ['spaceAfter' => 150]);
        $cellLeft->addText('COMPÉTENCES', $whiteSmall, ['spaceAfter' => 100]);
        if (!empty($c['competences_techniques_raw'])) {
            $skills = array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/\n]+/', $c['competences_techniques_raw']))));
            foreach (array_slice($skills, 0, 6) as $s) {
                $cellLeft->addText(htmlspecialchars($s), $whiteSmall, ['spaceAfter' => 60]);
            }
        }
        $cellLeft->addText('LANGUES', $whiteSmall, ['spaceAfter' => 100]);
        if (!empty($c['competences_langues_raw'])) {
            $cellLeft->addText(htmlspecialchars($c['competences_langues_raw']), $whiteSmall, ['spaceAfter' => 60]);
        }

        $cellRight = $table->addCell(7500, ['valign' => 'top']);
        $cellRight->addText($name, ['size' => 18, 'bold' => true], ['spaceAfter' => 50]);
        $cellRight->addText($roleLine, $accent, ['spaceAfter' => 300]);

        if (!empty($c['poste_actuel']) || !empty($c['experience_annees']) || !empty($c['experience_detail_raw'])) {
            $cellRight->addText('EXPÉRIENCE', $headingStyle, ['spaceAfter' => 150]);
            $cellRight->addText(htmlspecialchars($c['poste_actuel'] ?? '—'), ['size' => 11, 'bold' => true], ['spaceAfter' => 50]);
            if (!empty($c['entreprise_actuelle'])) $cellRight->addText(htmlspecialchars($c['entreprise_actuelle']), $accent, ['spaceAfter' => 50]);
            if (!empty($c['experience_annees'])) $cellRight->addText((int)$c['experience_annees'] . ' an(s) d\'expérience', $gray, ['spaceAfter' => 80]);
            if (!empty($c['experience_detail_raw'])) $cellRight->addText(htmlspecialchars($c['experience_detail_raw']), $gray, ['spaceAfter' => 200]);
        }

        if (!empty($c['education_niveau']) || !empty($c['diplome']) || !empty($c['universite'])) {
            $cellRight->addText('FORMATION', $headingStyle, ['spaceAfter' => 150]);
            $cellRight->addText(htmlspecialchars($c['diplome'] ?? $c['education_niveau'] ?? '—'), ['size' => 11, 'bold' => true], ['spaceAfter' => 50]);
            if (!empty($c['universite'])) {
                $line = $c['universite'];
                if (!empty($c['annee_diplome'])) $line .= ' · ' . $c['annee_diplome'];
                $cellRight->addText(htmlspecialchars($line), $accent, ['spaceAfter' => 200]);
            }
        }

        if (!empty($c['competences_techniques_raw'])) {
            $cellRight->addText('COMPÉTENCES TECHNIQUES', $headingStyle, ['spaceAfter' => 150]);
            $cellRight->addText(htmlspecialchars($c['competences_techniques_raw']), $gray, ['spaceAfter' => 200]);
        }

        if (!empty($c['projets_raw'])) {
            $cellRight->addText('PROJETS', $headingStyle, ['spaceAfter' => 150]);
            $cellRight->addText(htmlspecialchars($c['projets_raw']), $gray, ['spaceAfter' => 200]);
        }

        if (!empty($c['certifications_raw'])) {
            $cellRight->addText('CERTIFICATIONS', $headingStyle, ['spaceAfter' => 150]);
            $cellRight->addText(htmlspecialchars($c['certifications_raw']), $gray, ['spaceAfter' => 80]);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'cv') . '.docx';
        try {
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);
            $size = filesize($tempFile);
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            header('Cache-Control: no-store');
            header('Content-Length: ' . $size);
            readfile($tempFile);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
        exit;
    }

    private function buildCvData(array $c): array
    {
        $fullName = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
        $initials = strtoupper(mb_substr($c['prenom'] ?? ' ', 0, 1) . mb_substr(preg_replace('/\s+/', '', $c['nom'] ?? ''), 0, 1));
        if (strlen($initials) < 2) {
            $initials = strtoupper(mb_substr($fullName, 0, 2)) ?: 'CV';
        }
        $skills = [];
        if (!empty($c['competences_techniques_raw'])) {
            $skills = array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/\n]+/', $c['competences_techniques_raw']))));
        }
        $languages = [];
        if (!empty($c['competences_langues_raw'])) {
            $languages = array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/\n]+/', $c['competences_langues_raw']))));
        }
        $experience = [];
        if (!empty($c['poste_actuel']) || !empty($c['experience_detail_raw'])) {
            $experience[] = [
                'title' => $c['poste_actuel'] ?? 'Expérience professionnelle',
                'company' => trim(($c['entreprise_actuelle'] ?? '') . ' · ' . ($c['ville'] ?? '')),
                'date' => (int)($c['experience_annees'] ?? 0) . ' an(s) d\'expérience',
                'description' => $c['experience_detail_raw'] ?? '',
            ];
        }
        $education = [];
        if (!empty($c['diplome']) || !empty($c['education_niveau']) || !empty($c['universite'])) {
            $education[] = [
                'title' => $c['diplome'] ?? $c['education_niveau'] ?? 'Formation',
                'school' => $c['universite'] ?? '',
                'date' => $c['annee_diplome'] ?? '',
                'description' => '',
            ];
        }
        $projects = [];
        if (!empty($c['projets_raw'])) {
            foreach (array_filter(array_map('trim', explode("\n", $c['projets_raw']))) as $line) {
                $projects[] = ['title' => $line, 'description' => ''];
            }
        }
        if (empty($projects) && !empty($c['projets_raw'])) {
            $projects[] = ['title' => 'Projets', 'description' => $c['projets_raw']];
        }
        $certifications = [];
        if (!empty($c['certifications_raw'])) {
            foreach (array_filter(array_map('trim', preg_split('/[\n,;]+/', $c['certifications_raw']))) as $cert) {
                $certifications[] = $cert;
            }
        }
        if (empty($certifications) && !empty($c['certifications_raw'])) {
            $certifications[] = $c['certifications_raw'];
        }
        return [
            'fullName' => $fullName ?: 'Mon CV',
            'initials' => $initials,
            'role' => $c['poste_actuel'] ?? 'Candidat',
            'email' => $c['email'] ?? '',
            'telephone' => $c['telephone'] ?? '',
            'ville' => $c['ville'] ?? '',
            'experienceYears' => (int)($c['experience_annees'] ?? 0),
            'about' => $c['experience_detail_raw'] ?? '',
            'experience' => $experience,
            'education' => $education,
            'skills' => $skills,
            'languages' => $languages,
            'projects' => $projects,
            'certifications' => $certifications,
        ];
    }

    private function ensureCandidateRecord(): ?array
    {
        $user = Auth::user();
        $c = Candidate::findByUserId($user['id']);
        if ($c) {
            return $c;
        }
        $userId = Auth::id();
        Candidate::createFromRegistration($userId, [
            'nom' => '',
            'prenom' => $user['name'],
            'email' => $user['email'],
        ]);
        return Candidate::findByUserId($userId);
    }

    private function layout(string $view, array $data = []): void
    {
        extract($data);
        $user = Auth::user();
        require dirname(__DIR__) . '/Views/layouts/candidate.php';
    }

    private function flash(string $msg, string $type): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = ['message' => $msg, 'type' => $type];
    }
}
