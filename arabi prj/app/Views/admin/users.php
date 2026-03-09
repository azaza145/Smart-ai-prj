<?php if (!empty($_SESSION['form_errors'])):
    $err = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
?>
<div class="alert-recruteia alert-danger"><?= htmlspecialchars(implode(' ', $err)) ?></div>
<?php endif; ?>

<!-- Add user -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ad-l);">➕</div>Ajouter un utilisateur</div>
    </div>
    <div class="card-body">
        <form method="post" action="/admin/users">
            <?= \App\Core\Csrf::field() ?>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 140px auto;gap:12px;align-items:flex-end;">
                <div class="field"><label>Nom</label><input type="text" name="name" placeholder="Nom complet" value="<?= htmlspecialchars($_SESSION['old']['name'] ?? '') ?>" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" placeholder="email@exemple.com" value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>" required></div>
                <div class="field"><label>Mot de passe</label><input type="password" name="password" placeholder="••••••••" required></div>
                <div class="field"><label>Rôle</label>
                    <select name="role">
                        <option value="recruiter">Recruteur</option>
                        <option value="admin">Admin</option>
                        <option value="candidate">Candidat</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-ad" style="align-self:flex-end;">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- Users table -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ad-l);">👥</div>Tous les utilisateurs</div>
        <span style="font-size:11px;color:var(--muted);"><?= count($users) ?> utilisateur(s)</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Inscrit le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $init = strtoupper(mb_substr($u['name'], 0, 1) . mb_substr(preg_replace('/\s+/', '', $u['name']), 1, 1));
                    $roleClass = $u['role'] === 'admin' ? 'tp' : ($u['role'] === 'recruiter' ? 'tb' : 'tg');
                    $statusClass = ($u['status'] ?? 'active') === 'active' ? 'tg' : 'ta';
                ?>
                <tr>
                    <td>
                        <div class="u-info">
                            <div class="u-av" style="background:var(--ad);"><?= htmlspecialchars($init) ?></div>
                            <div class="u-name"><?= htmlspecialchars($u['name']) ?></div>
                        </div>
                    </td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="tag <?= $roleClass ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td><span class="tag <?= $statusClass ?>"><?= htmlspecialchars($u['status'] ?? 'active') ?></span></td>
                    <td style="font-size:12px;color:var(--muted);"><?= !empty($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '—' ?></td>
                    <td>
                        <div class="abtns">
                            <a href="/admin/users/<?= (int)$u['id'] ?>" class="btn btn-ghost btn-sm">Modifier</a>
                            <?php if ($u['role'] !== 'admin'): ?>
                            <form method="post" action="/admin/users/<?= (int)$u['id'] ?>/toggle" style="display:inline;">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn <?= ($u['status'] ?? 'active') === 'active' ? 'btn-outline-danger' : 'btn-outline-ca' ?> btn-sm">
                                    <?= ($u['status'] ?? 'active') === 'active' ? 'Suspendre' : 'Activer' ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">Aucun utilisateur.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
