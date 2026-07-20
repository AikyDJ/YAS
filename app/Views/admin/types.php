<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Types d'opérations - Admin</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <div class="central-div">
        <div class="admin-nav-card">
            <div class="card">
                <nav class="op-nav">
                    <a href="<?= base_url('admin/dashboard') ?>" class="op-link">Vue Générale</a>
                    <a href="<?= base_url('admin/prefixes') ?>" class="op-link">Préfixes</a>
                    <a href="<?= base_url('admin/types') ?>" class="op-link active">Types</a>
                    <a href="<?= base_url('admin/baremes') ?>" class="op-link">Barèmes</a>
                    <a href="<?= base_url('admin/logout') ?>" class="op-link op-link-logout">Déconnexion</a>
                </nav>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert-message success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert-message error"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <div class="grid-row grid-4-6">
            <div class="prefixe-card">
                <div class="card">
                    <h3>Ajouter un Type</h3>
                    <form action="<?= base_url('admin/types/ajouter') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" name="nom" id="nom" placeholder="depot" required>
                        </div>
                        <div class="form-group">
                            <label for="code_type_operation">Code</label>
                            <input type="number" name="code_type_operation" id="code_type_operation" min="1" required>
                        </div>
                        <button type="submit" class="btn-submit btn-depot">Ajouter</button>
                    </form>
                </div>
            </div>

            <div class="prefixe-list-card">
                <div class="card">
                    <h3>Types Configurés</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($types)): ?>
                                <?php foreach ($types as $i => $type): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= esc(ucfirst($type['nom'])) ?></td>
                                        <td><?= esc($type['code_type_operation']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">Aucun type configuré.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>