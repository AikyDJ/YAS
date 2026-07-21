<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Datainit extends Seeder
{
    public function run()
    {
        // ---------------------------------------------------------
        // Opérateurs (sans code_operateur — c'est dans prefix_operateur)
        // ---------------------------------------------------------
        $this->db->table('operateur')->insertBatch([
            ['nom' => 'Airtel Money', 'comission_ptc' => 0.04,'bonus' => 0.50],
            ['nom' => 'Mvola', 'comission_ptc' => 0.03,'bonus' => 0.50],
            ['nom' => 'Orange Money', 'comission_ptc' => 0.05,'bonus' => 0.50],
        ]);

        // ---------------------------------------------------------
        // Préfixes opérateurs
        // id_operateur : 1 = Orange Money, 2 = Mvola, 3 = Airtel Money
        // ---------------------------------------------------------
        $this->db->table('prefix_operateur')->insertBatch([
            ['prefix' => '33', 'id_operateur' => 1],
            ['prefix' => '34', 'id_operateur' => 2],
            ['prefix' => '32', 'id_operateur' => 3],
        ]);

        // ---------------------------------------------------------
        // Types d'opération
        // ---------------------------------------------------------
        $this->db->table('type_operation')->insertBatch([
            ['nom' => 'depot', 'code_type_operation' => 1],
            ['nom' => 'retrait', 'code_type_operation' => 2],
            ['nom' => 'transfaire', 'code_type_operation' => 3],
        ]);

        // ---------------------------------------------------------
        // Barème de frais
        // ---------------------------------------------------------
        $this->db->table('frais_barem')->insertBatch([
            ['id_type_operation' => 1, 'montant' => 50, 'min_montant' => 100, 'max_montant' => 1000],
            ['id_type_operation' => 1, 'montant' => 50, 'min_montant' => 1001, 'max_montant' => 5000],
            ['id_type_operation' => 1, 'montant' => 100, 'min_montant' => 5001, 'max_montant' => 10000],
            ['id_type_operation' => 1, 'montant' => 200, 'min_montant' => 10001, 'max_montant' => 25000],
            ['id_type_operation' => 1, 'montant' => 400, 'min_montant' => 25001, 'max_montant' => 50000],
            ['id_type_operation' => 1, 'montant' => 800, 'min_montant' => 50001, 'max_montant' => 100000],
            ['id_type_operation' => 1, 'montant' => 1500, 'min_montant' => 100001, 'max_montant' => 250000],
            ['id_type_operation' => 1, 'montant' => 1500, 'min_montant' => 250001, 'max_montant' => 500000],
            ['id_type_operation' => 1, 'montant' => 2500, 'min_montant' => 500001, 'max_montant' => 1000000],
            ['id_type_operation' => 1, 'montant' => 3000, 'min_montant' => 1000001, 'max_montant' => 2000000],
        ]);

        // ---------------------------------------------------------
        // Clients
        // id_operateur : 1 = Airtel Money , 2 = Mvola , 3 = Orange Money
        // ---------------------------------------------------------
        $this->db->table('client')->insertBatch([
            ['nom' => 'Rakoto', 'prenom' => 'Jean', 'code_client' => '331234567', 'code_secret' => '0000', 'id_operateur' => 1],
            ['nom' => 'Rasoa', 'prenom' => 'Marie', 'code_client' => '330123456', 'code_secret' => '0000', 'id_operateur' => 1],
            ['nom' => 'Pierre', 'prenom' => 'Jacques', 'code_client' => '330054321', 'code_secret' => '0000', 'id_operateur' => 1],
            ['nom' => 'Ral', 'prenom' => 'Entouine', 'code_client' => '330004321', 'code_secret' => '0000', 'id_operateur' => 1],
            ['nom' => 'Andry', 'prenom' => 'Paul', 'code_client' => '340012345', 'code_secret' => '0000', 'id_operateur' => 2],
            ['nom' => 'Hery', 'prenom' => 'Nirina', 'code_client' => '320001234', 'code_secret' => '0000', 'id_operateur' => 3],
        ]);

        // ---------------------------------------------------------
        // Opérations
        // ---------------------------------------------------------
        $this->db->table('operation')->insertBatch([
            [
                'id_primary_client' => 1,
                'id_secondary_client' => null,
                'id_type_operation' => 1,
                'montant' => 50000,
                'montant_frais' => 0,
                'montant_comission' => 0,
                'date_operation' => '2026-01-05',
            ],
            [
                'id_primary_client' => 2,
                'id_secondary_client' => null,
                'id_type_operation' => 1,
                'montant' => 50000,
                'montant_frais' => 0,
                'montant_comission' => 0,
                'date_operation' => '2026-01-05',
            ],
            [
                'id_primary_client' => 3,
                'id_secondary_client' => null,
                'id_type_operation' => 1,
                'montant' => 50000,
                'montant_frais' => 0,
                'montant_comission' => 0,
                'date_operation' => '2026-01-05',
            ],
            [
                'id_primary_client' => 4,
                'id_secondary_client' => null,
                'id_type_operation' => 1,
                'montant' => 50000,
                'montant_frais' => 0,
                'montant_comission' => 0,
                'date_operation' => '2026-01-05',
            ],
            [
                'id_primary_client' => 1,
                'id_secondary_client' => null,
                'id_type_operation' => 1,
                'montant' => 50000,
                'montant_frais' => 0,
                'montant_comission' => 0,
                'date_operation' => '2026-01-05',
            ],
        ]);
    }
}
