-- =========================================================
-- RECONSTRUCTION ET INJECTION DES DONNÉES DE TEST
-- =========================================================

PRAGMA foreign_keys = OFF;

-- Suppression des anciennes vues pour éviter les conflits de réécriture
DROP VIEW IF EXISTS v_client_operateur;
DROP VIEW IF EXISTS v_operation_client;
DROP VIEW IF EXISTS v_solde_client_a_date;
DROP VIEW IF EXISTS v_solde_client_historique;
DROP VIEW IF EXISTS v_solde_client;

-- Nettoyage complet des anciennes tables
DROP TABLE IF EXISTS operation;
DROP TABLE IF EXISTS client;
DROP TABLE IF EXISTS prefix_operateur;
DROP TABLE IF EXISTS operateur;
DROP TABLE IF EXISTS type_operation;
DROP TABLE IF EXISTS frais_barem;

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------
-- CRÉATION DE TES TABLES (STRICTEMENT IDENTIQUES)
-- ---------------------------------------------------------

CREATE TABLE operateur(
                          id INTEGER PRIMARY KEY AUTOINCREMENT,
                          nom TEXT NOT NULL,
                          comission_ptc REAL NOT NULL
);

CREATE TABLE prefix_operateur(
                                 id INTEGER PRIMARY KEY AUTOINCREMENT,
                                 prefix VARCHAR(3) NOT NULL UNIQUE,
                                 id_operateur INTEGER NOT NULL,
                                 FOREIGN KEY (id_operateur) REFERENCES operateur(id)
);

CREATE TABLE client(
                       id INTEGER PRIMARY KEY AUTOINCREMENT,
                       nom TEXT NOT NULL,
                       prenom TEXT NOT NULL,
                       code_client VARCHAR(10) NOT NULL UNIQUE,
                       code_secret VARCHAR(4) NOT NULL,
                       id_operateur INTEGER NOT NULL,
                       FOREIGN KEY (id_operateur) REFERENCES operateur(id)
);

CREATE TABLE frais_barem(
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            montant REAL NOT NULL,
                            min_montant REAL NOT NULL,
                            max_montant REAL NOT NULL
);

CREATE TABLE type_operation(
                               id INTEGER PRIMARY KEY AUTOINCREMENT,
                               nom TEXT NOT NULL,
                               code_type_operation INTEGER NOT NULL UNIQUE
);

CREATE TABLE operation(
                          id INTEGER PRIMARY KEY AUTOINCREMENT,
                          id_primary_client INTEGER NOT NULL,
                          id_secondary_client INTEGER,
                          id_type_operation INTEGER NOT NULL,
                          montant REAL NOT NULL,
                          montant_frais REAL NOT NULL,
                          montant_comission REAL NOT NULL,
                          date_operation TEXT NOT NULL,
                          FOREIGN KEY (id_primary_client) REFERENCES client(id),
                          FOREIGN KEY (id_secondary_client) REFERENCES client(id),
                          FOREIGN KEY (id_type_operation) REFERENCES type_operation(id)
);

-- ---------------------------------------------------------
-- INDEX
-- ---------------------------------------------------------

CREATE INDEX idx_client_operateur ON client(id_operateur);
CREATE INDEX idx_prefix_operateur ON prefix_operateur(id_operateur);
CREATE INDEX idx_operation_primary_client ON operation(id_primary_client);
CREATE INDEX idx_operation_secondary_client ON operation(id_secondary_client);
CREATE INDEX idx_operation_type ON operation(id_type_operation);
CREATE INDEX idx_operation_date ON operation(date_operation);

-- ---------------------------------------------------------
-- CRÉATION DE TES VUES (STRICTEMENT IDENTIQUES)
-- ---------------------------------------------------------

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
GROUP BY c.id, c.nom, c.prenom, po.prefix, c.code_client;

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
         JOIN prefix_operateur po ON po.id_operateur = op.id;

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
WHERE rn = 1;

CREATE VIEW v_operation_client AS
SELECT
    o.id AS id_operation,
    o.date_operation,
    o.montant,
    o.montant_frais,
    o.montant_comission,
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
ORDER BY o.date_operation DESC;

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
ORDER BY op.nom, c.nom;

-- ---------------------------------------------------------
-- INJECTION DES DONNÉES DE TEST DIRECTEMENT ADAPTÉES
-- ---------------------------------------------------------

-- Insertion des Opérateurs (comission_ptc présente)
INSERT INTO operateur (id, nom, comission_ptc) VALUES
                                                   (1, 'Telma (Local)', 0.0),
                                                   (2, 'Orange Money', 2.0),
                                                   (3, 'Airtel Money', 1.5);

-- Insertion des Préfixes
INSERT INTO prefix_operateur (id, prefix, id_operateur) VALUES
                                                            (1, '034', 1),
                                                            (2, '032', 2),
                                                            (3, '033', 3);

-- Insertion des Types Opérations
INSERT INTO type_operation (id, nom, code_type_operation) VALUES
                                                              (1, 'depot', 10),
                                                              (2, 'retrait', 20),
                                                              (3, 'transfaire', 30);

-- Insertion des Clients
INSERT INTO client (id, nom, prenom, code_client, code_secret, id_operateur) VALUES
                                                                                 (1, 'Raza', 'Jean', '1111111', '1234', 1),
                                                                                 (2, 'Ranaivo', 'Paul', '2222222', '1234', 1),
                                                                                 (3, 'Rakoto', 'Bako', '3333333', '1234', 2),
                                                                                 (4, 'Andria', 'Soleil', '4444444', '1234', 3);

-- Insertion des Barèmes
INSERT INTO frais_barem (id, montant, min_montant, max_montant) VALUES
                                                                    (1, 500.0, 1000.0, 50000.0),
                                                                    (2, 1000.0, 50001.0, 100000.0),
                                                                    (3, 2000.0, 100001.0, 500000.0);

-- Insertion des Opérations réelles
INSERT INTO operation (id, id_primary_client, id_secondary_client, id_type_operation, montant, montant_frais, montant_comission, date_operation)
VALUES (1, 1, NULL, 1, 500000.0, 0.0, 0.0, '2026-07-15 09:00:00');

INSERT INTO operation (id, id_primary_client, id_secondary_client, id_type_operation, montant, montant_frais, montant_comission, date_operation)
VALUES (2, 2, NULL, 1, 300000.0, 0.0, 0.0, '2026-07-15 09:30:00');

INSERT INTO operation (id, id_primary_client, id_secondary_client, id_type_operation, montant, montant_frais, montant_comission, date_operation)
VALUES (3, 1, NULL, 2, 40000.0, 500.0, 0.0, '2026-07-16 14:00:00');

INSERT INTO operation (id, id_primary_client, id_secondary_client, id_type_operation, montant, montant_frais, montant_comission, date_operation)
VALUES (4, 1, 2, 3, 80000.0, 1000.0, 0.0, '2026-07-17 10:15:00');

INSERT INTO operation (id, id_primary_client, id_secondary_client, id_type_operation, montant, montant_frais, montant_comission, date_operation)
VALUES (5, 1, 3, 3, 200000.0, 2000.0, 4000.0, '2026-07-18 11:00:00');

INSERT INTO operation (id, id_primary_client, id_secondary_client, id_type_operation, montant, montant_frais, montant_comission, date_operation)
VALUES (6, 2, 4, 3, 100000.0, 1000.0, 1500.0, '2026-07-19 15:45:00');