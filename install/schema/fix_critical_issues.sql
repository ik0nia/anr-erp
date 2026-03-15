-- Script de rezolvare probleme critice identificate în audit
-- Rulați în phpMyAdmin sau MySQL CLI
-- ATENȚIE: Verificați că nu există date orfane înainte de a rula acest script

USE `r74526anrb_internapp_crm`;

-- ============================================
-- 1. ADĂUGARE FOREIGN KEYS LIPSĂ
-- ============================================

-- Verificare și adăugare FK pentru liste_prezenta_membri
-- Șterge FK-uri existente dacă există (pentru re-creare)
SET @exist := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
               WHERE CONSTRAINT_SCHEMA = 'r74526anrb_internapp_crm' 
               AND TABLE_NAME = 'liste_prezenta_membri' 
               AND CONSTRAINT_NAME = 'fk_lista');
SET @sqlstmt := IF(@exist > 0, 'ALTER TABLE liste_prezenta_membri DROP FOREIGN KEY fk_lista', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
               WHERE CONSTRAINT_SCHEMA = 'r74526anrb_internapp_crm' 
               AND TABLE_NAME = 'liste_prezenta_membri' 
               AND CONSTRAINT_NAME = 'fk_membru');
SET @sqlstmt := IF(@exist > 0, 'ALTER TABLE liste_prezenta_membri DROP FOREIGN KEY fk_membru', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adăugare FK pentru liste_prezenta_membri
ALTER TABLE liste_prezenta_membri 
  ADD CONSTRAINT fk_lista FOREIGN KEY (lista_id) REFERENCES liste_prezenta(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_membru FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE CASCADE;

-- Verificare și adăugare FK pentru activitati.lista_prezenta_id
SET @exist := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
               WHERE CONSTRAINT_SCHEMA = 'r74526anrb_internapp_crm' 
               AND TABLE_NAME = 'activitati' 
               AND CONSTRAINT_NAME = 'fk_lista_prezenta');
SET @sqlstmt := IF(@exist > 0, 'ALTER TABLE activitati DROP FOREIGN KEY fk_lista_prezenta', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE activitati 
  ADD CONSTRAINT fk_lista_prezenta FOREIGN KEY (lista_prezenta_id) REFERENCES liste_prezenta(id) ON DELETE SET NULL;

-- Verificare și adăugare FK pentru registru_interactiuni.subiect_id
SET @exist := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
               WHERE CONSTRAINT_SCHEMA = 'r74526anrb_internapp_crm' 
               AND TABLE_NAME = 'registru_interactiuni' 
               AND CONSTRAINT_NAME = 'fk_subiect');
SET @sqlstmt := IF(@exist > 0, 'ALTER TABLE registru_interactiuni DROP FOREIGN KEY fk_subiect', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE registru_interactiuni 
  ADD CONSTRAINT fk_subiect FOREIGN KEY (subiect_id) REFERENCES registru_interactiuni_subiecte(id) ON DELETE SET NULL;

-- ============================================
-- 2. ADĂUGARE INDEXURI PENTRU PERFORMANȚĂ
-- ============================================

-- Index compus pentru contacte (căutare după tip și nume)
CREATE INDEX IF NOT EXISTS idx_contacte_tip_nume ON contacte(tip_contact, nume);

-- Index compus pentru registru_interactiuni
CREATE INDEX IF NOT EXISTS idx_registru_tip_data ON registru_interactiuni(tip, data_ora);

-- Index pentru taskuri (căutare după finalizat și dată)
CREATE INDEX IF NOT EXISTS idx_taskuri_finalizat_data ON taskuri(finalizat, data_ora);

-- ============================================
-- 3. VERIFICARE INTEGRITATE DATE
-- ============================================

-- Verificare date orfane în liste_prezenta_membri
SELECT 'Date orfane liste_prezenta_membri (lista_id inexistent):' as verificare;
SELECT COUNT(*) as numar FROM liste_prezenta_membri lpm 
LEFT JOIN liste_prezenta lp ON lpm.lista_id = lp.id 
WHERE lp.id IS NULL;

SELECT 'Date orfane liste_prezenta_membri (membru_id inexistent):' as verificare;
SELECT COUNT(*) as numar FROM liste_prezenta_membri lpm 
LEFT JOIN membri m ON lpm.membru_id = m.id 
WHERE m.id IS NULL;

-- Verificare date orfane în activitati
SELECT 'Date orfane activitati (lista_prezenta_id inexistent):' as verificare;
SELECT COUNT(*) as numar FROM activitati a 
LEFT JOIN liste_prezenta lp ON a.lista_prezenta_id = lp.id 
WHERE a.lista_prezenta_id IS NOT NULL AND lp.id IS NULL;

-- Verificare date orfane în registru_interactiuni
SELECT 'Date orfane registru_interactiuni (subiect_id inexistent):' as verificare;
SELECT COUNT(*) as numar FROM registru_interactiuni ri 
LEFT JOIN registru_interactiuni_subiecte ris ON ri.subiect_id = ris.id 
WHERE ri.subiect_id IS NOT NULL AND ris.id IS NULL;

-- ============================================
-- 4. CURĂȚARE DATE ORFANE (OPȚIONAL - COMENTAT)
-- ============================================

-- DECOMENTAȚI DOAR DUPĂ VERIFICARE MANUALĂ!
-- DELETE FROM liste_prezenta_membri WHERE lista_id NOT IN (SELECT id FROM liste_prezenta);
-- DELETE FROM liste_prezenta_membri WHERE membru_id NOT IN (SELECT id FROM membri);
-- UPDATE activitati SET lista_prezenta_id = NULL WHERE lista_prezenta_id NOT IN (SELECT id FROM liste_prezenta);
-- UPDATE registru_interactiuni SET subiect_id = NULL WHERE subiect_id NOT IN (SELECT id FROM registru_interactiuni_subiecte);

-- ============================================
-- 5. VERIFICARE FINALĂ
-- ============================================

SELECT 'Verificare foreign keys create:' as status;
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE CONSTRAINT_SCHEMA = 'r74526anrb_internapp_crm'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
