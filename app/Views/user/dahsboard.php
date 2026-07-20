<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YAS Mobile</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <div class="central-div">
        <div class="top-bar">
            <div class="client-info">
                <span class="client-name"><?= esc($prenom) ?> <?= esc($nom) ?></span>
                <span class="client-code"><?= esc($code_client) ?></span>
            </div>
            <a href="<?= base_url('client/logout') ?>" class="btn-logout">Déconnexion</a>
        </div>

        <div class="sold-card">
            <div class="main-container">
                <div class="border">
                    <div class="card-inner">
                        <div class="shadow">
                            <div class="content">
                                <p class="rev">YAS Mobile</p>
                                <svg version="1.1" class="chip" xmlns="http://www.w3.org/2000/svg" width="40px" height="40px" viewBox="0 0 50 50">
                                    <image width="50" height="50" x="0" y="0" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAMAAAAp4XiDAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAB6VBMVEUAAACNcTiVeUKVeUOYfEaafEeUeUSYfEWZfEaykleyklaXe0SWekSZZjOYfEWYe0WXfUWXe0WcgEicfkiXe0SVekSXekSWekKYe0a9nF67m12ZfUWUeEaXfESVekOdgEmVeUWWekSniU+VeUKVeUOrjFKYfEWliE6WeESZe0GSe0WYfES7ml2Xe0WXeESUeEOWfEWcf0eWfESXe0SXfEWYekSVeUKXfEWxklawkVaZfEWWekOUekOWekSYfESZe0eXekWYfEWZe0WZe0eVeUSWeETAnmDCoWLJpmbxy4P1zoXwyoLIpWbjvXjivnjgu3bfu3beunWvkFWxkle/nmDivXiWekTnwXvkwHrCoWOuj1SXe0TEo2TDo2PlwHratnKZfEbQrWvPrWuafUfbt3PJp2agg0v0zYX0zYSfgkvKp2frxX7mwHrlv3rsxn/yzIPgvHfduXWXe0XuyIDzzISsjVO1lVm0lFitjVPzzIPqxX7duna0lVncuHTLqGjvyIHeuXXxyYGZfUayk1iyk1e2lln1zYTEomO2llrbtnOafkjFpGSbfkfZtXLhvHfkv3nqxH3mwXujhU3KqWizlFilh06khk2fgkqsjlPHpWXJp2erjVOhg0yWe0SliE+XekShhEvAn2D///+gx8TWAAAARnRSTlMACVCTtsRl7Pv7+vxkBab7pZv5+ZlL/UnU/f3SJCVe+Fx39naA9/75XSMh0/3SSkia+pil/KRj7Pr662JPkrbP7OLQ0JFOijI1MwAAAAFiS0dEorDd34wAAAAJcEhZcwAACxMAAAsTAQCanBgAAAAHdElNRQfnAg0IDx2lsiuJAAACLElEQVRIx2NgGAXkAUYmZhZWPICFmYkRVQcbOwenmzse4MbFzc6DpIGXj8PD04sA8PbhF+CFaxEU8iWkAQT8hEVgOkTF/InR4eUVICYO1SIhCRMLDAoKDvFDVhUaEhwUFAjjSUlDdMiEhcOEItzdI6OiYxA6YqODIt3dI2DcuDBZsBY5eVTr4xMSYcyk5BRUOXkFsBZFJTQnp6alQxgZmVloUkrKYC0qqmji2WE5EEZuWB6alKoKdi35YQUQRkFYPpFaCouKIYzi6EDitJSUlsGY5RWVRGjJLyxNy4ZxqtIqqvOxaVELQwZFZdkIJVU1RSiSalAt6rUwUBdWG1CP6pT6gNqwOrgCdQyHNYR5YQFhDXj8MiK1IAeyN6aORiyBjByVTc0FqBoKWpqwRCVSgilOaY2OaUPw29qjOzqLvTAchpos47u6EZyYnngUSRwpuTe6D+6qaFQdOPNLRzOM1dzhRZyW+CZouHk3dWLXglFcFIflQhj9YWjJGlZcaKAVSvjyPrRQ0oQVKDAQHlYFYUwIm4gqExGmBSkutaVQJeomwViTJqPK6OhCy2Q9sQBk8cY0DxjTJw0lAQWK6cOKfgNhpKK7ZMpUeF3jPa28BCETamiEqJKM+X1gxvWXpoUjVIVPnwErw71nmpgiqiQGBjNzbgs3j1nus+fMndc+Cwm0T52/oNR9lsdCS24ra7Tq1cbWjpXV3sHRCb1idXZ0sGdltXNxRateRwHRAACYHutzk/2I5QAAACV0RVh0ZGF0ZTpjcmVhdGUAMjAyMy0wMi0xM1QwODoxNToyOSswMDowMEUnN7UAAAAldEVYdGRhdGU6bW9kaWZ5ADIwMjMtMDItMTNUMDg6MTU6MjkrMDA6MDA0eo8JAAAAKHRFWHRkYXRlOnRpbWVzdGFtcAAyMDIzLTAyLTEzVDA4OjE1OjI5KzAwOjAwY2+u1gAAAABJRU5ErkJggg=="></image>
                                </svg>
                                <p class="solde-amount"><?= number_format($solde, 2, ',', ' ') ?> <?= $monnaie ?></p>
                                <p class="master-text">SOLDE ACTUEL</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="operation-card">
            <div class="card">
                <div class="op-nav">
                    <a href="?operation=depot/retrait" class="op-link <?= (!isset($_GET['operation']) || $_GET['operation'] === 'depot/retrait') ? 'active' : '' ?>">Dépôt / Retrait</a>
                    <a href="?operation=transfert" class="op-link <?= (isset($_GET['operation']) && $_GET['operation'] === 'transfert') ? 'active' : '' ?>">Transfert</a>
                </div>
                <div class="op-content">
                    <?php
                        $op = $_GET['operation'] ?? 'depot/retrait';
                        if ($op === 'transfert') {
                            echo view('user/transfert_op', ['frais' => $frais ?? []]);
                        } else {
                            echo view('user/depot_retrait', ['frais' => $frais ?? []]);
                        }
                    ?>
                </div>
            </div>
        </div>

        <div class="history-card">
            <div class="card">
                <h3>Historique</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Solde</th>
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
                            <td colspan="4">Aucune opération</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    const FRAIS = <?= json_encode($frais ?? []) ?>;
    </script>
    <script src="<?= base_url('js/app.js') ?>"></script>
</body>
</html>
