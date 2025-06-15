<?php
/**
 * Configuration des sous-domaines pour GeekBoard
 * Ce fichier gère la détection automatique du magasin basé sur le sous-domaine
 */

// Fonction pour détecter le magasin basé sur le sous-domaine
function detectShopFromSubdomain() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    // Mapping des sous-domaines vers les shop_id
    $subdomain_mapping = [
        'cannes.mdgeek.top' => 4,
        'principal.mdgeek.top' => 1,
        'pscannes.mdgeek.top' => 2,
        // Ajouter d'autres sous-domaines si nécessaire
    ];
    
    // Vérifier si l'hôte correspond à un sous-domaine configuré
    if (isset($subdomain_mapping[$host])) {
        return $subdomain_mapping[$host];
    }
    
    // Si aucun sous-domaine spécifique, retourner null
    return null;
}

// Détecter automatiquement le magasin si pas encore défini en session
if (!isset($_SESSION['shop_id'])) {
    $detected_shop_id = detectShopFromSubdomain();
    
    if ($detected_shop_id) {
        // Vérifier que le magasin existe et est actif
        try {
            require_once __DIR__ . '/database.php';
            $pdo_main = getMainDBConnection();
            $stmt = $pdo_main->prepare("SELECT id, name FROM shops WHERE id = ? AND active = 1");
            $stmt->execute([$detected_shop_id]);
            $shop = $stmt->fetch();
            
            if ($shop) {
                $_SESSION['shop_id'] = $shop['id'];
                $_SESSION['shop_name'] = $shop['name'];
                error_log("Shop ID automatiquement détecté depuis sous-domaine: " . $shop['id'] . " (" . $shop['name'] . ")");
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la détection automatique du magasin: " . $e->getMessage());
        }
    }
}
?> 