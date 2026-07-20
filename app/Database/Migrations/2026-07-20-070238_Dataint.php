<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Dataint extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------
        // Table: operateur
        // ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'auto_increment' => true,
            ],
            'nom' => [
                'type' => 'TEXT',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('operateur');

        // ---------------------------------------------------------
        // Table: prefix_operateur
        // ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'auto_increment' => true,
            ],
            'prefix' => [
                'type' => 'VARCHAR(3)',
                'null' => false,
            ],
            'id_operateur' => [
                'type' => 'INTEGER',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('prefix');
        $this->forge->addForeignKey('id_operateur', 'operateur', 'id', false, false);
        $this->forge->createTable('prefix_operateur');

        // ---------------------------------------------------------
        // Table: client
        // ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'auto_increment' => true,
            ],
            'nom' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'prenom' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'code_client' => [
                'type' => 'VARCHAR(10)',
                'null' => false,
            ],
            'code_secret' => [
                'type' => 'VARCHAR(4)',
                'null' => false,
            ],
            'id_operateur' => [
                'type' => 'INTEGER',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code_client');
        $this->forge->addForeignKey('id_operateur', 'operateur', 'id', false, false);
        $this->forge->createTable('client');

        // ---------------------------------------------------------
        // Table: frais_barem
        // ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'auto_increment' => true,
            ],
            'id_type_operation' => [
                'type' => 'INTEGER',
                'null' => false,
            ],
            'montant' => [
                'type' => 'REAL',
                'null' => false,
            ],
            'min_montant' => [
                'type' => 'REAL',
                'null' => false,
            ],
            'max_montant' => [
                'type' => 'REAL',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('id_type_operation', 'type_operation', 'id', false, false);
        $this->forge->createTable('frais_barem');

        // ---------------------------------------------------------
        // Table: type_operation
        // ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'auto_increment' => true,
            ],
            'nom' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'code_type_operation' => [
                'type' => 'INTEGER',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code_type_operation');
        $this->forge->createTable('type_operation');

        // ---------------------------------------------------------
        // Table: operation
        // ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'auto_increment' => true,
            ],
            'id_primary_client' => [
                'type' => 'INTEGER',
                'null' => false,
            ],
            'id_secondary_client' => [
                'type' => 'INTEGER',
                'null' => true,
            ],
            'id_type_operation' => [
                'type' => 'INTEGER',
                'null' => false,
            ],
            'montant' => [
                'type' => 'REAL',
                'null' => false,
            ],
            'montant_frais' => [
                'type' => 'REAL',
                'null' => false,
            ],
            'date_operation' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('id_primary_client', 'client', 'id', false, false);
        $this->forge->addForeignKey('id_secondary_client', 'client', 'id', false, 'SET NULL');
        $this->forge->addForeignKey('id_type_operation', 'type_operation', 'id', false, false);
        $this->forge->createTable('operation');

        // ---------------------------------------------------------
        // Index de performance
        // ---------------------------------------------------------
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_client_operateur ON client(id_operateur)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_prefix_operateur ON prefix_operateur(id_operateur)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_operation_primary_client ON operation(id_primary_client)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_operation_secondary_client ON operation(id_secondary_client)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_operation_type ON operation(id_type_operation)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_operation_date ON operation(date_operation)');

        // ---------------------------------------------------------
        // Vues (SQLite)
        // ---------------------------------------------------------
        $this->createViews();
    }

    public function down()
    {
        $this->dropViews();

        $this->forge->dropTable('operation', true);
        $this->forge->dropTable('type_operation', true);
        $this->forge->dropTable('frais_barem', true);
        $this->forge->dropTable('client', true);
        $this->forge->dropTable('prefix_operateur', true);
        $this->forge->dropTable('operateur', true);
    }

    private function createViews()
    {
        $this->dropViews();

        // Solde actuel par client
        $this->db->query("
            CREATE VIEW v_solde_client AS
            WITH mouvements AS (
                SELECT
                    o.id_primary_client AS id_client,
                    CASE
                        WHEN LOWER(t.nom) = 'depot' THEN o.montant
                        WHEN LOWER(t.nom) IN ('retrait', 'transfaire') THEN -o.montant - o.montant_frais
                        ELSE 0
                    END AS mouvement
                FROM operation o
                JOIN type_operation t ON t.id = o.id_type_operation

                UNION ALL

                SELECT
                    o.id_secondary_client AS id_client,
                    o.montant AS mouvement
                FROM operation o
                JOIN type_operation t ON t.id = o.id_type_operation
                WHERE LOWER(t.nom) = 'transfaire'
                  AND o.id_secondary_client IS NOT NULL
            )
            SELECT
                c.id AS id_client,
                c.nom,
                c.prenom,
                po.prefix || c.code_client AS code_client_complet,
                COALESCE(SUM(m.mouvement), 0) AS solde_actuel
            FROM client c
            JOIN operateur op ON op.id = c.id_operateur
            JOIN prefix_operateur po ON po.id_operateur = op.id
            LEFT JOIN mouvements m ON m.id_client = c.id
            GROUP BY c.id, c.nom, c.prenom, po.prefix, c.code_client
        ");

        // Historique du solde cumulé par client
        $this->db->query("
            CREATE VIEW v_solde_client_historique AS
            WITH mouvements AS (
                SELECT
                    o.id AS id_operation,
                    o.date_operation,
                    o.id_primary_client AS id_client,
                    CASE
                        WHEN LOWER(t.nom) = 'depot' THEN o.montant
                        WHEN LOWER(t.nom) IN ('retrait', 'transfaire') THEN -o.montant - o.montant_frais
                        ELSE 0
                    END AS mouvement
                FROM operation o
                JOIN type_operation t ON t.id = o.id_type_operation

                UNION ALL

                SELECT
                    o.id AS id_operation,
                    o.date_operation,
                    o.id_secondary_client AS id_client,
                    o.montant AS mouvement
                FROM operation o
                JOIN type_operation t ON t.id = o.id_type_operation
                WHERE LOWER(t.nom) = 'transfaire'
                  AND o.id_secondary_client IS NOT NULL
            )
            SELECT
                m.id_operation,
                m.date_operation,
                c.id AS id_client,
                c.nom,
                c.prenom,
                po.prefix || c.code_client AS code_client_complet,
                m.mouvement,
                SUM(m.mouvement) OVER (
                    PARTITION BY c.id
                    ORDER BY m.date_operation, m.id_operation
                    ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                ) AS solde_cumule
            FROM mouvements m
            JOIN client c ON c.id = m.id_client
            JOIN operateur op ON op.id = c.id_operateur
            JOIN prefix_operateur po ON po.id_operateur = op.id
        ");

        // Solde le plus récent par client
        $this->db->query("
            CREATE VIEW v_solde_client_a_date AS
            SELECT *
            FROM (
                SELECT
                    v.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY v.id_client
                        ORDER BY v.date_operation DESC, v.id_operation DESC
                    ) AS rn
                FROM v_solde_client_historique v
            )
            WHERE rn = 1
        ");

        // Opérations détaillées par client
        $this->db->query("
            CREATE VIEW v_operation_client AS
            SELECT
                o.id AS id_operation,
                o.date_operation,
                o.montant,
                o.montant_frais,
                t.nom AS type_operation,
                t.code_type_operation,
                pc.id AS id_client_primaire,
                pc.nom AS nom_client_primaire,
                pc.prenom AS prenom_client_primaire,
                sc.id AS id_client_secondaire,
                sc.nom AS nom_client_secondaire,
                sc.prenom AS prenom_client_secondaire,
                op.nom AS operateur,
                po.prefix AS prefix_operateur
            FROM operation o
            JOIN type_operation t ON t.id = o.id_type_operation
            JOIN client pc ON pc.id = o.id_primary_client
            LEFT JOIN client sc ON sc.id = o.id_secondary_client
            JOIN operateur op ON op.id = pc.id_operateur
            JOIN prefix_operateur po ON po.id_operateur = op.id
            ORDER BY o.date_operation DESC
        ");

        // Liste des clients par opérateur
        $this->db->query("
            CREATE VIEW v_client_operateur AS
            SELECT
                op.id AS id_operateur,
                op.nom AS nom_operateur,
                po.prefix AS prefix_operateur,
                c.id AS id_client,
                c.nom,
                c.prenom,
                c.code_client
            FROM operateur op
            JOIN prefix_operateur po ON po.id_operateur = op.id
            JOIN client c ON c.id_operateur = op.id
            ORDER BY op.nom, c.nom
        ");
    }

    private function dropViews()
    {
        $this->db->query('DROP VIEW IF EXISTS v_client_operateur');
        $this->db->query('DROP VIEW IF EXISTS v_operation_client');
        $this->db->query('DROP VIEW IF EXISTS v_solde_client_a_date');
        $this->db->query('DROP VIEW IF EXISTS v_solde_client_historique');
        $this->db->query('DROP VIEW IF EXISTS v_solde_client');
    }
}
