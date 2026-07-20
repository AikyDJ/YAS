<div class="transfert-container">
    <h4>Transfert d'argent</h4>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-message error">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-message success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('client/proceder-transfert') ?>" method="POST" id="form-transfert">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="destinataire">Numéro du destinataire</label>
            <input type="text" name="destinataire" id="destinataire" placeholder="033xx... ou 037xx..." required>
            <span class="dest-status" id="dest-status"></span>
        </div>

        <div class="form-group">
            <label for="montant_t">Montant à envoyer (Ar)</label>
            <input type="number" name="montant" id="montant_t" min="1" step="any" placeholder="10 000" required>
        </div>

        <div class="fee-preview" id="fee-preview-t" style="display:none;">
            <div class="fee-row">
                <span>Montant</span>
                <span id="fee-montant-t">0 Ar</span>
            </div>
            <div class="fee-row">
                <span>Frais (<span id="fee-pct-t">0</span>%)</span>
                <span id="fee-amount-t" class="fee-cost">0 Ar</span>
            </div>
            <div class="fee-row fee-total">
                <span>Total débité</span>
                <span id="fee-total-t">0 Ar</span>
            </div>
        </div>

        <div class="form-group">
            <label for="code_secret_t">Code Secret</label>
            <input type="password" name="code_secret" id="code_secret_t" maxlength="4" pattern="[0-9]{4}" placeholder="••••" required>
        </div>

        <button type="submit" class="btn-submit btn-transfert">Confirmer le transfert</button>
    </form>
</div>
