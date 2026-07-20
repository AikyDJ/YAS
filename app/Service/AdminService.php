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
            'prefix' => $row['prefix'] ?? '',
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
            'id'             => $row['id'] ?? null,
            'type_operation' => $row['type_operation'] ?? 'N/A',
            'montant_min'    => $row['montant_min'] ?? ($row['min_montant'] ?? 0),
            'montant_max'    => $row['montant_max'] ?? ($row['max_montant'] ?? 0),
            'montant'        => $row['montant'] ?? null,
            'min_montant'    => $row['min_montant'] ?? ($row['montant_min'] ?? null),
            'max_montant'    => $row['max_montant'] ?? ($row['montant_max'] ?? null),
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
                ->join('type_operation t', 't.id = o.id_type_operation')
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
        $db = $this->db();

        if (!$this->tableExists($db, 'prefix_operateur')) {
            return [];
        }

        try {
            $rows = $db->table('prefix_operateur po')
                ->select('po.id, o.nom, po.prefix')
                ->join('operateur o', 'o.id = po.id_operateur')
                ->orderBy('po.prefix', 'ASC')
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
        return $this->safeTableRows('frais_barem', [$this, 'normalizeBareme'], 'min_montant ASC');
    }

    public function getBaremeById($id): ?array
    {
        $db = $this->db();

        if (!$this->tableExists($db, 'frais_barem')) {
            return null;
        }

        try {
            $bareme = $db->table('frais_barem')->where('id', $id)->get()->getRowArray();

            return $bareme ? $this->normalizeBareme($bareme) : null;
        } catch (Throwable $exception) {
            return null;
        }
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

        $prefix = trim((string) ($data['prefix'] ?? ''));
        $nom    = trim((string) ($data['nom'] ?? ''));

        if (!$this->tableExists($db, 'prefix_operateur') || !$this->tableExists($db, 'operateur')) {
            $message = 'Base de données non initialisée.';
        } elseif ($prefix === '') {
            $message = 'Préfixe invalide.';
        } else {
            $exists = $db->table('prefix_operateur')->where('prefix', $prefix)->countAllResults();

            if ($exists > 0) {
                $message = 'Préfixe existant';
            } else {
                $nomOp = $nom !== '' ? $nom : 'Préfixe ' . $prefix;

                try {
                    $db->table('operateur')->insert(['nom' => $nomOp]);
                    $idOperateur = $db->insertID();

                    $db->table('prefix_operateur')->insert([
                        'prefix'       => $prefix,
                        'id_operateur' => $idOperateur,
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
                'montant'     => $data['montant'] ?? 0,
                'min_montant' => $data['min_montant'] ?? 0,
                'max_montant' => $data['max_montant'] ?? 0,
            ]);
        } catch (Throwable $exception) {
            return ['success' => false, 'message' => 'Impossible d\'enregistrer le barème.'];
        }

        return ['success' => true, 'message' => 'Barème enregistré.'];
    }
}
