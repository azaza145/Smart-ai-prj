<?php
$layoutRole = 'admin';
if (isset($view) && $view === 'admin/stats' && isset($stats)) {
    $hero_title = 'Gérez la plateforme <em style="color:var(--ad);">RecruteIA</em>';
    $hero_sub = 'Administration système';
    $hero_desc = 'Importez des CV, gérez les utilisateurs, configurez les postes et supervisez le pipeline IA.';
    $hero_stats = [
        ['value' => (string)($stats['candidates'] ?? 0), 'label' => 'Candidats'],
        ['value' => (string)($stats['jobs'] ?? 0), 'label' => 'Postes'],
        ['value' => (string)($stats['users'] ?? 0), 'label' => 'Utilisateurs'],
    ];
    $hero_color = '--ad';
}
require __DIR__ . '/recruteia.php';
