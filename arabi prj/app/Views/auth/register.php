<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Inscription') ?> — RecruteIA</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link href="/css/recruteia.css" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="animation: fadeUp .5s ease both;">
        <a href="/" class="auth-logo">Recrute<span>IA</span></a>
        <h1>Créer un compte</h1>
        <p class="sub">Rejoignez RecruteIA en tant que candidat</p>

        <?php if (!empty($errors)) { ?>
        <div class="alert-recruteia alert-danger"><?= htmlspecialchars(is_array($errors) ? implode(' ', $errors) : $errors) ?></div>
        <?php } ?>

        <form method="post" action="/register">
            <?= \App\Core\Csrf::field() ?>
            <div class="field">
                <label>Nom complet</label>
                <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" placeholder="Prénom Nom" required autofocus>
            </div>
            <div class="field">
                <label>Adresse email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="vous@exemple.com" required>
            </div>
            <div class="field">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="6 caractères minimum" required minlength="6">
            </div>
            <button type="submit" class="btn btn-ca">Créer mon compte →</button>
        </form>

        <div class="sd">ou</div>
        <p class="link">Déjà un compte ? <a href="/login">Se connecter</a></p>
    </div>
</div>
</body>
</html>
