-- Script de mise à jour des magasins pour le serveur
-- Base principale : geekboard_general

USE geekboard_general;

-- Mise à jour de tous les magasins vers localhost avec les nouvelles conventions
UPDATE shops SET 
    db_host = 'localhost',
    db_port = '3306',
    db_user = 'root',
    db_pass = 'Mamanmaman01#',
    db_name = CASE 
        -- Correspondances spécifiques
        WHEN db_name = 'u139954273_Vscodetest' OR name = 'DatabaseGeneral' THEN 'geekboard_general'
        WHEN db_name = 'u139954273_cannesphones' OR subdomain = 'cannesphones' THEN 'geekboard_cannesphones'
        WHEN db_name = 'u139954273_pscannes' OR subdomain = 'pscannes' THEN 'geekboard_pscannes'
        WHEN subdomain = 'psphonac' THEN 'geekboard_psphonac'
        -- Cas génériques pour les nouvelles bases
        WHEN subdomain IS NOT NULL AND subdomain != '' THEN CONCAT('geekboard_', subdomain)
        ELSE db_name
    END
WHERE active = 1;

-- Ajouter des magasins manquants si nécessaire
INSERT IGNORE INTO shops (name, subdomain, db_host, db_port, db_name, db_user, db_pass, active) VALUES 
    ('MD Geek Principal', 'mdgeek', 'localhost', '3306', 'geekboard_general', 'root', 'Mamanmaman01#', 1),
    ('MD Geek', '', 'localhost', '3306', 'geekboard_general', 'root', 'Mamanmaman01#', 1);

-- Vérification des mises à jour
SELECT 
    '--- Configuration finale des magasins ---' as info;
    
SELECT 
    id,
    name,
    subdomain,
    CONCAT(db_host, ':', db_port) as serveur,
    db_name as base_donnees,
    db_user as utilisateur,
    CASE WHEN active = 1 THEN 'Actif' ELSE 'Inactif' END as statut
FROM shops
ORDER BY id; 
-- Base principale : geekboard_general

USE geekboard_general;

-- Mise à jour de tous les magasins vers localhost avec les nouvelles conventions
UPDATE shops SET 
    db_host = 'localhost',
    db_port = '3306',
    db_user = 'root',
    db_pass = 'Mamanmaman01#',
    db_name = CASE 
        -- Correspondances spécifiques
        WHEN db_name = 'u139954273_Vscodetest' OR name = 'DatabaseGeneral' THEN 'geekboard_general'
        WHEN db_name = 'u139954273_cannesphones' OR subdomain = 'cannesphones' THEN 'geekboard_cannesphones'
        WHEN db_name = 'u139954273_pscannes' OR subdomain = 'pscannes' THEN 'geekboard_pscannes'
        WHEN subdomain = 'psphonac' THEN 'geekboard_psphonac'
        -- Cas génériques pour les nouvelles bases
        WHEN subdomain IS NOT NULL AND subdomain != '' THEN CONCAT('geekboard_', subdomain)
        ELSE db_name
    END
WHERE active = 1;

-- Ajouter des magasins manquants si nécessaire
INSERT IGNORE INTO shops (name, subdomain, db_host, db_port, db_name, db_user, db_pass, active) VALUES 
    ('MD Geek Principal', 'mdgeek', 'localhost', '3306', 'geekboard_general', 'root', 'Mamanmaman01#', 1),
    ('MD Geek', '', 'localhost', '3306', 'geekboard_general', 'root', 'Mamanmaman01#', 1);

-- Vérification des mises à jour
SELECT 
    '--- Configuration finale des magasins ---' as info;
    
SELECT 
    id,
    name,
    subdomain,
    CONCAT(db_host, ':', db_port) as serveur,
    db_name as base_donnees,
    db_user as utilisateur,
    CASE WHEN active = 1 THEN 'Actif' ELSE 'Inactif' END as statut
FROM shops
ORDER BY id; 