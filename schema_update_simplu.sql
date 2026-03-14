-- Script simplificat pentru adăugarea coloanelor lipsă
-- Rulați acest script în phpMyAdmin

USE `r74526anrb_internapp_crm`;

-- Adaugă coloana status_dosar (cea mai importantă - cauzează eroarea)
ALTER TABLE membri ADD COLUMN status_dosar ENUM('Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat') DEFAULT 'Activ' AFTER dosardata;

-- Adaugă coloana cidataexp
ALTER TABLE membri ADD COLUMN cidataexp DATE DEFAULT NULL AFTER cidataelib;

-- Adaugă coloana judet_domiciliu
ALTER TABLE membri ADD COLUMN judet_domiciliu VARCHAR(50) DEFAULT NULL AFTER domloc;

-- Modifică coloana hmotiv de la TEXT la VARCHAR(255) dacă este TEXT
ALTER TABLE membri MODIFY COLUMN hmotiv VARCHAR(255) DEFAULT NULL;

-- Actualizează membrii care au status NULL la 'Activ'
UPDATE membri SET status_dosar = 'Activ' WHERE status_dosar IS NULL;
