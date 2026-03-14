-- Actualizare schema taskuri pentru a adăuga utilizator_id
-- Rulați acest script în phpMyAdmin

USE `r74526anrb_internapp_crm`;

-- Adaugă coloana utilizator_id dacă nu există
ALTER TABLE taskuri 
ADD COLUMN IF NOT EXISTS utilizator_id INT DEFAULT NULL AFTER created_at,
ADD INDEX IF NOT EXISTS idx_utilizator_id (utilizator_id);
