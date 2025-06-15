<?php
// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

try {
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
        // Récupérer les produits temporaires qui ont plus de 12 jours
    $stmt = $shop_pdo->prepare("
        SELECT id, name, barcode, date_created
        FROM stock
        WHERE status = 'temporaire'
        AND DATEDIFF(NOW(), date_created) >= 12
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        // Mettre à jour le statut du produit
        $stmt = $shop_pdo->prepare("
            UPDATE stock 
            SET status = 'a_retourner',
                date_limite_retour = DATE_ADD(NOW(), INTERVAL 7 DAY),
                motif_retour = 'Délai de 12 jours dépassé'
            WHERE id = ?
        ");
        $stmt->execute([$product['id']]);
        
        // Créer un retour automatique
        $stmt = $shop_pdo->prepare("
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
        $stmt->execute([$product['id']]);
        
        // Enregistrer dans les logs
        $stmt = $shop_pdo->prepare("
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
            "Produit '%s' (code: %s) marqué pour retour automatique après 12 jours",
            $product['name'],
            $product['barcode']
        );
        $stmt->execute([$description]);
    }

    $shop_pdo->commit();
    
    // Afficher le résultat
    echo "Vérification terminée. " . count($products) . " produit(s) marqué(s) pour retour.\n";
    
} catch (Exception $e) {
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    echo "Erreur lors de la vérification des produits temporaires: " . $e->getMessage() . "\n";
} 