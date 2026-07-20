<div class="depot-retrait-container">
    <h4>Dépôt / Retrait</h4>

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

    <form action="<?= base_url('client/proceder-operation') ?>" method="POST" id="form-operation">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="type_operation">Action</label>
            <select name="type_operation" id="type_operation" required>
                <option value="depot">Dépôt</option>
                <option value="retrait">Retrait</option>
            </select>
        </div>

        <div class="form-group">
            <label for="montant">Montant (Ar)</label>
            <input type="number" name="montant" id="montant" min="1" step="any" placeholder="5 000" required>
        </div>

        <div class="fee-preview" id="fee-preview" style="display:none;">
            <div class="fee-row">
                <span>Montant</span>
                <span id="fee-montant">0 Ar</span>
            </div>
            <div class="fee-row">
                <span>Frais (<span id="fee-pct">0</span>%)</span>
                <span id="fee-amount" class="fee-cost">0 Ar</span>
            </div>
            <div class="fee-row fee-total">
                <span>Total à payer</span>
                <span id="fee-total">0 Ar</span>
            </div>
        </div>

        <div class="form-group">
            <label for="code_secret">Code Secret</label>
            <input type="password" name="code_secret" id="code_secret" maxlength="4" pattern="[0-9]{4}" placeholder="••••" required>
        </div>

        <button type="submit" class="btn-submit" id="btn-operation">Confirmer le dépôt</button>
    </form>
</div>
