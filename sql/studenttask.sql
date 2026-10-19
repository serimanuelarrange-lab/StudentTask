-- =====================================================================
-- StudentTask : script de création de la base de données
-- Compatible MySQL 5.7+ et MariaDB 10.4 (XAMPP)
-- Ce script ne supprime rien : il peut être importé sans risque.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS studenttask
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenttask;

-- ---------------------------------------------------------------------
-- Table : utilisateurs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id_utilisateur    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom               VARCHAR(100) NOT NULL,
    email             VARCHAR(150) NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    date_creation     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_utilisateur),
    UNIQUE KEY uq_utilisateurs_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table : projets
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projets (
    id_projet     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre         VARCHAR(150) NOT NULL,
    description   TEXT         NULL,
    date_debut    DATE         NOT NULL,
    date_limite   DATE         NOT NULL,
    archive       TINYINT(1)   NOT NULL DEFAULT 0,
    id_createur   INT UNSIGNED NOT NULL,
    date_creation DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_projet),
    KEY idx_projets_createur (id_createur),
    CONSTRAINT fk_projets_createur
        FOREIGN KEY (id_createur) REFERENCES utilisateurs (id_utilisateur)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_projets_dates CHECK (date_limite >= date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table : membres_projet (liaison entre projets et utilisateurs)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS membres_projet (
    id_projet      INT UNSIGNED NOT NULL,
    id_utilisateur INT UNSIGNED NOT NULL,
    role           ENUM('chef_projet', 'membre') NOT NULL DEFAULT 'membre',
    date_ajout     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_projet, id_utilisateur),
    KEY idx_membres_utilisateur (id_utilisateur),
    CONSTRAINT fk_membres_projet
        FOREIGN KEY (id_projet) REFERENCES projets (id_projet)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_membres_utilisateur
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id_utilisateur)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table : taches
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS taches (
    id_tache      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_projet     INT UNSIGNED NOT NULL,
    id_assigne    INT UNSIGNED NULL,
    titre         VARCHAR(150) NOT NULL,
    description   TEXT         NULL,
    statut        ENUM('a_faire', 'en_cours', 'termine') NOT NULL DEFAULT 'a_faire',
    priorite      ENUM('basse', 'moyenne', 'haute')      NOT NULL DEFAULT 'moyenne',
    date_limite   DATE         NOT NULL,
    date_creation DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_tache),
    KEY idx_taches_projet (id_projet),
    KEY idx_taches_assigne (id_assigne),
    KEY idx_taches_statut (statut),
    KEY idx_taches_date_limite (date_limite),
    CONSTRAINT fk_taches_projet
        FOREIGN KEY (id_projet) REFERENCES projets (id_projet)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_taches_assigne
        FOREIGN KEY (id_assigne) REFERENCES utilisateurs (id_utilisateur)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS reinitialisations_mdp (
    id_reinit       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_utilisateur  INT UNSIGNED     NOT NULL,
    code_hash       VARCHAR(255)     NOT NULL,
    date_creation   DATETIME         NOT NULL,
    date_expiration DATETIME         NOT NULL,
    tentatives      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    utilise         TINYINT(1)       NOT NULL DEFAULT 0,
    PRIMARY KEY (id_reinit),
    KEY idx_reinit_utilisateur (id_utilisateur),
    CONSTRAINT fk_reinit_utilisateur
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id_utilisateur)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;