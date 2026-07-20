<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HomePage</title>
</head>
<body>
    <div class="central-div">
        <div class="client-div">
            <div class="sold-card">
                <div class="card">
                    <h3>Solde actuel</h3>
                    <p class="solde">
                        <?php
                        echo number_format($solde, 2, ',', ' ') . ' ' . $monnaie;
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="operation-card">
            <div class="op-nav">
                <a href="?operation=depot/retrait" class="op-link <?= (!isset($_GET['operation']) || $_GET['operation'] === 'depot/retrait') ? 'active' : '' ?>">Dépôt / Retrait</a>
                <a href="?operation=transfert" class="op-link <?= (isset($_GET['operation']) && $_GET['operation'] === 'transfert') ? 'active' : '' ?>">Transfert</a>
            </div>
            <div class="op-content">
                <?php
                    $op = $_GET['operation'] ?? 'depot/retrait';
                    if ($op === 'transfert') {
                        echo view('user/transfert_op');
                    } else {
                        echo view('user/depot_retrait');
                    }
                ?>
            </div>
        </div>
        <div class="history-card">
            <div class="card">
                <h3>Historique des opérations</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type d'opération</th>
                            <th>Montant</th>
                            <th>Solde du jour</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (!empty($operations)):
                                foreach ($operations as $row):
                        ?>
                        <tr>
                            <td><?= $row['date'] ?></td>
                            <td><?= $row['type'] ?></td>
                            <td><?= number_format($row['montant'], 2, ',', ' ') ?></td>
                            <td><?= number_format($row['solde_jour'], 2, ',', ' ') ?></td>
                        </tr>
                        <?php
                                endforeach;
                            else:
                        ?>
                        <tr>
                            <td colspan="4">Aucune opération enregistrée</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
