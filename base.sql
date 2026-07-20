-- =========================================================
-- SCHEMA COMPLET - Base SQLite
-- =========================================================

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------
-- TABLES
-- ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS operateur(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS prefix_operateur(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prefix VARCHAR(3) NOT NULL UNIQUE,
    id_operateur INTEGER NOT NULL,
    FOREIGN KEY (id_operateur) REFERENCES operateur(id)
);

CREATE TABLE IF NOT EXISTS client(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    prenom TEXT NOT NULL,
    code_client VARCHAR(10) NOT NULL UNIQUE,
    code_secret VARCHAR(4) NOT NULL,
    id_operateur INTEGER NOT NULL,
    FOREIGN KEY (id_operateur) REFERENCES operateur(id)
);

CREATE TABLE IF NOT EXISTS frais_barem(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    montant REAL NOT NULL,
    min_montant REAL NOT NULL,
    max_montant REAL NOT NULL
);

CREATE TABLE IF NOT EXISTS type_operation(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    code_type_operation INTEGER NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS operation(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_primary_client INTEGER NOT NULL,
    id_secondary_client INTEGER,
    id_type_operation INTEGER NOT NULL,
    montant REAL NOT NULL,
    montant_frais REAL NOT NULL,
    date_operation TEXT NOT NULL,
    FOREIGN KEY (id_primary_client) REFERENCES client(id),
    FOREIGN KEY (id_secondary_client) REFERENCES client(id),
    FOREIGN KEY (id_type_operation) REFERENCES type_operation(id)
);

-- ---------------------------------------------------------
-- INDEX
-- ---------------------------------------------------------

CREATE INDEX IF NOT EXISTS idx_client_operateur ON client(id_operateur);
CREATE INDEX IF NOT EXISTS idx_prefix_operateur ON prefix_operateur(id_operateur);
CREATE INDEX IF NOT EXISTS idx_operation_primary_client ON operation(id_primary_client);
CREATE INDEX IF NOT EXISTS idx_operation_secondary_client ON operation(id_secondary_client);
CREATE INDEX IF NOT EXISTS idx_operation_type ON operation(id_type_operation);
CREATE INDEX IF NOT EXISTS idx_operation_date ON operation(date_operation);

-- ---------------------------------------------------------
-- VUES
-- ---------------------------------------------------------

-- Solde actuel par client
DROP VIEW IF EXISTS v_solde_client;
CREATE VIEW v_solde_client AS
WITH mouvements AS (
    SELECT
        o.id_primary_client AS id_client,
        CASE
            WHEN LOWER(t.nom) = 'depot' THEN o.montant
            WHEN LOWER(t.nom) IN ('retrait', 'transfaire') THEN -o.montant - o.montant_frais - o.montant_comission
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

-- Historique du solde cumulé (running balance) par client
DROP VIEW IF EXISTS v_solde_client_historique;
CREATE VIEW v_solde_client_historique AS
WITH mouvements AS (
    SELECT
        o.id AS id_operation,
        o.date_operation,
        o.id_primary_client AS id_client,
        CASE
            WHEN LOWER(t.nom) = 'depot' THEN o.montant
            WHEN LOWER(t.nom) IN ('retrait', 'transfaire') THEN -o.montant - o.montant_frais - o.montant_comission
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

-- Solde le plus récent par client
DROP VIEW IF EXISTS v_solde_client_a_date;
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

-- Opérations détaillées par client
DROP VIEW IF EXISTS v_operation_client;
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
ORDER BY o.date_operation DESC;

-- Liste des clients par opérateur
DROP VIEW IF EXISTS v_client_operateur;
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
