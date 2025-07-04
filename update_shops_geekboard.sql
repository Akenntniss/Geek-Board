-- Script de mise à jour des magasins pour la migration GeekBoard
-- De Hostinger (u139954273_*) vers Localhost (geekboard_*)

USE geekboard_main;

-- Mise à jour de la configuration des magasins existants
UPDATE shops SET 
    db_host = 'localhost',
    db_port = '3306',
    db_user = 'root',
    db_pass = '',
    db_name = CASE 
        WHEN db_name = 'u139954273_Vscodetest' THEN 'geekboard_main'
        WHEN db_name = 'u139954273_cannesphones' THEN 'geekboard_cannesphones'
        WHEN db_name = 'u139954273_pscannes' THEN 'geekboard_pscannes'
        WHEN db_name = 'u139954273_mdgeek' THEN 'geekboard_mdgeek'
        ELSE REPLACE(db_name, 'u139954273_', 'geekboard_')
    END
WHERE db_name LIKE 'u139954273_%' OR db_host != 'localhost';

-- Vérification des mises à jour
SELECT 
    id,
    name,
    subdomain,
    db_host,
    db_name,
    db_user,
    active
FROM shops
ORDER BY id;

-- Insertion de magasins d'exemple si la table est vide
INSERT IGNORE INTO shops (
    name, subdomain, db_host, db_port, db_name, db_user, db_pass, active
) VALUES 
    ('Cannes Phones', 'cannesphones', 'localhost', '3306', 'geekboard_cannesphones', 'root', '', 1),
    ('PS Cannes', 'pscannes', 'localhost', '3306', 'geekboard_pscannes', 'root', '', 1),
    ('MD Geek', 'mdgeek', 'localhost', '3306', 'geekboard_mdgeek', 'root', '', 1);

-- Affichage final de la configuration
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
-- De Hostinger (u139954273_*) vers Localhost (geekboard_*)

USE geekboard_main;

-- Mise à jour de la configuration des magasins existants
UPDATE shops SET 
    db_host = 'localhost',
    db_port = '3306',
    db_user = 'root',
    db_pass = '',
    db_name = CASE 
        WHEN db_name = 'u139954273_Vscodetest' THEN 'geekboard_main'
        WHEN db_name = 'u139954273_cannesphones' THEN 'geekboard_cannesphones'
        WHEN db_name = 'u139954273_pscannes' THEN 'geekboard_pscannes'
        WHEN db_name = 'u139954273_mdgeek' THEN 'geekboard_mdgeek'
        ELSE REPLACE(db_name, 'u139954273_', 'geekboard_')
    END
WHERE db_name LIKE 'u139954273_%' OR db_host != 'localhost';

-- Vérification des mises à jour
SELECT 
    id,
    name,
    subdomain,
    db_host,
    db_name,
    db_user,
    active
FROM shops
ORDER BY id;

-- Insertion de magasins d'exemple si la table est vide
INSERT IGNORE INTO shops (
    name, subdomain, db_host, db_port, db_name, db_user, db_pass, active
) VALUES 
    ('Cannes Phones', 'cannesphones', 'localhost', '3306', 'geekboard_cannesphones', 'root', '', 1),
    ('PS Cannes', 'pscannes', 'localhost', '3306', 'geekboard_pscannes', 'root', '', 1),
    ('MD Geek', 'mdgeek', 'localhost', '3306', 'geekboard_mdgeek', 'root', '', 1);

-- Affichage final de la configuration
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