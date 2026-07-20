<div class="depot-retrait-container" style="padding: 15px;">
    <h4 style="margin-top: 0; margin-bottom: 20px; color: #333;">Faire un Dépôt / Retrait</h4>


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


    <form action="<?= base_url('client/proceder-operation') ?>" method="POST">
        <?= csrf_field() ?>


        <div class="form-group" style="margin-bottom: 15px;">
            <label for="type_operation" style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Action :</label>
            <select name="type_operation" id="type_operation" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                <option value="depot">Dépôt</option>
                <option value="retrait">Retrait</option>
            </select>
        </div>


        <div class="form-group" style="margin-bottom: 20px;">
            <label for="montant" style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Montant à déplacer :</label>
            <input type="number" name="montant" id="montant" min="1" step="any" placeholder="Ex d'équivalent : 5000" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
        </div>


        <div class="form-group" style="margin-bottom: 20px;">
            <label for="code_secret" style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Code Secret (4 chiffres) :</label>
            <input type="password" name="code_secret" id="code_secret" maxlength="4" pattern="[0-9]{4}" placeholder="••••" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; letter-spacing: 5px; text-align: center;">
        </div>

        <button type="submit" class="btn-submit" style="width: 100%; background-color: #007bff; color: white; border: none; padding: 12px; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.2s;">
            Confirmer l'opération
        </button>
    </form>
</div>
