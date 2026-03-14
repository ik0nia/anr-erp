-- Membri de test pentru CRM ANR Bihor
-- Rulați acest script în phpMyAdmin după ce ați rulat schema.sql

USE `r74526anrb_internapp_crm`;

-- Membru 1: Ion Popescu
INSERT INTO membri (
    dosarnr, dosardata, nume, prenume, telefonnev, telefonapartinator, email,
    datanastere, locnastere, judnastere, ciseria, cinumar, cielib, cidataelib,
    gdpr, codpost, tipmediuur, domloc, domstr, domnr, dombl, domsc, domet, domap,
    sex, hgrad, hmotiv, hdur, cnp, cenr, cedata, ceexp, primaria, notamembru
) VALUES (
    'DOS-2024-001',
    '2024-01-15',
    'Popescu',
    'Ion',
    '0721234567',
    '0721234568',
    'ion.popescu@example.com',
    '1988-03-22',
    'Oradea',
    'Bihor',
    'AB',
    '123456',
    'Primăria Oradea',
    '2010-06-15',
    1,
    '410001',
    'Urban',
    'Oradea',
    'Calea Republicii',
    '15',
    NULL,
    'A',
    '2',
    '10',
    'Masculin',
    'Mediu',
    'Deficiență de vedere',
    'Permanent',
    '1880322055052',
    'CH-2024-001',
    '2024-01-10',
    '2029-01-10',
    'Primăria Oradea',
    'Membru activ, participă la toate activitățile.'
);

-- Membru 2: Maria Ionescu
INSERT INTO membri (
    dosarnr, dosardata, nume, prenume, telefonnev, telefonapartinator, email,
    datanastere, locnastere, judnastere, ciseria, cinumar, cielib, cidataelib,
    gdpr, codpost, tipmediuur, domloc, domstr, domnr, dombl, domsc, domet, domap,
    sex, hgrad, hmotiv, hdur, cnp, cenr, cedata, ceexp, primaria, notamembru
) VALUES (
    'DOS-2024-002',
    '2024-02-20',
    'Ionescu',
    'Maria',
    '0729876543',
    '0729876544',
    'maria.ionescu@example.com',
    '1988-03-22',
    'Beiuș',
    'Bihor',
    'BH',
    '654321',
    'Primăria Beiuș',
    DATE_SUB(DATE_ADD(NOW(), INTERVAL 2 MONTH), INTERVAL 10 YEAR),
    0,
    '415100',
    'Rural',
    'Beiuș',
    'Strada Principală',
    '42',
    NULL,
    NULL,
    NULL,
    NULL,
    'Feminin',
    'Usor',
    'Deficiență de vedere parțială',
    'Revizuibil',
    '1880322055052',
    'CH-2024-002',
    '2024-02-15',
    DATE_ADD(NOW(), INTERVAL 3 MONTH),
    'Primăria Beiuș',
    'Membru nou, necesită suport suplimentar.'
);
