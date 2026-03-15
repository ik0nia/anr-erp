-- Actualizare schema pentru câmpuri noi
-- Rulați acest script în phpMyAdmin pentru a adăuga câmpurile noi

USE `r74526anrb_internapp_crm`;

-- Adaugă coloana cidataexp dacă nu există
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'r74526anrb_internapp_crm' 
AND TABLE_NAME = 'membri' 
AND COLUMN_NAME = 'cidataexp';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE membri ADD COLUMN cidataexp DATE DEFAULT NULL AFTER cidataelib',
    'SELECT "Coloana cidataexp există deja" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adaugă coloana status_dosar dacă nu există
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'r74526anrb_internapp_crm' 
AND TABLE_NAME = 'membri' 
AND COLUMN_NAME = 'status_dosar';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE membri ADD COLUMN status_dosar ENUM(\'Activ\', \'Expirat\', \'Suspendat\', \'Retras\', \'Decedat\') DEFAULT \'Activ\' AFTER dosardata',
    'SELECT "Coloana status_dosar există deja" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modifică coloana status_dosar dacă există cu valori vechi
-- Această comandă va funcționa doar dacă nu există date incompatibile
ALTER TABLE membri MODIFY COLUMN status_dosar ENUM('Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat') DEFAULT 'Activ';

-- Adaugă coloana judet_domiciliu dacă nu există
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'r74526anrb_internapp_crm' 
AND TABLE_NAME = 'membri' 
AND COLUMN_NAME = 'judet_domiciliu';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE membri ADD COLUMN judet_domiciliu VARCHAR(50) DEFAULT NULL AFTER domloc',
    'SELECT "Coloana judet_domiciliu există deja" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modifică coloana hmotiv de la TEXT la VARCHAR(255) dacă este TEXT
SET @col_type = '';
SELECT DATA_TYPE INTO @col_type 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'r74526anrb_internapp_crm' 
AND TABLE_NAME = 'membri' 
AND COLUMN_NAME = 'hmotiv';

SET @sql = IF(@col_type = 'text',
    'ALTER TABLE membri MODIFY COLUMN hmotiv VARCHAR(255) DEFAULT NULL',
    'SELECT "Coloana hmotiv nu este TEXT sau nu există" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizează membrii care au status NULL la 'Activ'
UPDATE membri SET status_dosar = 'Activ' WHERE status_dosar IS NULL;

SELECT 'Actualizare completă! Toate coloanele necesare au fost adăugate/modificate.' AS message;
