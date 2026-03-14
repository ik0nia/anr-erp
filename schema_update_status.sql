-- Actualizare status_dosar cu noile valori
-- Rulați acest script în phpMyAdmin pentru a actualiza status_dosar

USE `r74526anrb_internapp_crm`;

-- Verifică dacă coloana status_dosar există, dacă nu o adaugă
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'r74526anrb_internapp_crm' 
AND TABLE_NAME = 'membri' 
AND COLUMN_NAME = 'status_dosar';

-- Dacă coloana nu există, o adaugă
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE membri ADD COLUMN status_dosar ENUM(\'Activ\', \'Expirat\', \'Suspendat\', \'Retras\', \'Decedat\') DEFAULT \'Activ\' AFTER dosardata',
    'SELECT "Coloana status_dosar există deja" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Dacă coloana există deja cu valori vechi, o modifică
-- Această comandă va funcționa doar dacă nu există date incompatibile
ALTER TABLE membri MODIFY COLUMN status_dosar ENUM('Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat') DEFAULT 'Activ';

-- Actualizează membrii care au status NULL sau valori vechi la 'Activ'
UPDATE membri SET status_dosar = 'Activ' WHERE status_dosar IS NULL OR status_dosar NOT IN ('Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat');
