<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Nouveau mot de passe') ?> — RecruteIA</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/recruteia.css" rel="stylesheet">
    <style>.auth-page{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;}.auth-card{background:var(--white);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow-md);max-width:400px;width:100%;padding:32px 28px;}.auth-card h1{font-family:var(--font-h);font-size:22px;font-weight:700;margin-bottom:6px;}.auth-card .sub{font-size:13px;color:var(--muted);margin-bottom:24px;}.auth-card .field{margin-bottom:16px;}.auth-card .btn{width:100%;justify-content:center;padding:11px;margin-top:8px;}.auth-card .link{font-size:13px;text-align:center;margin-top:20px;}.auth-card .link a{color:var(--ca);font-weight:600;text-decoration:none;}.auth-card .link a:hover{text-decoration:underline;}.auth-logo{margin-bottom:28px;font-family:var(--font-h);font-size:24px;font-weight:700;color:var(--text);}.auth-logo span{color:var(--ca);}</style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <a href="/" class="auth-logo" style="display:block;text-decoration:none;">Recrute<span>IA</span></a>
        <h1>Nouveau mot de passe</h1>
        <p class="sub">Choisissez un mot de passe d'au moins 6 caractères.</p>
        <?php if (!empty($errors)) { ?>
            <div class="alert-recruteia alert-danger"><?= htmlspecialchars(is_array($errors) ? implode(' ', $errors) : $errors) ?></div>
        <?php } ?>
        <form method="post" action="/reset-password">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
            <div class="field">
                <label>Nouveau mot de passe</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="field">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="password_confirm" required minlength="6">
            </div>
            <button type="submit" class="btn btn-ca">Réinitialiser le mot de passe</button>
        </form>
        <p class="link"><a href="/login">← Retour à la connexion</a></p>
    </div>
</div>
</body>
</html>
