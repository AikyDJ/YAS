<div class="multi-transfert-container">
    <h4>Transfert Multiple (même opérateur)</h4>

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

    <form action="<?= base_url('client/proceder-multi-transfert') ?>" method="POST" id="form-multi-transfert">
        <?= csrf_field() ?>

        <div id="destinataires-list">
            <div class="destinataire-row" data-index="0">
                <div class="form-group">
                    <label>Destinataire #1</label>
                    <input type="text" name="destinataires[0][code_client]" placeholder="033xx..." required>
                </div>
                <button type="button" class="btn-remove-dest" title="Supprimer">&times;</button>
            </div>
        </div>

        <button type="button" id="add-destinataire" class="btn-add-dest">+ Ajouter un destinataire</button>

        <div class="fee-preview" id="fee-preview-multi" style="display:none;">
            <div class="fee-row">
                <span>Total montants</span>
                <span id="multi-total-montant">0 Ar</span>
            </div>
            <div class="fee-row">
                <span>Total frais</span>
                <span id="multi-total-frais" class="fee-cost">0 Ar</span>
            </div>
            <div class="fee-row fee-total">
                <span>Total débité</span>
                <span id="multi-total-debit">0 Ar</span>
            </div>
        </div>

        <div class="form-group">
            <label for="montant">Montant (Ar)</label>
            <input type="number" name="montant" id="montant" min="1" step="any" placeholder="5 000" required>
            <label for="code_secret_multi">Code Secret</label>
            <input type="password" name="code_secret" id="code_secret_multi" maxlength="4" pattern="[0-9]{4}" placeholder="••••" required>
        </div>

        <button type="submit" class="btn-submit btn-transfert">Confirmer les transferts</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const list = document.getElementById('destinataires-list');
    const addBtn = document.getElementById('add-destinataire');
    let index = 1;

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            const html = `
            <div class="destinataire-row" data-index="${index}">
                <div class="form-group">
                    <label>Destinataire #${index + 1}</label>
                    <input type="text" name="destinataires[${index}][code_client]" placeholder="033xx..." required>
                </div>
                <button type="button" class="btn-remove-dest" title="Supprimer">&times;</button>
            </div>`;
            list.insertAdjacentHTML('beforeend', html);
            index++;
            bindRemove();
            updateMultiTotal();
        });
    }

    function bindRemove() {
        document.querySelectorAll('.btn-remove-dest').forEach(btn => {
            btn.onclick = function() {
                this.closest('.destinataire-row').remove();
                updateMultiTotal();
            };
        });
    }
    bindRemove();

    function updateMultiTotal() {
        const inputs = document.querySelectorAll('.montant-input');
        const preview = document.getElementById('fee-preview-multi');
        let totalMontant = 0;

        inputs.forEach(input => {
            totalMontant += parseFloat(input.value) || 0;
        });

        if (totalMontant > 0) {
            const totalFrais = <?= json_encode($frais ?? []) ?>;
            let totalFraisCalc = 0;
            totalFrais.forEach(f => {
                if (totalMontant >= f.min_montant && totalMontant <= f.max_montant) {
                    totalFraisCalc = parseFloat(f.montant) * inputs.length;
                }
            });

            document.getElementById('multi-total-montant').textContent = totalMontant.toLocaleString('fr') + ' Ar';
            document.getElementById('multi-total-frais').textContent = totalFraisCalc.toLocaleString('fr') + ' Ar';
            document.getElementById('multi-total-debit').textContent = (totalMontant + totalFraisCalc).toLocaleString('fr') + ' Ar';
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }

    list.addEventListener('input', updateMultiTotal);
});
</script>
