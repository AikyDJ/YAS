<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préfixes - Admin</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <div class="central-div">
        <div class="admin-nav-card">
            <div class="card">
                <nav class="op-nav">
                    <a href="<?= base_url('admin/dashboard') ?>" class="op-link">Vue Générale</a>
                    <a href="<?= base_url('admin/prefixes') ?>" class="op-link active">Préfixes</a>
                    <a href="<?= base_url('admin/baremes') ?>" class="op-link">Barèmes</a>
                    <a href="<?= base_url('admin/logout') ?>" class="op-link op-link-logout">Déconnexion</a>
                </nav>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert-message success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <div class="grid-row grid-4-6">
            <div class="prefixe-card">
                <div class="card">
                    <h3>Ajouter un Préfixe</h3>
                    <form action="<?= base_url('admin/prefixes/ajouter') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="prefixe">Préfixe (3 chiffres)</label>
                            <input type="text" name="prefixe" id="prefixe" placeholder="033" pattern="[0-9]{3}" maxlength="3" required>
                            <span class="field-status" id="prefix-status"></span>
                        </div>
                        <button type="submit" class="btn-submit btn-depot">Ajouter</button>
                    </form>
                </div>
            </div>

            <div class="prefixe-list-card">
                <div class="card">
                    <h3>Préfixes Configurés</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Préfixe</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($prefixes)): ?>
                                <?php foreach ($prefixes as $i => $p): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="td-prefix"><?= $p['prefix'] ?></td>
                                        <td>
                                            <form action="<?= base_url('admin/prefixes/supprimer') ?>" method="POST" class="inline-form" onsubmit="return confirm('Supprimer le préfixe <?= $p['prefix'] ?> ?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn-delete">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">Aucun préfixe configuré.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    const prefixInput = document.getElementById('prefix');
    const prefixStatus = document.getElementById('prefix-status');
    if (prefixInput && prefixStatus) {
        prefixInput.addEventListener('input', function() {
            const v = this.value.trim();
            if (v.length === 0) { prefixStatus.textContent = ''; prefixStatus.className = 'field-status'; return; }
            if (/^\d{3}$/.test(v)) {
                prefixStatus.textContent = 'Format valide';
                prefixStatus.className = 'field-status valid';
            } else {
                prefixStatus.textContent = '3 chiffres requis';
                prefixStatus.className = 'field-status invalid';
            }
        });
    }
    </script>
</body>
</html>
