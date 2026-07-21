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

            $exists = !empty($result);
            echo "[DEBUG] tableExists('{$table}'): " . ($exists ? 'YES' : 'NO') . "\n";
            return $exists;
        } catch (Throwable $exception) {
            echo "[DEBUG] tableExists('{$table}'): EXCEPTION - " . $exception->getMessage() . "\n";
            return false;
        }
    }

    private function columnExists($db, string $table, string $column): bool
    {
        if (!$this->tableExists($db, $table)) {
            echo "[DEBUG] columnExists('{$table}', '{$column}'): table not exists, returning false\n";
            return false;
        }

        foreach ($db->query('PRAGMA table_info(' . $db->escapeIdentifiers($table) . ')')->getResultArray() as $field) {
            if (($field['name'] ?? '') === $column) {
                echo "[DEBUG] columnExists('{$table}', '{$column}'): YES\n";
                return true;
            }
        }

        echo "[DEBUG] columnExists('{$table}', '{$column}'): NO\n";
        return false;
    }

    private function normalizePrefix(array $row): array
    {
        echo "[DEBUG] normalizePrefix: row = " . json_encode($row) . "\n";
        return [
            'id'     => $row['id'] ?? null,
            'nom'    => $row['nom'] ?? null,
            'prefix' => (string) ($row['prefix'] ?? $row['code_operateur'] ?? ''),
        ];
    }

    private function normalizeCompte(array $row): array
    {
        echo "[DEBUG] normalizeCompte: row = " . json_encode($row) . "\n";
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
        echo "[DEBUG] normalizeBareme: row = " . json_encode($row) . "\n";
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

    private function safeTableRows(string $orderBy = ''): array
    {
        echo "[DEBUG] safeTableRows: orderBy = '{$orderBy}'\n";
        $db = $this->db();
        $rows = [];

        if ($this->tableExists($db, 'type_operation')) {
            try {
                $builder = $db->table('type_operation');

                if ($orderBy !== '') {
                    $builder->orderBy($orderBy);
                }

                $rows = $builder->get()->getResultArray();
                echo "[DEBUG] safeTableRows: found " . count($rows) . " rows\n";
            } catch (Throwable $exception) {
                echo "[DEBUG] safeTableRows: EXCEPTION - " . $exception->getMessage() . "\n";
                $rows = null;
            }
        } else {
            echo "[DEBUG] safeTableRows: type_operation table does not exist\n";
        }

        if (!empty($rows)) {
            $rows = array_map(null, $rows);
        }

        echo "[DEBUG] safeTableRows: returning " . count($rows) . " rows\n";
        return $rows;
    }


    public function getMontantsAEnvoyerParOperateur(): array
    {
        echo "[DEBUG] getMontantsAEnvoyerParOperateur: START\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'operation') || !$this->tableExists($db, 'operateur')) {
            echo "[DEBUG] getMontantsAEnvoyerParOperateur: missing tables, returning []\n";
            return [];
        }

        try {
            $result = $db->table('operation o')
                ->select('op.nom AS operateur_destination, SUM(o.montant) AS total_a_envoyer')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->join('client pc', 'pc.id = o.id_primary_client')
                ->join('client sc', 'sc.id = o.id_secondary_client')
                ->join('operateur op', 'op.id = sc.id_operateur')
                ->where('LOWER(t.nom)', 'transfaire')
                ->where('pc.id_operateur != sc.id_operateur', null, false)
                ->groupBy('op.id, op.nom')
                ->get()->getResultArray();
            echo "[DEBUG] getMontantsAEnvoyerParOperateur: result = " . json_encode($result) . "\n";
            return $result;
        } catch (Throwable $exception) {
            echo "[DEBUG] getMontantsAEnvoyerParOperateur: EXCEPTION - " . $exception->getMessage() . "\n";
            return [];
        }
    }

    public function getDashboardData(): array
    {
        echo "[DEBUG] getDashboardData: START\n";
        $db = $this->db();
        $situationGain = $this->getSituationGain();

        $totalComptes = $this->tableExists($db, 'client') ? (int) $db->table('client')->countAllResults() : 0;
        $prefixes = $this->getPrefixes();
        $nbOperations = $this->tableExists($db, 'operation') ? (int) $db->table('operation')->countAllResults() : 0;
        $gainsRetrait = $this->getGainsByType('retrait');
        $gainsTransfert = $this->getGainsByType('transfaire');
        $totalComissions = $this->getTotalComissions();

        echo "[DEBUG] getDashboardData: total_comptes={$totalComptes}\n";
        echo "[DEBUG] getDashboardData: prefixes count=" . count($prefixes) . "\n";
        echo "[DEBUG] getDashboardData: nb_operations={$nbOperations}\n";
        echo "[DEBUG] getDashboardData: gains_retrait={$gainsRetrait}\n";
        echo "[DEBUG] getDashboardData: gains_transfert={$gainsTransfert}\n";
        echo "[DEBUG] getDashboardData: total_comissions={$totalComissions}\n";
        echo "[DEBUG] getDashboardData: situationGain = " . json_encode($situationGain) . "\n";

        $data = [
            'total_comptes'          => $totalComptes,
            'prefixes'               => $prefixes,
            'nb_operations'          => $nbOperations,
            'gains_retrait'          => $gainsRetrait,
            'gains_transfert'        => $gainsTransfert,
            'total_comissions'       => $totalComissions,
            'gains_internes'         => (float) ($situationGain['gains_internes'] ?? $situationGain['gains_operateur'] ?? 0),
            'gains_externes'         => (float) ($situationGain['gains_externes'] ?? $situationGain['gains_autres_ops'] ?? 0),
            'total_gains'            => (float) ($situationGain['total_gains'] ?? 0),
            'montants_par_operateur' => $this->getMontantsAEnvoyerParOperateur(),
            'comptes'                => $this->getComptes(),
        ];

        echo "[DEBUG] getDashboardData: DONE\n";
        return $data;
    }

    public function getPrefixes(): array
    {
        echo "[DEBUG] getPrefixes: START\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'operateur')) {
            echo "[DEBUG] getPrefixes: operateur table missing, returning []\n";
            return [];
        }

        try {
            if ($this->tableExists($db, 'prefix_operateur')) {
                echo "[DEBUG] getPrefixes: using prefix_operateur table\n";
                $rows = $db->table('prefix_operateur po')
                    ->select('po.id, o.nom, po.prefix')
                    ->join('operateur o', 'o.id = po.id_operateur')
                    ->orderBy('po.prefix', 'ASC')
                    ->get()->getResultArray();

                echo "[DEBUG] getPrefixes: raw rows = " . json_encode($rows) . "\n";
                $result = array_map([$this, 'normalizePrefix'], $rows);
                echo "[DEBUG] getPrefixes: returning " . count($result) . " prefixes\n";
                return $result;
            }

            echo "[DEBUG] getPrefixes: prefix_operateur table NOT found, trying operateur.code_operateur\n";
            if (!$this->columnExists($db, 'operateur', 'code_operateur')) {
                echo "[DEBUG] getPrefixes: code_operateur column missing, returning []\n";
                return [];
            }
            $rows = $db->table('operateur')
                ->select('id, nom, code_operateur')
                ->orderBy('code_operateur', 'ASC')
                ->get()
                ->getResultArray();

            echo "[DEBUG] getPrefixes: raw rows from operateur = " . json_encode($rows) . "\n";
            $result = array_map([$this, 'normalizePrefix'], $rows);
            echo "[DEBUG] getPrefixes: returning " . count($result) . " prefixes\n";
            return $result;
        } catch (Throwable $exception) {
            echo "[DEBUG] getPrefixes: EXCEPTION - " . $exception->getMessage() . "\n";
            return [];
        }
    }

    public function getComptes(): array
    {
        echo "[DEBUG] getComptes: START\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'v_solde_client')) {
            echo "[DEBUG] getComptes: v_solde_client view missing, returning []\n";
            return [];
        }

        try {
            $rows = $db->table('v_solde_client')->get()->getResultArray();
            echo "[DEBUG] getComptes: raw rows = " . json_encode($rows) . "\n";

            $result = array_map([$this, 'normalizeCompte'], $rows);
            echo "[DEBUG] getComptes: returning " . count($result) . " comptes\n";
            return $result;
        } catch (Throwable $exception) {
            echo "[DEBUG] getComptes: EXCEPTION - " . $exception->getMessage() . "\n";
            return [];
        }
    }

    public function getBaremes(): array
    {
        echo "[DEBUG] getBaremes: START\n";
        $db = $this->db();
        if (!$this->tableExists($db, 'frais_barem')) {
            echo "[DEBUG] getBaremes: frais_barem table missing, returning []\n";
            return [];
        }

        try {
            $builder = $db->table('frais_barem f')->select('f.*')->orderBy('f.min_montant', 'ASC');
            if ($this->columnExists($db, 'frais_barem', 'id_type_operation')) {
                echo "[DEBUG] getBaremes: joining type_operation\n";
                $builder->select('t.nom AS type_operation')->join('type_operation t', 't.id = f.id_type_operation', 'left');
            }
            $rows = $builder->get()->getResultArray();
            echo "[DEBUG] getBaremes: raw rows = " . json_encode($rows) . "\n";

            $result = array_map([$this, 'normalizeBareme'], $rows);
            echo "[DEBUG] getBaremes: returning " . count($result) . " baremes\n";
            return $result;
        } catch (Throwable $exception) {
            echo "[DEBUG] getBaremes: EXCEPTION - " . $exception->getMessage() . "\n";
            return [];
        }
    }

    public function getBaremeById($id): ?array
    {
        echo "[DEBUG] getBaremeById: id={$id}\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'frais_barem')) {
            echo "[DEBUG] getBaremeById: frais_barem table missing, returning null\n";
            return null;
        }

        try {
            $builder = $db->table('frais_barem f')->select('f.*')->where('f.id', $id);
            if ($this->columnExists($db, 'frais_barem', 'id_type_operation')) {
                $builder->select('t.nom AS type_operation')->join('type_operation t', 't.id = f.id_type_operation', 'left');
            }
            $bareme = $builder->get()->getRowArray();
            echo "[DEBUG] getBaremeById: raw row = " . json_encode($bareme) . "\n";

            $result = $bareme ? $this->normalizeBareme($bareme) : null;
            echo "[DEBUG] getBaremeById: returning " . ($result ? 'found' : 'null') . "\n";
            return $result;
        } catch (Throwable $exception) {
            echo "[DEBUG] getBaremeById: EXCEPTION - " . $exception->getMessage() . "\n";
            return null;
        }
    }

    private function getGainsByType(string $type): float
    {
        echo "[DEBUG] getGainsByType: type='{$type}'\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'operation') || !$this->tableExists($db, 'type_operation')) {
            echo "[DEBUG] getGainsByType: missing tables, returning 0.0\n";
            return 0.0;
        }

        try {
            $result = $db->table('operation o')
                ->select('SUM(o.montant_frais) AS total')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->where('LOWER(t.nom)', strtolower($type))
                ->get()
                ->getRowArray();

            $total = (float) ($result['total'] ?? 0);
            echo "[DEBUG] getGainsByType: result = " . json_encode($result) . " => total={$total}\n";
            return $total;
        } catch (Throwable $exception) {
            echo "[DEBUG] getGainsByType: EXCEPTION - " . $exception->getMessage() . "\n";
            return 0.0;
        }
    }

    public function getTotalComissions(): float
    {
        echo "[DEBUG] getTotalComissions: START\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'operation')) {
            echo "[DEBUG] getTotalComissions: operation table missing, returning 0.0\n";
            return 0.0;
        }

        try {
            $result = $db->table('operation')
                ->select('SUM(montant_comission) AS total')
                ->get()
                ->getRowArray();

            $total = (float) ($result['total'] ?? 0);
            echo "[DEBUG] getTotalComissions: result = " . json_encode($result) . " => total={$total}\n";
            return $total;
        } catch (Throwable $exception) {
            echo "[DEBUG] getTotalComissions: EXCEPTION - " . $exception->getMessage() . "\n";
            return 0.0;
        }
    }

    public function getComissionsByType(string $type): float
    {
        echo "[DEBUG] getComissionsByType: type='{$type}'\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'operation') || !$this->tableExists($db, 'type_operation')) {
            echo "[DEBUG] getComissionsByType: missing tables, returning 0.0\n";
            return 0.0;
        }

        try {
            $result = $db->table('operation o')
                ->select('SUM(o.montant_comission) AS total')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->where('LOWER(t.nom)', strtolower($type))
                ->get()
                ->getRowArray();

            $total = (float) ($result['total'] ?? 0);
            echo "[DEBUG] getComissionsByType: result = " . json_encode($result) . " => total={$total}\n";
            return $total;
        } catch (Throwable $exception) {
            echo "[DEBUG] getComissionsByType: EXCEPTION - " . $exception->getMessage() . "\n";
            return 0.0;
        }
    }

    public function addNewPrefix($data)
    {
        echo "[DEBUG] addNewPrefix: data = " . json_encode($data) . "\n";
        $db = $this->db();
        $success = false;
        $message = 'Préfixe ajouté.';

        $prefix = trim((string) ($data['prefix'] ?? $data['code_operateur'] ?? ''));
        $nom    = trim((string) ($data['nom'] ?? ''));

        echo "[DEBUG] addNewPrefix: prefix='{$prefix}', nom='{$nom}'\n";

        if (!$this->tableExists($db, 'operateur')) {
            $message = 'Erreur de base';
            echo "[DEBUG] addNewPrefix: operateur table missing\n";
        } elseif (!preg_match('/^\d{2,3}$/', $prefix)) {
            $message = 'Préfixe invalide.';
            echo "[DEBUG] addNewPrefix: prefix format invalid\n";
        } else {
            $hasPrefixTable = $this->tableExists($db, 'prefix_operateur');
            echo "[DEBUG] addNewPrefix: hasPrefixTable={$hasPrefixTable}\n";

            $exists = $hasPrefixTable
                ? $db->table('prefix_operateur')->where('prefix', $prefix)->countAllResults()
                : ($this->columnExists($db, 'operateur', 'code_operateur') ? $db->table('operateur')->where('code_operateur', (int) $prefix)->countAllResults() : 0);

            echo "[DEBUG] addNewPrefix: exists={$exists}\n";

            if ($exists > 0) {
                $message = 'Préfixe existant';
                echo "[DEBUG] addNewPrefix: prefix already exists\n";
            } else {
                $nomOp = $nom !== '' ? $nom : 'Préfixe ' . $prefix;
                echo "[DEBUG] addNewPrefix: nomOp='{$nomOp}'\n";

                try {
                    $operator = ['nom' => $nomOp];
                    if ($this->columnExists($db, 'operateur', 'code_operateur')) {
                        $operator['code_operateur'] = (int) $prefix;
                    }
                    if ($this->columnExists($db, 'operateur', 'comission_ptc')) {
                        $operator['comission_ptc'] = 0;
                    }
                    echo "[DEBUG] addNewPrefix: inserting operator = " . json_encode($operator) . "\n";
                    $db->table('operateur')->insert($operator);
                    if ($hasPrefixTable) {
                        $insertId = $db->insertID();
                        echo "[DEBUG] addNewPrefix: inserting prefix_operateur with id_operateur={$insertId}\n";
                        $db->table('prefix_operateur')->insert(['prefix' => $prefix, 'id_operateur' => $insertId]);
                    }
                    $success = true;
                    echo "[DEBUG] addNewPrefix: SUCCESS\n";
                } catch (Throwable $exception) {
                    $message = 'Impossible d\'ajouter le préfixe.';
                    echo "[DEBUG] addNewPrefix: EXCEPTION - " . $exception->getMessage() . "\n";
                }
            }
        }

        echo "[DEBUG] addNewPrefix: returning success={$success}, message='{$message}'\n";
        return ['success' => $success, 'message' => $message];
    }

    public function createFraisTranche(array $data)
    {
        echo "[DEBUG] createFraisTranche: data = " . json_encode($data) . "\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'frais_barem')) {
            echo "[DEBUG] createFraisTranche: frais_barem table missing\n";
            return ['success' => false, 'message' => 'Erreur dans la base'];
        }

        try {
            $values = [
                'montant'     => $data['montant'] ?? 0,
                'min_montant' => $data['min_montant'] ?? 0,
                'max_montant' => $data['max_montant'] ?? 0,
            ];
            if ($this->columnExists($db, 'frais_barem', 'id_type_operation')) {
                $values['id_type_operation'] = $data['id_type_operation'] ?? null;
            }
            echo "[DEBUG] createFraisTranche: inserting = " . json_encode($values) . "\n";
            $db->table('frais_barem')->insert($values);
            echo "[DEBUG] createFraisTranche: SUCCESS\n";
        } catch (Throwable $exception) {
            echo "[DEBUG] createFraisTranche: EXCEPTION - " . $exception->getMessage() . "\n";
            return ['success' => false, 'message' => 'Barème Non enregistré.'];
        }

        return ['success' => true, 'message' => 'Barème enregistré.'];
    }

    public function getTypeOperations(): array
    {
        echo "[DEBUG] getTypeOperations: START\n";
        $result = $this->safeTableRows('code_type_operation ASC');
        echo "[DEBUG] getTypeOperations: returning " . count($result) . " items\n";
        return $result;
    }

    public function saveTypeOperation(array $data): array
    {
        echo "[DEBUG] saveTypeOperation: data = " . json_encode($data) . "\n";
        $nom = strtolower(trim((string) ($data['nom'] ?? '')));
        $code = filter_var($data['code_type_operation'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $db = $this->db();

        echo "[DEBUG] saveTypeOperation: nom='{$nom}', code=" . var_export($code, true) . "\n";

        if (!$this->tableExists($db, 'type_operation')) {
            echo "[DEBUG] saveTypeOperation: type_operation table missing\n";
            return ['success' => false, 'message' => 'Base de données non initialisée.'];
        }
        if ($nom === '' || $code === false) {
            echo "[DEBUG] saveTypeOperation: invalid nom or code\n";
            return ['success' => false, 'message' => 'Nom ou code de type invalide.'];
        }
        $existsCount = $db->table('type_operation')->groupStart()->where('nom', $nom)->orWhere('code_type_operation', $code)->groupEnd()->countAllResults();
        echo "[DEBUG] saveTypeOperation: exists count={$existsCount}\n";

        if ($existsCount > 0) {
            echo "[DEBUG] saveTypeOperation: type already exists\n";
            return ['success' => false, 'message' => 'Ce type ou ce code existe déjà.'];
        }

        try {
            echo "[DEBUG] saveTypeOperation: inserting ['nom' => '{$nom}', 'code_type_operation' => {$code}]\n";
            $db->table('type_operation')->insert(['nom' => $nom, 'code_type_operation' => $code]);
            echo "[DEBUG] saveTypeOperation: SUCCESS\n";
            return ['success' => true, 'message' => 'Type d\'opération ajouté.'];
        } catch (Throwable $exception) {
            echo "[DEBUG] saveTypeOperation: EXCEPTION - " . $exception->getMessage() . "\n";
            return ['success' => false, 'message' => 'Impossible d\'ajouter le type.'];
        }
    }

    public function saveBareme(array $data): array
    {
        echo "[DEBUG] saveBareme: data = " . json_encode($data) . "\n";
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $typeId = filter_var($data['id_type_operation'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $montant = filter_var($data['montant'] ?? null, FILTER_VALIDATE_FLOAT);
        $min = filter_var($data['min_montant'] ?? null, FILTER_VALIDATE_FLOAT);
        $max = filter_var($data['max_montant'] ?? null, FILTER_VALIDATE_FLOAT);
        $db = $this->db();

        echo "[DEBUG] saveBareme: id=" . var_export($id, true) . ", typeId=" . var_export($typeId, true) . ", montant=" . var_export($montant, true) . ", min=" . var_export($min, true) . ", max=" . var_export($max, true) . "\n";

        if (!$this->tableExists($db, 'frais_barem') || !$this->tableExists($db, 'type_operation')) {
            echo "[DEBUG] saveBareme: missing tables\n";
            return ['success' => false, 'message' => 'Base de données non initialisée.'];
        }
        $hasTypeColumn = $this->columnExists($db, 'frais_barem', 'id_type_operation');
        echo "[DEBUG] saveBareme: hasTypeColumn=" . ($hasTypeColumn ? 'YES' : 'NO') . "\n";

        if (($hasTypeColumn && $typeId === false) || $montant === false || $min === false || $max === false || $montant < 0 || $min < 0 || $max < $min) {
            echo "[DEBUG] saveBareme: validation failed\n";
            return ['success' => false, 'message' => 'Les valeurs du barème sont invalides.'];
        }
        if ($hasTypeColumn && $db->table('type_operation')->where('id', $typeId)->countAllResults() === 0) {
            echo "[DEBUG] saveBareme: type_operation id={$typeId} not found\n";
            return ['success' => false, 'message' => 'Type d\'opération introuvable.'];
        }

        $values = ['montant' => $montant, 'min_montant' => $min, 'max_montant' => $max];
        if ($hasTypeColumn) {
            $values['id_type_operation'] = $typeId;
        }
        echo "[DEBUG] saveBareme: values = " . json_encode($values) . "\n";

        try {
            if ($id !== false && $id !== null) {
                echo "[DEBUG] saveBareme: updating id={$id}\n";
                $db->table('frais_barem')->where('id', $id)->update($values);
                echo "[DEBUG] saveBareme: UPDATED\n";
                return ['success' => true, 'message' => 'Barème mis à jour.'];
            }
            echo "[DEBUG] saveBareme: inserting new bareme\n";
            $db->table('frais_barem')->insert($values);
            echo "[DEBUG] saveBareme: INSERTED\n";
            return ['success' => true, 'message' => 'Barème enregistré.'];
        } catch (Throwable $exception) {
            echo "[DEBUG] saveBareme: EXCEPTION - " . $exception->getMessage() . "\n";
            return ['success' => false, 'message' => 'Impossible d\'enregistrer le barème.'];
        }
    }

    public function deletePrefix(int $id): array
    {
        echo "[DEBUG] deletePrefix: id={$id}\n";
        $db = $this->db();
        if ($id < 1 || !$this->tableExists($db, 'operateur')) {
            echo "[DEBUG] deletePrefix: invalid id or operateur table missing\n";
            return ['success' => false, 'message' => 'Préfixe invalide.'];
        }
        $clientCount = $this->tableExists($db, 'client') ? $db->table('client')->where('id_operateur', $id)->countAllResults() : 0;
        echo "[DEBUG] deletePrefix: clients using operator={$clientCount}\n";

        if ($clientCount > 0) {
            echo "[DEBUG] deletePrefix: cannot delete, has clients\n";
            return ['success' => false, 'message' => 'Impossible de supprimer un opérateur ayant des clients.'];
        }
        $db->table('operateur')->where('id', $id)->delete();
        echo "[DEBUG] deletePrefix: DELETED\n";
        return ['success' => true, 'message' => 'Préfixe supprimé.'];
    }

    public function getCommission(int $id): array
    {
        echo "[DEBUG] getCommission: id={$id}\n";
        $db = $this->db();
        if ($id < 1 || !$this->tableExists($db, 'operateur')) {
            echo "[DEBUG] getCommission: invalid id or operateur table missing\n";
            return ['success' => false, 'message' => 'Opérateur introuvable.', 'commission' => 0.0];
        }
        try {
            $row = $db->table('operateur')
                ->select('comission_ptc')
                ->where('id', $id)
                ->get()
                ->getRowArray();

            echo "[DEBUG] getCommission: row = " . json_encode($row) . "\n";
            $commission = (float) ($row['comission_ptc'] ?? 0.0);
            echo "[DEBUG] getCommission: commission={$commission}\n";
            return [
                'success' => true,
                'commission' => $commission
            ];
        } catch (Throwable $exception) {
            echo "[DEBUG] getCommission: EXCEPTION - " . $exception->getMessage() . "\n";
            return ['success' => false, 'message' => 'Erreur de lecture.', 'commission' => 0.0];
        }
    }

    public function saveCommission(int $id, float $pourcentage): array
    {
        echo "[DEBUG] saveCommission: id={$id}, pourcentage={$pourcentage}\n";
        $db = $this->db();
        if ($id < 1 || !$this->tableExists($db, 'operateur') || $pourcentage < 0) {
            echo "[DEBUG] saveCommission: validation failed\n";
            return ['success' => false, 'message' => 'Error'];
        }
        try {
            echo "[DEBUG] saveCommission: updating comission_ptc={$pourcentage} for id={$id}\n";
            $db->table('operateur')
                ->where('id', $id)
                ->update(['comission_ptc' => $pourcentage]);
            echo "[DEBUG] saveCommission: UPDATED\n";
            return ['success' => true, 'message' => 'Mise à jour.'];
        } catch (Throwable $exception) {
            echo "[DEBUG] saveCommission: EXCEPTION - " . $exception->getMessage() . "\n";
            return ['success' => false, 'message' => 'Erreur de Mise à jour .'];
        }
    }
    public function getSituationGain(): array
    {
        echo "[DEBUG] getSituationGain: START\n";
        $db = $this->db();

        if (!$this->tableExists($db, 'operation') || !$this->tableExists($db, 'client') || !$this->tableExists($db, 'type_operation')) {
            echo "[DEBUG] getSituationGain: missing tables, returning defaults\n";
            return [
                'gains_operateur'    => 0.0,
                'gains_autres_ops'   => 0.0,
                'total_gains'        => 0.0,
                'details_frais'      => 0.0,
                'details_commission' => 0.0,
            ];
        }
        try {
            $opQuery = $db->table('operation o')
                ->select('SUM(o.montant_frais) AS total_frais')
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

            echo "[DEBUG] getSituationGain: opQuery = " . json_encode($opQuery) . "\n";
            $gainsOperateur = (float) ($opQuery['total_frais'] ?? 0);
            echo "[DEBUG] getSituationGain: gainsOperateur={$gainsOperateur}\n";

            $autresQuery = $db->table('operation o')
                ->select('SUM(o.montant_frais) AS total_frais, SUM(o.montant_comission) AS total_commission')
                ->join('type_operation t', 't.id = o.id_type_operation')
                ->join('client pc', 'pc.id = o.id_primary_client')
                ->join('client sc', 'sc.id = o.id_secondary_client')
                ->where('LOWER(t.nom)', 'transfaire')
                ->where('pc.id_operateur != sc.id_operateur', null, false)
                ->get()->getRowArray();

            echo "[DEBUG] getSituationGain: autresQuery = " . json_encode($autresQuery) . "\n";

            $fraisExternes = (float) ($autresQuery['total_frais'] ?? 0);
            $commissionExternes = (float) ($autresQuery['total_commission'] ?? 0);

            $gainsAutresOps = $fraisExternes + $commissionExternes;
            $totalGains = $gainsOperateur + $gainsAutresOps;

            echo "[DEBUG] getSituationGain: fraisExternes={$fraisExternes}, commissionExternes={$commissionExternes}\n";
            echo "[DEBUG] getSituationGain: gainsAutresOps={$gainsAutresOps}, totalGains={$totalGains}\n";

            $result = [
                'gains_operateur'    => $gainsOperateur,
                'gains_autres_ops'   => $gainsAutresOps,
                'total_gains'        => $totalGains,
                'details_frais'      => $gainsOperateur + $fraisExternes,
                'details_commission' => $commissionExternes
            ];
            echo "[DEBUG] getSituationGain: returning = " . json_encode($result) . "\n";
            return $result;

        } catch (Throwable $exception) {
            echo "[DEBUG] getSituationGain: EXCEPTION - " . $exception->getMessage() . "\n";
            return [
                'gains_operateur'    => 0.0,
                'gains_autres_ops'   => 0.0,
                'total_gains'        => 0.0,
                'details_frais'      => 0.0,
                'details_commission' => 0.0,
            ];
        }
    }
}
