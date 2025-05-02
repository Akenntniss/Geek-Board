-- Script pour supprimer les données de test

-- Désactiver les contraintes de clé étrangère temporairement
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Nettoyage des logs (journal_actions)
TRUNCATE TABLE journal_actions;

-- 2. Nettoyage des réparations
DELETE FROM reparations;

-- 3. Nettoyage des logs de réparation
DELETE FROM reparation_logs;

-- 4. Nettoyage des clients
DELETE FROM clients;

-- 5. Nettoyage des commandes
-- Commandes fournisseurs
DELETE FROM commandes_fournisseurs;

-- Lignes de commande fournisseur
DELETE FROM lignes_commande_fournisseur;

-- Commandes pièces
DELETE FROM commandes_pieces;

-- 6. Nettoyage des logs SMS
DELETE FROM sms_logs;

-- 7. Nettoyage des logs de réparation SMS
DELETE FROM reparation_sms;

-- 8. Nettoyage de l'historique
-- Historique des soldes
DELETE FROM historique_soldes;

-- Historique du stock
DELETE FROM stock_history;

-- 9. Nettoyage des photos de réparation
DELETE FROM photos_reparation;

-- Réactiver les contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 1; 