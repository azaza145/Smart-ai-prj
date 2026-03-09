<?php
$layoutRole = 'candidate';
if (!isset($hero_title)) { $hero_title = 'Complétez votre profil pour <em style="color:var(--ca);">maximiser</em> vos chances'; }
if (!isset($hero_sub)) { $hero_sub = 'Bienvenue'; }
if (!isset($hero_desc)) { $hero_desc = 'Notre IA analyse votre profil et le compare aux postes disponibles. Un profil complet améliore votre score.'; }
require __DIR__ . '/recruteia.php';
