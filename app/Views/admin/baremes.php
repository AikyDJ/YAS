<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barèmes - Admin</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <div class="central-div">
        <div class="admin-nav-card">
            <div class="card">
                <nav class="op-nav">
                    <a href="<?= base_url('admin/dashboard') ?>" class="op-link">Vue Générale</a>
                    <a href="<?= base_url('admin/prefixes') ?>" class="op-link">Préfixes</a>
                    <a href="<?= base_url('admin/types') ?>" class="op-link">Types</a>
                    <a href="<?= base_url('admin/baremes') ?>" class="op-link active">Barèmes</a>
                    <a href="<?= base_url('admin/logout') ?>" class="op-link op-link-logout">Déconnexion</a>
                </nav>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert-message success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <?php if (!empty($bareme) && !empty($bareme['id'])): ?>
            <div class="alert-message edit-mode">
                Modification du barème #<?= $bareme['id'] ?>
                <a href="<?= base_url('admin/baremes') ?>" class="btn-cancel-edit">Annuler</a>
            </div>
        <?php endif; ?>

        <div class="bareme-form-card">
            <div class="card">
                <h3><?= (!empty($bareme['id'])) ? 'Modifier le Barème' : 'Nouveau Barème' ?></h3>
                <form action="<?= base_url('admin/baremes/sauvegarder') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $bareme['id'] ?? '' ?>">

                    <div class="form-group">
                        <label for="id_type_operation">Type d'opération</label>
                        <select name="id_type_operation" id="id_type_operation" required>
                            <option value="">-- Choisir --</option>
                            <?php if (!empty($types)): ?>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= $type['id'] ?>" <?= (!empty($bareme['id_type_operation']) && (int) $bareme['id_type_operation'] === (int) $type['id']) ? 'selected' : '' ?>><?= ucfirst($type['nom']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="montant">Montant des frais</label>
                        <input type="number" name="montant" id="montant" min="0" step="0.01" value="<?= esc($bareme['montant'] ?? '') ?>" required>
                    </div>

                    <div class="grid-row grid-3">
                        <div class="form-group">
                            <label for="min_montant">Min (Ar)</label>
                            <input type="number" name="min_montant" id="min_montant" min="0" step="0.01" value="<?= esc($bareme['min_montant'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="max_montant">Max (Ar)</label>
                            <input type="number" name="max_montant" id="max_montant" min="0" step="0.01" value="<?= esc($bareme['max_montant'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="tranches-header">
                        <h4>Tranches de montant et frais</h4>
                        <button type="button" class="btn-add-tranche" id="add-tranche">+ Ajouter</button>
                    </div>

                    <div id="tranches-list">
                        <div class="tranche-row" data-index="0">
                            <div class="form-group">
                                <label for="tranche-0-min">Min (Ar)</label>
                                <input type="number" id="tranche-0-min" name="tranches[0][min]" min="0" placeholder="0" class="tranche-min">
                            </div>
                            <div class="form-group">
                                <label for="tranche-0-max">Max (Ar)</label>
                                <input type="number" id="tranche-0-max" name="tranches[0][max]" min="0" placeholder="10 000" class="tranche-max">
                            </div>
                            <div class="form-group">
                                <label for="tranche-0-frais">Frais (%)</label>
                                <input type="number" id="tranche-0-frais" name="tranches[0][frais]" min="0" step="0.01" placeholder="1.5" class="tranche-frais">
                            </div>
                            <button type="button" class="btn-remove-tranche" title="Supprimer">&times;</button>
                        </div>
                    </div>

                    <div class="fee-simulation" id="fee-sim" style="display:none;">
                        <h4>Simulation</h4>
                        <div class="sim-row">
                            <label for="sim-montant">Montant test :</label>
                            <input type="number" id="sim-montant" placeholder="15 000" min="0">
                        </div>
                        <div class="sim-result" id="sim-result"></div>
                    </div>

                    <button type="submit" class="btn-submit btn-depot">
                        <?= (!empty($bareme['id'])) ? 'Mettre à jour' : 'Enregistrer le Barème' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="bareme-list-card">
            <div class="card">
                <h3>Barèmes Existants</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Tranches</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($baremes)): ?>
                            <?php foreach ($baremes as $i => $b): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= ucfirst($b['type_operation']) ?></td>
                                    <td><?= number_format($b['montant_min'], 0, ',', ' ') ?> - <?= number_format($b['montant_max'], 0, ',', ' ') ?> Ar</td>
                                    <td class="td-actions">
                                        <a href="<?= base_url('admin/baremes/modifier/' . $b['id']) ?>" class="btn-edit">Modifier</a>
                                        <form action="<?= base_url('admin/baremes/supprimer') ?>" method="POST" class="inline-form" onsubmit="return confirm('Supprimer ce barème ?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn-delete">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">Aucun barème configuré.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Dynamic tranches
    let trancheIndex = 2;
    const trancheList = document.getElementById('tranches-list');
    const addBtn = document.getElementById('add-tranche');

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            const html = `
            <div class="tranche-row" data-index="${trancheIndex}">
                <div class="form-group">
                    <label for="tranche-${trancheIndex}-min">Min (Ar)</label>
                    <input type="number" id="tranche-${trancheIndex}-min" name="tranches[${trancheIndex}][min]" min="0" placeholder="0" class="tranche-min">
                </div>
                <div class="form-group">
                    <label for="tranche-${trancheIndex}-max">Max (Ar)</label>
                    <input type="number" id="tranche-${trancheIndex}-max" name="tranches[${trancheIndex}][max]" min="0" placeholder="100 000" class="tranche-max">
                </div>
                <div class="form-group">
                    <label for="tranche-${trancheIndex}-frais">Frais (%)</label>
                    <input type="number" id="tranche-${trancheIndex}-frais" name="tranches[${trancheIndex}][frais]" min="0" step="0.01" placeholder="3.0" class="tranche-frais">
                </div>
                <button type="button" class="btn-remove-tranche" title="Supprimer">&times;</button>
            </div>`;
            trancheList.insertAdjacentHTML('beforeend', html);
            trancheIndex++;
            bindRemove();
            updateSim();
        });
    }

    function bindRemove() {
        document.querySelectorAll('.btn-remove-tranche').forEach(btn => {
            btn.onclick = function() {
                this.closest('.tranche-row').remove();
                updateSim();
            };
        });
    }
    bindRemove();

    // Fee simulation
    const simInput = document.getElementById('sim-montant');
    const simResult = document.getElementById('sim-result');
    const feeSim = document.getElementById('fee-sim');

    function updateSim() {
        if (!simInput || !simResult) return;
        const m = parseFloat(simInput.value);
        if (!m || m <= 0) { simResult.innerHTML = ''; feeSim.style.display = 'none'; return; }

        const rows = document.querySelectorAll('.tranche-row');
        let found = false;
        rows.forEach(row => {
            const min = parseFloat(row.querySelector('.tranche-min').value) || 0;
            const max = parseFloat(row.querySelector('.tranche-max').value) || Infinity;
            const pct = parseFloat(row.querySelector('.tranche-frais').value) || 0;
            if (m >= min && m <= max && !found) {
                const frais = Math.round(m * pct / 100);
                simResult.innerHTML = `<span class="sim-ok">Frais : ${frais.toLocaleString('fr')} Ar (${pct}%) — Total : ${(m + frais).toLocaleString('fr')} Ar</span>`;
                found = true;
            }
        });
        if (!found) {
            simResult.innerHTML = '<span class="sim-err">Aucune tranche ne correspond à ce montant.</span>';
        }
        feeSim.style.display = 'block';
    }

    if (simInput) {
        simInput.addEventListener('input', updateSim);
        trancheList.addEventListener('input', updateSim);
    }
    </script>
</body>
</html>
