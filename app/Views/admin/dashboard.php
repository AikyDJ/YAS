<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Espace Admin - Opérateur Mobile Money</title>
</head>
<body>
    <div class="central-div">
        <div class="admin-nav-card">
            <div class="card">
                <nav class="op-nav">
                    <a href="<?= base_url('admin/dashboard') ?>" class="op-link active">Vue Générale</a>
                    <a href="<?= base_url('admin/prefixes') ?>" class="op-link">Gérer les Préfixes</a>
                    <a href="<?= base_url('admin/baremes') ?>" class="op-link">Gérer les Barèmes de Frais</a>
                    <a href="<?= base_url('admin/logout') ?>" class="op-link">Déconnexion</a>
                </nav>
            </div>
        </div>

        <div class="info-card">
            <div class="card">
                <h3>Informations Réseau</h3>
                <p>Nombre total de comptes : <?= $total_comptes ?? 0 ?></p>

                <p>Liste de tous les préfixes valides :</p>
                <?php if (!empty($prefixes)): ?>
                    <ul>
                        <?php foreach ($prefixes as $p): ?>
                            <li><?= $p['prefixe'] ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Aucun préfixe configuré.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="gains-card">
            <div class="card">
                <h3>Situation des Gains</h3>
                <p>Gains sur Retraits : <?= number_format($gains_retrait ?? 0, 2, ',', ' ') ?> Ar</p>
                <p>Gains sur Transferts : <?= number_format($gains_transfert ?? 0, 2, ',', ' ') ?> Ar</p>
                <p>Gain Total de l'Opérateur : <?= number_format(($gains_retrait ?? 0) + ($gains_transfert ?? 0), 2, ',', ' ') ?> Ar</p>
            </div>
        </div>

        <div class="comptes-card">
            <div class="card">
                <h3>Situation des Comptes Clients</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Numéro de Téléphone</th>
                            <th>Solde Actuel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($comptes)): ?>
                            <?php foreach ($comptes as $c): ?>
                                <tr>
                                    <td><?= $c['telephone'] ?></td>
                                    <td><?= number_format($c['solde'], 2, ',', ' ') ?> Ar</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">Aucun client enregistré pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
