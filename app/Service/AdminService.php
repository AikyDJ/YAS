<?php

namespace App\Service;

use Throwable;

class AdminService
{
    private const MESSAGE_DB_NOT_INITIALIZED = 'Base de données non initialisée.';
    private const JOIN_TYPE_OPERATION = 'type_operation t';

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
            'id'      => $row['id'] ?? null,
            'nom'     => $row['nom'] ?? null,
            'prefixe' => $row['code_operateur'] ?? ($row['prefixe'] ?? ''),
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

    private function normalizeTypeOperation(array $row): array
    {
        return [
            'id'                  => $row['id'] ?? null,
            'nom'                 => $row['nom'] ?? '',
            'code_type_operation' => $row['code_type_operation'] ?? null,
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

    public function getSituationGain()
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'operation') || !$this->tableExists($db, 'client') || !$this->tableExists($db, 'type_operation')) {
            return [
                'par_client'  => [],
                'total_gains' => 0,
            ];
        }

        try {
            $totalFrais = $db->table('operation o')
                ->select('c.nom, c.prenom, o.id_type_operation, t.nom AS type_op, SUM(o.montant_frais) AS total_frais, COUNT(o.id) AS nb_operations')
                ->join('client c', 'c.id = o.id_primary_client')
                ->join(self::JOIN_TYPE_OPERATION, 't.id = o.id_type_operation')
                ->groupBy('c.id, o.id_type_operation')->get()->getResultArray();

            $gainsTotal = $db->table('operation')
                ->select('SUM(montant_frais) AS total_gains')->get()->getRowArray();
        } catch (Throwable $exception) {
            return [
                'par_client'  => [],
                'total_gains' => 0,
            ];
        }

        return [
            'par_client'  => $totalFrais,
            'total_gains' => $gainsTotal['total_gains'] ?? 0,
        ];
    }

    public function getDashboardData(): array
    {
        $db = $this->db();

        return [
            'total_comptes'   => $this->tableExists($db, 'client') ? (int) $db->table('client')->countAllResults() : 0,
            'prefixes'        => $this->getPrefixes(),
            'nb_operations'   => $this->tableExists($db, 'operation') ? (int) $db->table('operation')->countAllResults() : 0,
            'gains_retrait'   => $this->getGainsByType('retrait'),
            'gains_transfert' => $this->getGainsByType('transfaire'),
            'comptes'         => $this->getComptes(),
        ];
    }

    public function getPrefixes(): array
    {
        return $this->safeTableRows('operateur', [$this, 'normalizePrefix'], 'code_operateur ASC');
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

        if (!$this->tableExists($db, 'frais_barem') || !$this->tableExists($db, 'type_operation')) {
            return [];
        }

        try {
            return $db->table('frais_barem fb')
                ->select('fb.id, fb.id_type_operation, fb.montant, fb.min_montant, fb.max_montant, t.nom AS type_operation')
                ->join('type_operation t', 't.id = fb.id_type_operation')
                ->orderBy('fb.min_montant', 'ASC')
                ->get()
                ->getResultArray();
        } catch (Throwable $exception) {
            return [];
        }
    }

    public function getBaremeById($id): ?array
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'frais_barem') || !$this->tableExists($db, 'type_operation')) {
            return null;
        }

        try {
            $bareme = $db->table('frais_barem fb')
                ->select('fb.id, fb.id_type_operation, fb.montant, fb.min_montant, fb.max_montant, t.nom AS type_operation')
                ->join('type_operation t', 't.id = fb.id_type_operation')
                ->where('fb.id', $id)
                ->get()
                ->getRowArray();

            return $bareme ? $bareme : null;
        } catch (Throwable $exception) {
            return null;
        }
    }

    public function getTypeOperations(): array
    {
        return $this->safeTableRows('type_operation', [$this, 'normalizeTypeOperation'], 'code_type_operation ASC');
    }

    public function saveBareme(array $data): array
    {
        $db = $this->db();
        $success = false;
        $message = self::MESSAGE_DB_NOT_INITIALIZED;

        if (!$this->tableExists($db, 'frais_barem') || !$this->tableExists($db, 'type_operation')) {
            return ['success' => false, 'message' => $message];
        }

        $idTypeOperation = (int) ($data['id_type_operation'] ?? 0);
        $montant = (float) ($data['montant'] ?? 0);
        $minMontant = (float) ($data['min_montant'] ?? 0);
        $maxMontant = (float) ($data['max_montant'] ?? 0);

        if ($idTypeOperation <= 0 || $montant < 0 || $minMontant < 0 || $maxMontant < $minMontant) {
            $message = 'Barème invalide.';
        } else {
            $payload = [
                'id_type_operation' => $idTypeOperation,
                'montant'           => $montant,
                'min_montant'       => $minMontant,
                'max_montant'       => $maxMontant,
            ];

            try {
                if (!empty($data['id'])) {
                    $db->table('frais_barem')->where('id', $data['id'])->update($payload);
                    $message = 'Barème mis à jour.';
                } else {
                    $db->table('frais_barem')->insert($payload);
                    $message = 'Barème enregistré.';
                }

                $success = true;
            } catch (Throwable $exception) {
                $message = 'Impossible d’enregistrer le barème.';
            }
        }

        return ['success' => $success, 'message' => $message];
    }

    public function saveTypeOperation(array $data): array
    {
        $db = $this->db();
        $success = false;
        $message = self::MESSAGE_DB_NOT_INITIALIZED;

        if (!$this->tableExists($db, 'type_operation')) {
            return ['success' => false, 'message' => $message];
        }

        $nom = strtolower(trim((string) ($data['nom'] ?? '')));
        $code = (int) ($data['code_type_operation'] ?? 0);

        if ($nom === '' || $code <= 0) {
            $message = 'Type d’opération invalide.';
        } else {
            try {
                $exists = $db->table('type_operation')->where('code_type_operation', $code)->countAllResults();

                if ($exists > 0) {
                    $message = 'Code de type déjà utilisé.';
                } else {
                    $db->table('type_operation')->insert([
                        'nom' => $nom,
                        'code_type_operation' => $code,
                    ]);

                    $success = true;
                    $message = 'Type d’opération ajouté.';
                }
            } catch (Throwable $exception) {
                $message = 'Impossible d’ajouter le type d’opération.';
            }
        }

        return ['success' => $success, 'message' => $message];
    }

    private function getGainsByType(string $type): float
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'v_operation_client')) {
            return 0.0;
        }

        try {
            $result = $db->table('v_operation_client')
                ->select('SUM(montant_frais) AS total')
                ->where('type_operation', $type)
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

        $codeOperateur = trim((string) ($data['code_operateur'] ?? ''));
        if (!$this->tableExists($db, 'operateur')) {
            $message = 'Base de données non initialisée.';
        } elseif ($codeOperateur === '') {
            $message = 'Préfixe invalide.';
        } else {
            $exists = $db->table('operateur')->where('code_operateur', $codeOperateur)->countAllResults();

            if ($exists > 0) {
                $message = 'Préfixe existant';
            } else {
                $nom = trim((string) ($data['nom'] ?? ''));

                if ($nom === '') {
                    $nom = 'Préfixe ' . $codeOperateur;
                }

                try {
                    $db->table('operateur')->insert([
                        'nom'            => $nom,
                        'code_operateur' => $codeOperateur,
                    ]);
                    $success = true;
                } catch (Throwable $exception) {
                    $message = 'Impossible d’ajouter le préfixe.';
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
                'montant'     => $data['montant'] ?? 0,
                'min_montant' => $data['min_montant'] ?? 0,
                'max_montant' => $data['max_montant'] ?? 0,
            ]);
        } catch (Throwable $exception) {
            return ['success' => false, 'message' => 'Impossible d’enregistrer le barème.'];
        }

        return ['success' => true, 'message' => 'Barème enregistré.'];
    }
    
}