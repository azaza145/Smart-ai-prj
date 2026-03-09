<?php
/**
 * Vérifie que l’étape AI du parsing CV est configurée (Ollama ou autre).
 * À lancer depuis la racine : php scripts/check_cv_ai.php
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    \App\Core\DB::loadEnv($envPath);
}
\App\Core\DB::loadEnv(dirname(__DIR__) . '/env.example');

$enabled = getenv('CV_AI_ENABLED');
$url = getenv('CV_LLM_API_URL');
$model = getenv('CV_LLM_MODEL');

echo "CV_AI_ENABLED = " . ($enabled ?: '(non défini)') . "\n";
echo "CV_LLM_API_URL = " . ($url ?: '(non défini)') . "\n";
echo "CV_LLM_MODEL  = " . ($model ?: '(non défini)') . "\n";

if (!$url || $enabled === '0' || strtolower($enabled) === 'false') {
    echo "\n→ L’étape AI est désactivée ou non configurée. Pour activer Ollama :\n";
    echo "  Vérifier dans .env ou env.example : CV_AI_ENABLED=1, CV_LLM_API_URL=http://localhost:11434/v1, CV_LLM_MODEL=llama3.2\n";
    exit(0);
}

echo "\n→ L’étape AI est activée. Pensez à redémarrer le serveur PHP après toute modification de .env.\n";
echo "  Test : ouvrir une fiche candidat avec CV puis cliquer « Extraire et pré-remplir le profil à partir du CV ».\n";
exit(0);
