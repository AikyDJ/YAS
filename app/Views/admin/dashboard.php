<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Admin - YAS Mobile</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <div class="central-div">
        <div class="admin-nav-card">
            <div class="card">
                <nav class="op-nav">
                    <a href="<?= base_url('admin/dashboard') ?>" class="op-link active">Vue Générale</a>
                    <a href="<?= base_url('admin/prefixes') ?>" class="op-link">Préfixes</a>
                    <a href="<?= base_url('admin/baremes') ?>" class="op-link">Barèmes</a>
                    <a href="<?= base_url('admin/logout') ?>" class="op-link op-link-logout">Déconnexion</a>
                </nav>
            </div>
        </div>

        <div class="grid-row grid-3">
            <div class="stat-card stat-blue">
                <div class="stat-icon">&#128100;</div>
                <div class="stat-value"><?= $total_comptes ?? 0 ?></div>
                <div class="stat-label">Comptes Clients</div>
            </div>
            <div class="stat-card stat-purple">
                <div class="stat-icon">&#128200;</div>
                <div class="stat-value"><?= $nb_operations ?? 0 ?></div>
                <div class="stat-label">Opérations</div>
            </div>
            <div class="stat-card stat-gold">
                <div class="stat-icon">&#128176;</div>
                <div class="stat-value"><?= number_format(($gains_retrait ?? 0) + ($gains_transfert ?? 0), 0, ',', ' ') ?></div>
                <div class="stat-label">Gains Total (Ar)</div>
            </div>
        </div>

        <div class="grid-row grid-2">
            <div class="gains-card">
                <div class="card">
                    <h3>Détail des Gains</h3>
                    <div class="gain-row">
                        <span class="gain-label">Gains Retraits</span>
                        <span class="gain-value gain-green"><?= number_format($gains_retrait ?? 0, 0, ',', ' ') ?> Ar</span>
                    </div>
                    <div class="gain-row">
                        <span class="gain-label">Gains Transferts</span>
                        <span class="gain-value gain-blue"><?= number_format($gains_transfert ?? 0, 0, ',', ' ') ?> Ar</span>
                    </div>
                    <div class="gain-row gain-total">
                        <span class="gain-label">Total Opérateur</span>
                        <span class="gain-value gain-gold"><?= number_format(($gains_retrait ?? 0) + ($gains_transfert ?? 0), 0, ',', ' ') ?> Ar</span>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="card">
                    <h3>Préfixes Réseau</h3>
                    <?php if (!empty($prefixes)): ?>
                        <div class="prefix-chips">
                            <?php foreach ($prefixes as $p): ?>
                                <span class="prefix-chip"><?= $p['prefixe'] ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-text">Aucun préfixe configuré.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="comptes-card">
            <div class="card">
                <h3>Comptes Clients</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Téléphone</th>
                            <th>Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($comptes)): ?>
                            <?php foreach ($comptes as $c): ?>
                                <tr>
                                    <td><?= $c['telephone'] ?></td>
                                    <td class="td-solde"><?= number_format($c['solde'], 0, ',', ' ') ?> Ar</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">Aucun client enregistré.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
