<?php
$layoutRole = 'recruiter';
if (!isset($hero_title)) { $hero_title = 'Trouvez le <em style="color:var(--re);">meilleur profil</em> grâce à l\'IA'; }
if (!isset($hero_sub)) { $hero_sub = 'Espace Recruteur'; }
if (!isset($hero_desc)) { $hero_desc = 'Sélectionnez un poste, lancez l\'analyse et consultez le classement automatique des candidats.'; }
require __DIR__ . '/recruteia.php';
