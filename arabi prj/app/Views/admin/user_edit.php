<?php if (!empty($errors)) { ?><div class="alert-recruteia alert-danger" style="margin-bottom:16px;"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php } ?>

<div class="card" style="max-width:500px;">
    <div class="card-header"><div class="card-title"><div class="ci" style="background:var(--ad-l);">✏️</div>Modifier l'utilisateur</div></div>
    <div class="card-body">
        <form method="post" action="/admin/users/<?= (int)$user['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="fg">
                <div class="field"><label>Nom</label><input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? $user['name']) ?>" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $user['email']) ?>" required></div>
                <div class="field"><label>Rôle</label>
                    <select name="role">
                        <option value="admin" <?= ($old['role'] ?? $user['role']) === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="recruiter" <?= ($old['role'] ?? $user['role']) === 'recruiter' ? 'selected' : '' ?>>Recruteur</option>
                        <option value="candidate" <?= ($old['role'] ?? $user['role']) === 'candidate' ? 'selected' : '' ?>>Candidat</option>
                    </select>
                </div>
                <div class="field"><label>Statut</label>
                    <select name="status">
                        <option value="active" <?= ($old['status'] ?? $user['status']) === 'active' ? 'selected' : '' ?>>Actif</option>
                        <option value="disabled" <?= ($old['status'] ?? $user['status']) === 'disabled' ? 'selected' : '' ?>>Désactivé</option>
                    </select>
                </div>
                <div class="field ff"><label>Nouveau mot de passe (laisser vide pour conserver)</label><input type="password" name="password" placeholder="Optionnel"></div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="submit" class="btn btn-ad">Enregistrer</button>
                <a href="/admin/users" class="btn btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</div>
