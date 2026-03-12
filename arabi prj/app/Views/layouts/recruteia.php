<?php
/**
 * RecruteIA — Shared App Layout
 * Expects: $layoutRole ('admin'|'recruiter'|'candidate'), $view, $title, $user
 * Optional: $hero_title, $hero_sub, $hero_desc, $hero_stats (array)
 */
$req     = $_SERVER['REQUEST_URI'] ?? '/';
$reqPath = parse_url($req, PHP_URL_PATH) ?: '/';

$roleConfig = [
    'admin' => [
        'badge' => 'Admin',
        'bg'    => 'var(--ad)',
        'color' => 'ad',
        'nav'   => [
            ['url' => '/admin/stats',      'label' => 'Tableau de bord'],
            ['url' => '/admin/users',      'label' => 'Utilisateurs'],
            ['url' => '/admin/import-csv', 'label' => 'Données & CSV'],
            ['url' => '/admin/jobs',       'label' => 'Postes'],
        ],
    ],
    'recruiter' => [
        'badge' => 'Recruteur',
        'bg'    => 'var(--re)',
        'color' => 're',
        'nav'   => [
            ['url' => '/recruiter/recommendations', 'label' => 'Recommandations IA'],
            ['url' => '/recruiter/jobs',             'label' => 'Postes'],
            ['url' => '/recruiter/candidates',       'label' => 'Candidats'],
            ['url' => '/recruiter/stats',            'label' => 'Statistiques'],
        ],
    ],
    'candidate' => [
        'badge' => 'Candidat',
        'bg'    => 'var(--ca)',
        'color' => 'ca',
        'nav'   => [
            ['url' => '/candidate/profile',      'label' => 'Mon Profil'],
            ['url' => '/candidate/jobs',         'label' => 'Offres'],
            ['url' => '/candidate/applications', 'label' => 'Mes candidatures'],
            ['url' => '/candidate/results',      'label' => 'Résultats IA'],
        ],
    ],
];

$cfg = $roleConfig[$layoutRole] ?? $roleConfig['candidate'];
$avatarInit = strtoupper(mb_substr($user['name'] ?? 'U', 0, 1) . mb_substr(preg_replace('/\s+/', '', $user['name'] ?? ''), 1, 1));
if (strlen($avatarInit) < 2) $avatarInit = strtoupper($avatarInit ?: 'U') . 'X';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '') ?> — RecruteIA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="/css/recruteia.css?v=<?= time() ?>" rel="stylesheet">
    <?php if ($layoutRole === 'candidate') { ?><link href="/css/candidate-profile.css" rel="stylesheet"><?php } ?>
    <?php if (!empty($extraCss)) { ?><link href="<?= htmlspecialchars($extraCss) ?>" rel="stylesheet"><?php } ?>
</head>
<body class="app-layout">

<nav class="topnav" id="navbar">
    <a href="<?= $layoutRole === 'admin' ? '/admin/stats' : ($layoutRole === 'recruiter' ? '/recruiter/jobs' : '/candidate/profile') ?>" class="logo">
        Recrute<span class="logo-dot">IA</span>
        <span class="role-badge" style="background:<?= $cfg['bg'] ?>;"><?= htmlspecialchars($cfg['badge']) ?></span>
    </a>
    <div class="nav-links">
        <?php foreach ($cfg['nav'] as $n):
            $navPath = parse_url($n['url'], PHP_URL_PATH) ?: '/';
            $active  = ($reqPath === $navPath) || str_starts_with($reqPath, rtrim($navPath, '/') . '/');
        ?>
        <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= htmlspecialchars($n['url']) ?>"><?= htmlspecialchars($n['label']) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="nav-right">
        <a class="btn btn-ghost" href="/logout" style="font-size:12px;padding:6px 13px;">Déconnexion</a>
        <div class="avatar-sm" style="background:linear-gradient(135deg,<?= $cfg['bg'] ?>,#22c07a);" title="<?= htmlspecialchars($user['name'] ?? '') ?>"><?= htmlspecialchars($avatarInit) ?></div>
    </div>
</nav>

<?php if (!empty($hero_title)): ?>
<div class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <?php if (!empty($hero_sub)): ?>
            <div class="hero-greeting"><?= htmlspecialchars($hero_sub) ?></div>
            <?php endif; ?>
            <h1 class="hero-title"><?= $hero_title ?></h1>
            <?php if (!empty($hero_desc)): ?>
            <p class="hero-sub"><?= htmlspecialchars($hero_desc) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($hero_stats) && is_array($hero_stats)): ?>
        <div class="hero-right">
            <?php foreach ($hero_stats as $i => $hs): ?>
            <div class="hstat" style="<?= $i === 0 ? 'border-top-color:var(--' . $cfg['color'] . ');' : '' ?>">
                <div class="hstat-v" style="<?= $i === 0 ? 'color:var(--' . $cfg['color'] . ');' : '' ?>"><?= htmlspecialchars($hs['value']) ?></div>
                <div class="hstat-l"><?= htmlspecialchars($hs['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="page">
    <?php if (!empty($_SESSION['flash'])):
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $isSuccess = ($f['type'] ?? '') === 'success';
    ?>
    <div class="alert-recruteia alert-<?= $f['type'] === 'success' ? 'success' : ($f['type'] === 'danger' ? 'danger' : 'info') ?><?= $isSuccess ? ' alert-confirm' : '' ?>">
        <?= $isSuccess ? '✓ ' : '' ?><?= htmlspecialchars($f['message']) ?>
    </div>
    <?php endif; ?>

    <?php include dirname(__DIR__) . '/' . $view . '.php'; ?>
</div>

<script>
/* ─── NAV SCROLL ─── */
const navbar = document.getElementById('navbar');
if (navbar) {
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 40);
    });
}
</script>

</body>
</html>
