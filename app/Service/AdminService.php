<?php

namespace App\Service;

use Throwable;

class AdminService
{
    private function db()
    {
        return \Config\Database::connect();
    }

    private function tableExists($db, string $table): bool
    {
        try {
            $result = $db->query(
                'SELECT name FROM sqlite_master WHERE type IN (\'table\', \'view\') AND name = ?',
                [$table]
            )->getRowArray();

            return !empty($result);
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function normalizePrefix(array $row): array
    {
        return [
            'id'     => $row['id'] ?? null,
            'nom'    => $row['nom'] ?? null,
            'prefix' => (string) ($row['prefix'] ?? $row['code_operateur'] ?? ''),
        ];
    }

    private function normalizeCompte(array $row): array
    {
        return [
            'id_client'           => $row['id_client'] ?? null,
            'telephone'           => $row['code_client_complet'] ?? ($row['telephone'] ?? ''),
            'solde'               => (float) ($row['solde_actuel'] ?? ($row['solde'] ?? 0)),
            'nom'                 => $row['nom'] ?? null,
            'prenom'              => $row['prenom'] ?? null,
            'code_client_complet' => $row['code_client_complet'] ?? null,
        ];
    }

    private function normalizeBareme(array $row): array
    {
        return [
            'id'          => $row['id'] ?? null,
            'id_type_operation' => $row['id_type_operation'] ?? null,
            'type_operation' => $row['type_operation'] ?? '',
            'montant'     => (float) ($row['montant'] ?? 0),
            'min_montant' => (float) ($row['min_montant'] ?? 0),
            'max_montant' => (float) ($row['max_montant'] ?? 0),
            'montant_min' => (float) ($row['min_montant'] ?? 0),
            'montant_max' => (float) ($row['max_montant'] ?? 0),
        ];
    }

    private function safeTableRows(string $table, ?callable $normalizer = null, string $orderBy = ''): array
    {
        $db = $this->db();
        $rows = [];

        if ($this->tableExists($db, $table)) {
            try {
                $builder = $db->table($table);

                if ($orderBy !== '') {
                    $builder->orderBy($orderBy);
                }

                $rows = $builder->get()->getResultArray();
            } catch (Throwable $exception) {
                $rows = [];
            }
        }

        if ($normalizer !== null && !empty($rows)) {
            $rows = array_map($normalizer, $rows);
        }

        return $rows;
    }


    public function getSituationGain(): array
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'operation') || !$this->tableExists($db, 'client') || !$this->tableExists($db, 'type_operation')) {
            return [
                'gains_internes' => 0.0,
                'gains_externes' => 0.0,
                'total_gains'    => 0.0,
                'par_client'     => [],
            ];
        }

        try {
            $gainsInternesQuery = $db->table('operation o')
                ->select('SUM(o.montant_frais) AS total')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->join('client pc', 'pc.id = o.id_primary_client')
                ->leftJoin('client sc', 'sc.id = o.id_secondary_client')
                ->groupStart()
                ->where('LOWER(t.nom) !=', 'transfaire')
                ->orGroupStart()
                ->where('LOWER(t.nom)', 'transfaire')
                ->where('pc.id_operateur = sc.id_operateur', null, false)
                ->groupEnd()
                ->groupEnd()
                ->get()->getRowArray();

            $gainsInternes = (float) ($gainsInternesQuery['total'] ?? 0);
            $gainsExternesQuery = $db->table('operation o')
                ->select('SUM(o.montant_frais) AS total')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->join('client pc', 'pc.id = o.id_primary_client')
                ->join('client sc', 'sc.id = o.id_secondary_client')
                ->where('LOWER(t.nom)', 'transfaire')
                ->where('pc.id_operateur != sc.id_operateur', null, false)
                ->get()->getRowArray();
            $gainsExternes = (float) ($gainsExternesQuery['total'] ?? 0);
            $parClient = $db->table('operation o')
                ->select('c.nom, c.prenom, o.id_type_operation, t.nom AS type_op, SUM(o.montant_frais) AS total_frais, COUNT(o.id) AS nb_operations')
                ->join('client c', 'c.id = o.id_primary_client')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->groupBy('c.id, c.nom, c.prenom, o.id_type_operation, t.nom')
                ->get()->getResultArray();
            $totalGains = $gainsInternes + $gainsExternes;
        } catch (Throwable $exception) {
            return [
                'gains_internes' => 0.0,
                'gains_externes' => 0.0,
                'total_gains'    => 0.0,
                'par_client'     => [],
            ];
        }

        return [
            'gains_internes' => $gainsInternes,
            'gains_externes' => $gainsExternes,
            'total_gains'    => $totalGains,
            'par_client'     => $parClient,
        ];
    }


    public function getMontantsAEnvoyerParOperateur(): array
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'operation') || !$this->tableExists($db, 'operateur')) {
            return [];
        }

        try {
            return $db->table('operation o')
                ->select('op.nom AS operateur_destination, SUM(o.montant) AS total_a_envoyer')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->join('client pc', 'pc.id = o.id_primary_client')
                ->join('client sc', 'sc.id = o.id_secondary_client')
                ->join('operateur op', 'op.id = sc.id_operateur')
                ->where('LOWER(t.nom)', 'transfaire')
                ->where('pc.id_operateur != sc.id_operateur', null, false)
                ->groupBy('op.id, op.nom')
                ->get()->getResultArray();
        } catch (Throwable $exception) {
            return [];
        }
    }

    public function getDashboardData(): array
    {
        $db = $this->db();
        $situationGain = $this->getSituationGain();

        return [
            'total_comptes'          => $this->tableExists($db, 'client') ? (int) $db->table('client')->countAllResults() : 0,
            'prefixes'               => $this->getPrefixes(),
            'nb_operations'          => $this->tableExists($db, 'operation') ? (int) $db->table('operation')->countAllResults() : 0,
            'gains_retrait'          => $this->getGainsByType('retrait'),
            'gains_transfert'        => $this->getGainsByType('transfaire'),
            'gains_internes'         => $situationGain['gains_internes'],
            'gains_externes'         => $situationGain['gains_externes'],
            'total_gains'            => $situationGain['total_gains'],
            'montants_par_operateur' => $this->getMontantsAEnvoyerParOperateur(),
            'comptes'                => $this->getComptes(),
        ];
    }

    public function getPrefixes(): array
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'operateur')) {
            return [];
        }

        try {
            $rows = $db->table('operateur')
                ->select('id, nom, code_operateur')
                ->orderBy('code_operateur', 'ASC')
                ->get()
                ->getResultArray();

            return array_map([$this, 'normalizePrefix'], $rows);
        } catch (Throwable $exception) {
            return [];
        }
    }

    public function getComptes(): array
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'v_solde_client')) {
            return [];
        }

        try {
            $rows = $db->table('v_solde_client')->get()->getResultArray();

            return array_map([$this, 'normalizeCompte'], $rows);
        } catch (Throwable $exception) {
            return [];
        }
    }

    public function getBaremes(): array
    {
        $db = $this->db();
        if (!$this->tableExists($db, 'frais_barem')) {
            return [];
        }

        try {
            $rows = $db->table('frais_barem f')
                ->select('f.*, t.nom AS type_operation')
                ->join('type_operation t', 't.id = f.id_type_operation', 'left')
                ->orderBy('f.min_montant', 'ASC')
                ->get()->getResultArray();

            return array_map([$this, 'normalizeBareme'], $rows);
        } catch (Throwable $exception) {
            return [];
        }
    }

    public function getBaremeById($id): ?array
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'frais_barem')) {
            return null;
        }

        try {
            $bareme = $db->table('frais_barem f')
                ->select('f.*, t.nom AS type_operation')
                ->join('type_operation t', 't.id = f.id_type_operation', 'left')
                ->where('f.id', $id)->get()->getRowArray();

            return $bareme ? $this->normalizeBareme($bareme) : null;
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function getGainsByType(string $type): float
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'operation') || !$this->tableExists($db, 'type_operation')) {
            return 0.0;
        }

        try {
            $result = $db->table('operation o')
                ->select('SUM(o.montant_frais) AS total')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->where('LOWER(t.nom)', strtolower($type))
                ->get()
                ->getRowArray();

            return (float) ($result['total'] ?? 0);
        } catch (Throwable $exception) {
            return 0.0;
        }
    }

    public function addNewPrefix($data)
    {
        $db = $this->db();
        $success = false;
        $message = 'Préfixe ajouté.';

        $prefix = trim((string) ($data['prefix'] ?? $data['code_operateur'] ?? ''));
        $nom    = trim((string) ($data['nom'] ?? ''));

        if (!$this->tableExists($db, 'operateur')) {
            $message = 'Base de données non initialisée.';
        } elseif (!preg_match('/^\d{2,3}$/', $prefix)) {
            $message = 'Préfixe invalide.';
        } else {
            $exists = $db->table('operateur')->where('code_operateur', (int) $prefix)->countAllResults();

            if ($exists > 0) {
                $message = 'Préfixe existant';
            } else {
                $nomOp = $nom !== '' ? $nom : 'Préfixe ' . $prefix;

                try {
                    $db->table('operateur')->insert([
                        'nom' => $nomOp,
                        'code_operateur' => (int) $prefix,
                    ]);
                    $success = true;
                } catch (Throwable $exception) {
                    $message = 'Impossible d\'ajouter le préfixe.';
                }
            }
        }

        return ['success' => $success, 'message' => $message];
    }

    public function createFraisTranche(array $data)
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'frais_barem')) {
            return ['success' => false, 'message' => 'Base de données non initialisée.'];
        }

        try {
            $db->table('frais_barem')->insert([
                'id_type_operation' => $data['id_type_operation'] ?? null,
                'montant'     => $data['montant'] ?? 0,
                'min_montant' => $data['min_montant'] ?? 0,
                'max_montant' => $data['max_montant'] ?? 0,
            ]);
        } catch (Throwable $exception) {
            return ['success' => false, 'message' => 'Impossible d\'enregistrer le barème.'];
        }

        return ['success' => true, 'message' => 'Barème enregistré.'];
    }

    public function getTypeOperations(): array
    {
        return $this->safeTableRows('type_operation', null, 'code_type_operation ASC');
    }

    public function saveTypeOperation(array $data): array
    {
        $nom = strtolower(trim((string) ($data['nom'] ?? '')));
        $code = filter_var($data['code_type_operation'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $db = $this->db();

        if (!$this->tableExists($db, 'type_operation')) {
            return ['success' => false, 'message' => 'Base de données non initialisée.'];
        }
        if ($nom === '' || $code === false) {
            return ['success' => false, 'message' => 'Nom ou code de type invalide.'];
        }
        if ($db->table('type_operation')->groupStart()->where('nom', $nom)->orWhere('code_type_operation', $code)->groupEnd()->countAllResults() > 0) {
            return ['success' => false, 'message' => 'Ce type ou ce code existe déjà.'];
        }

        try {
            $db->table('type_operation')->insert(['nom' => $nom, 'code_type_operation' => $code]);
            return ['success' => true, 'message' => 'Type d\'opération ajouté.'];
        } catch (Throwable $exception) {
            return ['success' => false, 'message' => 'Impossible d\'ajouter le type.'];
        }
    }

    public function saveBareme(array $data): array
    {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $typeId = filter_var($data['id_type_operation'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $montant = filter_var($data['montant'] ?? null, FILTER_VALIDATE_FLOAT);
        $min = filter_var($data['min_montant'] ?? null, FILTER_VALIDATE_FLOAT);
        $max = filter_var($data['max_montant'] ?? null, FILTER_VALIDATE_FLOAT);
        $db = $this->db();

        if (!$this->tableExists($db, 'frais_barem') || !$this->tableExists($db, 'type_operation')) {
            return ['success' => false, 'message' => 'Base de données non initialisée.'];
        }
        if ($typeId === false || $montant === false || $min === false || $max === false || $montant < 0 || $min < 0 || $max < $min) {
            return ['success' => false, 'message' => 'Les valeurs du barème sont invalides.'];
        }
        if ($db->table('type_operation')->where('id', $typeId)->countAllResults() === 0) {
            return ['success' => false, 'message' => 'Type d\'opération introuvable.'];
        }

        $values = ['id_type_operation' => $typeId, 'montant' => $montant, 'min_montant' => $min, 'max_montant' => $max];
        try {
            if ($id !== false && $id !== null) {
                $db->table('frais_barem')->where('id', $id)->update($values);
                return ['success' => true, 'message' => 'Barème mis à jour.'];
            }
            $db->table('frais_barem')->insert($values);
            return ['success' => true, 'message' => 'Barème enregistré.'];
        } catch (Throwable $exception) {
            return ['success' => false, 'message' => 'Impossible d\'enregistrer le barème.'];
        }
    }

    public function deletePrefix(int $id): array
    {
        $db = $this->db();
        if ($id < 1 || !$this->tableExists($db, 'operateur')) {
            return ['success' => false, 'message' => 'Préfixe invalide.'];
        }
        if ($this->tableExists($db, 'client') && $db->table('client')->where('id_operateur', $id)->countAllResults() > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer un opérateur ayant des clients.'];
        }
        $db->table('operateur')->where('id', $id)->delete();
        return ['success' => true, 'message' => 'Préfixe supprimé.'];
    }

    public function getCommission(int $id): array
    {
        $db = $this->db();
        if ($id < 1 || !$this->tableExists($db, 'operateur')) {
            return ['success' => false, 'message' => 'Opérateur introuvable.', 'commission' => 0.0];
        }
        try {
            $row = $db->table('operateur')
                ->select('comission_ptc')
                ->where('id', $id)
                ->get()
                ->getRowArray();

            return [
                'success' => true,
                'commission' => (float) ($row['comission_ptc'] ?? 0.0)
            ];
        } catch (Throwable $exception) {
            return ['success' => false, 'message' => 'Erreur de lecture.', 'commission' => 0.0];
        }
    }

    public function saveCommission(int $id, float $pourcentage): array
    {
        $db = $this->db();
        if ($id < 1 || !$this->tableExists($db, 'operateur') || $pourcentage < 0) {
            return ['success' => false, 'message' => 'Données ou pourcentage invalides.'];
        }
        try {
            $db->table('operateur')
                ->where('id', $id)
                ->update(['comission_ptc' => $pourcentage]);
            return ['success' => true, 'message' => 'Commission mise à jour avec succès.'];
        } catch (Throwable $exception) {
            return ['success' => false, 'message' => 'Impossible de sauvegarder la commission.'];
        }
    }
}
