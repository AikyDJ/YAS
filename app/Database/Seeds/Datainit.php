<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Datainit extends Seeder
{
    public function run()
    {
        // ---------------------------------------------------------
        // Opérateurs
        // ---------------------------------------------------------
        $this->db->table('operateur')->insertBatch([
            ['nom' => 'Orange Money', 'code_operateur' => 33],
            ['nom' => 'Mvola',        'code_operateur' => 34],
            ['nom' => 'Airtel Money', 'code_operateur' => 32],
        ]);

        // ---------------------------------------------------------
        // Types d'opération
        // ---------------------------------------------------------
        $this->db->table('type_operation')->insertBatch([
            ['nom' => 'depot',       'code_type_operation' => 1],
            ['nom' => 'retrait',     'code_type_operation' => 2],
            ['nom' => 'transaction', 'code_type_operation' => 3],
        ]);

        // ---------------------------------------------------------
        // Barème de frais (exemple)
        // ---------------------------------------------------------
        $this->db->table('frais_barem')->insertBatch([
            ['montant' => 100,  'min_montant' => 0,      'max_montant' => 5000],
            ['montant' => 300,  'min_montant' => 5001,   'max_montant' => 20000],
            ['montant' => 500,  'min_montant' => 20001,  'max_montant' => 50000],
            ['montant' => 1000, 'min_montant' => 50001,  'max_montant' => 100000],
            ['montant' => 2000, 'min_montant' => 100001, 'max_montant' => 500000],
        ]);

        // ---------------------------------------------------------
        // Clients
        // id_operateur : 1 = Orange Money, 2 = Mvola, 3 = Airtel Money
        // ---------------------------------------------------------
        $this->db->table('client')->insertBatch([
            ['nom' => 'Rakoto', 'prenom' => 'Jean',   'code_client' => 1001, 'id_operateur' => 1],
            ['nom' => 'Rasoa',  'prenom' => 'Marie',  'code_client' => 1002, 'id_operateur' => 1],
            ['nom' => 'Andry',  'prenom' => 'Paul',   'code_client' => 2001, 'id_operateur' => 2],
            ['nom' => 'Hery',   'prenom' => 'Nirina', 'code_client' => 3001, 'id_operateur' => 3],
        ]);

        // ---------------------------------------------------------
        // Opérations
        // id_type_operation : 1 = depot, 2 = retrait, 3 = transaction
        // ---------------------------------------------------------
        $this->db->table('operation')->insertBatch([
            // Dépôt : Rakoto dépose 50 000
            [
                'id_primary_client'   => 1,
                'id_secondary_client' => null,
                'id_type_operation'   => 1,
                'montant'             => 50000,
                'date_operation'      => '2026-01-05',
            ],
            // Retrait : Rakoto retire 10 000
            [
                'id_primary_client'   => 1,
                'id_secondary_client' => null,
                'id_type_operation'   => 2,
                'montant'             => 10000,
                'date_operation'      => '2026-01-10',
            ],
            // Transaction : Rakoto envoie 15 000 à Rasoa
            [
                'id_primary_client'   => 1,
                'id_secondary_client' => 2,
                'id_type_operation'   => 3,
                'montant'             => 15000,
                'date_operation'      => '2026-01-15',
            ],
            // Dépôt : Andry dépose 20 000
            [
                'id_primary_client'   => 3,
                'id_secondary_client' => null,
                'id_type_operation'   => 1,
                'montant'             => 20000,
                'date_operation'      => '2026-02-01',
            ],
            // Transaction : Andry envoie 5 000 à Hery
            [
                'id_primary_client'   => 3,
                'id_secondary_client' => 4,
                'id_type_operation'   => 3,
                'montant'             => 5000,
                'date_operation'      => '2026-02-10',
            ],
            // Retrait : Rasoa retire 3 000
            [
                'id_primary_client'   => 2,
                'id_secondary_client' => null,
                'id_type_operation'   => 2,
                'montant'             => 3000,
                'date_operation'      => '2026-02-20',
            ],
        ]);
    }
}
