<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gérer les Préfixes - Admin</title>
</head>
<body>
    <div class="central-div">
        <div class="admin-nav-card">
            <div class="card">
                <nav class="op-nav">
                    <a href="<?= base_url('admin/dashboard') ?>" class="op-link">Vue Générale</a>
                    <a href="<?= base_url('admin/prefixes') ?>" class="op-link active">Gérer les Préfixes</a>
                    <a href="<?= base_url('admin/baremes') ?>" class="op-link">Gérer les Barèmes de Frais</a>
                    <a href="<?= base_url('admin/logout') ?>" class="op-link">Déconnexion</a>
                </nav>
            </div>
        </div>

        <div class="prefixe-card">
            <div class="card">
                <h3>Ajouter un Préfixe</h3>
                <form action="<?= base_url('admin/prefixes/ajouter') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="prefixe">Préfixe valide :</label>
                        <input type="text" name="prefixe" id="prefixe" placeholder="Ex: 033" pattern="[0-9]{3}" maxlength="3" required>
                    </div>
                    <button type="submit" class="btn-submit">Ajouter</button>
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
                                    <td><?= $p['prefixe'] ?></td>
                                    <td>
                                        <a href="<?= base_url('admin/prefixes/supprimer/' . $p['id']) ?>">Supprimer</a>
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
</body>
</html>
