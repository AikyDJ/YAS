document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // Fee calculation helper
    // ==========================================
    function calculerFrais(montant) {
        for (const t of FRAIS) {
            if (montant >= t.min && montant <= t.max) {
                return { pct: t.pct, frais: Math.round(montant * t.pct / 100) };
            }
        }
        return { pct: 0, frais: 0 };
    }

    function formatNum(n) {
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' Ar';
    }

    // ==========================================
    // Depot / Retrait form
    // ==========================================
    const typeSelect  = document.getElementById('type_operation');
    const montantIn   = document.getElementById('montant');
    const btnOp       = document.getElementById('btn-operation');
    const feePreview  = document.getElementById('fee-preview');
    const formOp      = document.getElementById('form-operation');

    if (typeSelect && montantIn && btnOp) {
        function updateDepotRetrait() {
            const type = typeSelect.value;
            if (type === 'depot') {
                btnOp.textContent = 'Confirmer le dépôt';
                btnOp.className = 'btn-submit btn-depot';
            } else {
                btnOp.textContent = 'Confirmer le retrait';
                btnOp.className = 'btn-submit btn-retrait';
            }
        }

        function updateFeePreview() {
            const m = parseFloat(montantIn.value);
            if (!m || m <= 0) { feePreview.style.display = 'none'; return; }

            const f = calculerFrais(m);
            document.getElementById('fee-montant').textContent = formatNum(m);
            document.getElementById('fee-pct').textContent = f.pct;
            document.getElementById('fee-amount').textContent = formatNum(f.frais);
            document.getElementById('fee-total').textContent = formatNum(m + f.frais);
            feePreview.style.display = 'block';
        }

        typeSelect.addEventListener('change', updateDepotRetrait);
        montantIn.addEventListener('input', updateFeePreview);

        formOp.addEventListener('submit', function(e) {
            const m = parseFloat(montantIn.value);
            const f = calculerFrais(m);
            const type = typeSelect.value === 'depot' ? 'dépôt' : 'retrait';
            if (!confirm('Confirmer le ' + type + ' de ' + formatNum(m) + ' ?\nFrais : ' + formatNum(f.frais) + '\nTotal : ' + formatNum(m + f.frais))) {
                e.preventDefault();
            }
        });

        updateDepotRetrait();
    }

    // ==========================================
    // Transfert form
    // ==========================================
    const montantT    = document.getElementById('montant_t');
    const feePreviewT = document.getElementById('fee-preview-t');
    const formT       = document.getElementById('form-transfert');
    const destinataire= document.getElementById('destinataire');
    const destStatus  = document.getElementById('dest-status');

    if (montantT && feePreviewT) {
        montantT.addEventListener('input', function() {
            const m = parseFloat(montantT.value);
            if (!m || m <= 0) { feePreviewT.style.display = 'none'; return; }

            const f = calculerFrais(m);
            document.getElementById('fee-montant-t').textContent = formatNum(m);
            document.getElementById('fee-pct-t').textContent = f.pct;
            document.getElementById('fee-amount-t').textContent = formatNum(f.frais);
            document.getElementById('fee-total-t').textContent = formatNum(m + f.frais);
            feePreviewT.style.display = 'block';
        });
    }

    if (destinataire && destStatus) {
        destinataire.addEventListener('input', function() {
            const v = destinataire.value.trim();
            if (v.length < 3) { destStatus.textContent = ''; destStatus.className = 'dest-status'; return; }

            const validPrefixes = ['033', '034', '037', '038'];
            const prefix = v.substring(0, 3);
            if (validPrefixes.includes(prefix)) {
                destStatus.textContent = 'Numéro valide';
                destStatus.className = 'dest-status valid';
            } else {
                destStatus.textContent = 'Préfixe inconnu';
                destStatus.className = 'dest-status invalid';
            }
        });
    }

    if (formT) {
        formT.addEventListener('submit', function(e) {
            const m = parseFloat(montantT.value);
            const f = calculerFrais(m);
            const dest = destinataire.value;
            if (!confirm('Confirmer le transfert de ' + formatNum(m) + ' à ' + dest + ' ?\nFrais : ' + formatNum(f.frais) + '\nTotal débité : ' + formatNum(m + f.frais))) {
                e.preventDefault();
            }
        });
    }

});
