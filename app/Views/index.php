<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - YAS Mobile</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">YAS</div>
            <p class="login-subtitle">Mobile Money</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert-message error"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert-message success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>

            <form action="<?= base_url('/auth') ?>" method="POST" class="login-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="telephone">Numéro de téléphone</label>
                    <input type="text" name="telephone" id="telephone" placeholder="033xx..." required>
                </div>

                <div class="form-group">
                    <label for="code_secret">Code Secret</label>
                    <input type="password" name="code_secret" id="code_secret" maxlength="4" pattern="[0-9]{4}" placeholder="••••" required>
                </div>

                <button type="submit" class="btn-submit">Se connecter</button>
            </form>
        </div>
    </div>
</body>
</html>
