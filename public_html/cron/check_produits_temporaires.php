<?php
// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Récupérer les produits temporaires qui ont plus de 12 jours
    $stmt = $pdo->prepare("
        SELECT id, nom, reference, created_at
        FROM produits
        WHERE status = 'temporaire'
        AND DATEDIFF(NOW(), created_at) >= 12
    ");
    $stmt->execute();
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($produits as $produit) {
        // Mettre à jour le statut du produit
        $stmt = $pdo->prepare("
            UPDATE produits 
            SET status = 'a_retourner',
                date_limite_retour = DATE_ADD(NOW(), INTERVAL 7 DAY),
                motif_retour = 'Délai de 12 jours dépassé'
            WHERE id = ?
        ");
        $stmt->execute([$produit['id']]);
        
        // Créer un retour automatique si la table retours existe
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as existe
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = 'retours'
        ");
        $stmt->execute();
        $table_exists = $stmt->fetch()['existe'] > 0;
        
        if ($table_exists) {
            $stmt = $pdo->prepare("
                INSERT INTO retours (
                    produit_id,
                    date_creation,
                    date_limite,
                    statut,
                    notes
                ) VALUES (
                    ?,
                    NOW(),
                    DATE_ADD(NOW(), INTERVAL 7 DAY),
                    'en_attente',
                    'Retour automatique après 12 jours'
                )
            ");
            $stmt->execute([$produit['id']]);
        }
        
        // Enregistrer dans les logs
        $stmt = $pdo->prepare("
            INSERT INTO journal_actions (
                type_action,
                description,
                date_action,
                user_id
            ) VALUES (
                'retour_auto',
                ?,
                NOW(),
                1
            )
        ");
        $description = sprintf(
            "Produit '%s' (ref: %s) marqué pour retour automatique après 12 jours",
            $produit['nom'],
            $produit['reference']
        );
        $stmt->execute([$description]);
    }
    
    $pdo->commit();
    
    // Afficher le résultat
    echo "Vérification terminée. " . count($produits) . " produit(s) marqué(s) pour retour.\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erreur lors de la vérification des produits temporaires: " . $e->getMessage() . "\n";
} 