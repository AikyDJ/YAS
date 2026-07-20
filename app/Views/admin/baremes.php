<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gérer les Barèmes de Frais - Admin</title>
</head>
<body>
    <div class="central-div">
        <div class="admin-nav-card">
            <div class="card">
                <nav class="op-nav">
                    <a href="<?= base_url('admin/dashboard') ?>" class="op-link">Vue Générale</a>
                    <a href="<?= base_url('admin/prefixes') ?>" class="op-link">Gérer les Préfixes</a>
                    <a href="<?= base_url('admin/baremes') ?>" class="op-link active">Gérer les Barèmes de Frais</a>
                    <a href="<?= base_url('admin/logout') ?>" class="op-link">Déconnexion</a>
                </nav>
            </div>
        </div>

        <div class="bareme-form-card">
            <div class="card">
                <h3>Ajouter / Modifier un Barème</h3>
                <form action="<?= base_url('admin/baremes/sauvegarder') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $bareme['id'] ?? '' ?>">

                    <div class="form-group">
                        <label for="type_operation">Type d'opération :</label>
                        <select name="type_operation" id="type_operation" required>
                            <option value="">-- Choisir --</option>
                            <option value="depot" <?= ($bareme['type_operation'] ?? '') === 'depot' ? 'selected' : '' ?>>Dépôt</option>
                            <option value="retrait" <?= ($bareme['type_operation'] ?? '') === 'retrait' ? 'selected' : '' ?>>Retrait</option>
                            <option value="transfert" <?= ($bareme['type_operation'] ?? '') === 'transfert' ? 'selected' : '' ?>>Transfert</option>
                        </select>
                    </div>

                    <h4>Tranches de montant et frais</h4>

                    <div class="tranches">
                        <div class="form-group">
                            <label>Montant min :</label>
                            <input type="number" name="montant_min_1" min="0" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Montant max :</label>
                            <input type="number" name="montant_max_1" min="0" placeholder="10000">
                        </div>
                        <div class="form-group">
                            <label>Frais (%) :</label>
                            <input type="number" name="frais_pct_1" min="0" step="0.01" placeholder="1.5">
                        </div>
                    </div>

                    <div class="tranches">
                        <div class="form-group">
                            <label>Montant min :</label>
                            <input type="number" name="montant_min_2" min="0" placeholder="10001">
                        </div>
                        <div class="form-group">
                            <label>Montant max :</label>
                            <input type="number" name="montant_max_2" min="0" placeholder="50000">
                        </div>
                        <div class="form-group">
                            <label>Frais (%) :</label>
                            <input type="number" name="frais_pct_2" min="0" step="0.01" placeholder="2.0">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Enregistrer le Barème</button>
                </form>
            </div>
        </div>

        <div class="bareme-list-card">
            <div class="card">
                <h3>Barèmes Existants</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type Opération</th>
                            <th>Tranche</th>
                            <th>Frais (%)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($baremes)): ?>
                            <?php foreach ($baremes as $i => $b): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= $b['type_operation'] ?></td>
                                    <td><?= number_format($b['montant_min'], 0, ',', ' ') ?> - <?= number_format($b['montant_max'], 0, ',', ' ') ?> Ar</td>
                                    <td><?= $b['frais_pct'] ?>%</td>
                                    <td>
                                        <a href="<?= base_url('admin/baremes/modifier/' . $b['id']) ?>">Modifier</a> |
                                        <a href="<?= base_url('admin/baremes/supprimer/' . $b['id']) ?>">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Aucun barème configuré.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
