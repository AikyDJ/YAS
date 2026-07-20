<div class="transfert-container" style="padding: 15px;">
    <h4 style="margin-top: 0; margin-bottom: 20px; color: #333;">Faire un Transfert d'argent</h4>


    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-message error" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb;">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-message success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb;">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('client/proceder-transfert') ?>" method="POST">
        <?= csrf_field() ?>


        <div class="form-group" style="margin-bottom: 15px;">
            <label for="destinataire" style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Numéro du destinataire :</label>
            <input type="text" name="destinataire" id="destinataire" placeholder="Ex: 033xx... ou 037xx..." required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
        </div>


        <div class="form-group" style="margin-bottom: 15px;">
            <label for="montant" style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Montant à envoyer :</label>
            <input type="number" name="montant" id="montant" min="1" step="any" placeholder="Montant en Ar" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
        </div>


        <div class="form-group" style="margin-bottom: 20px;">
            <label for="code_secret" style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Code Secret (4 chiffres) :</label>
            <input type="password" name="code_secret" id="code_secret" maxlength="4" pattern="[0-9]{4}" placeholder="••••" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; letter-spacing: 5px; text-align: center;">
        </div>


        <button type="submit" class="btn-submit" style="width: 100%; background-color: #28a745; color: white; border: none; padding: 12px; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.2s;">
            Confirmer le transfert
        </button>
    </form>
</div>
