<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Vérifier la session
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Non autorisé');
}

// Récupérer les paramètres
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
$period = isset($_GET['period']) ? $_GET['period'] : 'today';
$groupBySupplier = isset($_GET['groupBySupplier']) ? filter_var($_GET['groupBySupplier'], FILTER_VALIDATE_BOOLEAN) : true;
$includePrices = isset($_GET['includePrices']) ? filter_var($_GET['includePrices'], FILTER_VALIDATE_BOOLEAN) : true;
$onlyActiveCommands = isset($_GET['onlyActiveCommands']) ? filter_var($_GET['onlyActiveCommands'], FILTER_VALIDATE_BOOLEAN) : true;

// Dates personnalisées
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;

// Configurer les dates en fonction de la période sélectionnée
$dateStart = '';
$dateEnd = '';
$periodLabel = '';

switch ($period) {
    case 'today':
        $dateStart = date('Y-m-d');
        $dateEnd = date('Y-m-d 23:59:59');
        $periodLabel = "Aujourd'hui (" . date('d/m/Y') . ")";
        break;
    case 'week':
        // Lundi de la semaine courante au dimanche
        $dateStart = date('Y-m-d', strtotime('monday this week'));
        $dateEnd = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $periodLabel = "Semaine du " . date('d/m/Y', strtotime('monday this week')) . " au " . date('d/m/Y', strtotime('sunday this week'));
        break;
    case 'month':
        // Premier jour du mois courant à la fin du mois
        $dateStart = date('Y-m-01');
        $dateEnd = date('Y-m-t 23:59:59');
        $periodLabel = "Mois de " . date('F Y');
        break;
    case 'custom':
        if ($startDate && $endDate) {
            $dateStart = $startDate;
            $dateEnd = date('Y-m-d 23:59:59', strtotime($endDate));
            $periodLabel = "Du " . date('d/m/Y', strtotime($startDate)) . " au " . date('d/m/Y', strtotime($endDate));
        } else {
            http_response_code(400);
            exit('Dates personnalisées manquantes');
        }
        break;
    default:
        $dateStart = date('Y-m-d');
        $dateEnd = date('Y-m-d 23:59:59');
        $periodLabel = "Aujourd'hui (" . date('d/m/Y') . ")";
}

// Construire la requête SQL
$sql = "SELECT cp.*, c.nom as client_nom, c.prenom as client_prenom, 
        f.nom as fournisseur_nom, r.id as reparation_id, 
        r.type_appareil, r.marque, r.modele,
        COALESCE(cp.date_commande, '-') as date_commande 
        FROM commandes_pieces cp 
        LEFT JOIN clients c ON cp.client_id = c.id 
        LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id 
        LEFT JOIN reparations r ON cp.reparation_id = r.id 
        WHERE cp.date_creation BETWEEN :date_start AND :date_end";

if ($onlyActiveCommands) {
    $sql .= " AND cp.statut IN ('en_attente', 'commande', 'recue', 'urgent')";
}

// Tri par fournisseur ou par date
if ($groupBySupplier) {
    $sql .= " ORDER BY f.nom, cp.date_creation DESC";
} else {
    $sql .= " ORDER BY cp.date_creation DESC";
}

// Exécuter la requête
try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':date_start', $dateStart);
    $stmt->bindParam(':date_end', $dateEnd);
    $stmt->execute();
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    http_response_code(500);
    exit('Erreur lors de la récupération des données');
}

// Si aucune commande trouvée
if (empty($commandes)) {
    http_response_code(404);
    exit('Aucune commande trouvée pour cette période');
}

// Générer le nom du fichier
$timestamp = date('YmdHis');
$filename = "Commandes_{$period}_{$timestamp}";

// Statuts pour l'affichage
$statuts = [
    'en_attente' => 'En attente',
    'commande' => 'Commandé',
    'recue' => 'Reçu',
    'urgent' => 'URGENT',
    'termine' => 'Terminé',
    'annulee' => 'Annulé'
];

// En fonction du format demandé, générer le fichier correspondant
if ($format === 'excel') {
    exportCSV($commandes, $filename, $periodLabel, $includePrices, $statuts);
} else {
    exportHTML($commandes, $filename, $periodLabel, $groupBySupplier, $includePrices, $statuts);
}

/**
 * Fonction pour exporter les commandes en format CSV (compatible Excel)
 */
function exportCSV($commandes, $filename, $periodLabel, $includePrices, $statuts) {
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Créer un gestionnaire de fichier pour écrire dans php://output
    $output = fopen('php://output', 'w');
    
    // Ajouter le BOM UTF-8 pour Excel
    fputs($output, "\xEF\xBB\xBF");
    
    // Définir les en-têtes de colonnes
    $headers = ['Code Barre', 'Client', 'Fournisseur', 'Pièce', 'Quantité'];
    if ($includePrices) {
        $headers[] = 'Prix Estimé (€)';
    }
    $headers[] = 'Statut';
    $headers[] = 'Créé le';
    $headers[] = 'Commandé le';
    
    // Écrire les en-têtes
    fputcsv($output, $headers, ';');
    
    // Écrire les données
    foreach ($commandes as $commande) {
        $row = [];
        $row[] = $commande['code_barre'] ?: '-';
        $row[] = $commande['client_nom'] . ' ' . $commande['client_prenom'];
        $row[] = $commande['fournisseur_nom'] ?: '-';
        $row[] = $commande['nom_piece'];
        $row[] = $commande['quantite'];
        
        if ($includePrices) {
            $row[] = number_format($commande['prix_estime'], 2, ',', '') . ' €';
        }
        
        // Statut
        $row[] = isset($statuts[$commande['statut']]) ? $statuts[$commande['statut']] : $commande['statut'];
        
        // Date de création
        $row[] = date('d/m/Y H:i', strtotime($commande['date_creation']));
        
        // Date de commande
        $dateCommande = $commande['date_commande'] && $commande['date_commande'] !== '-' ? 
            date('d/m/Y H:i', strtotime($commande['date_commande'])) : 
            '-';
        $row[] = $dateCommande;
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}

/**
 * Fonction pour exporter les commandes en format HTML (visualisable dans le navigateur)
 */
function exportHTML($commandes, $filename, $periodLabel, $groupBySupplier, $includePrices, $statuts) {
    // Commencer par le HTML de base
    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Commandes - ' . $periodLabel . '</title>
    <style>
        @page {
            size: landscape;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 15px;
            line-height: 1.3;
            color: #333;
            font-size: 11px;
        }
        h1 {
            text-align: center;
            color: #0D6EFD;
            margin-bottom: 5px;
            font-size: 20px;
        }
        h2 {
            text-align: center;
            font-weight: normal;
            margin-top: 0;
            margin-bottom: 15px;
            color: #555;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        th {
            background-color: #0D6EFD;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 6px;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 0;
        }
        /* Largeurs des colonnes */
        th:nth-child(1), td:nth-child(1) { width: 8%; } /* Code Barre */
        th:nth-child(2), td:nth-child(2) { width: 12%; } /* Client */
        th:nth-child(3), td:nth-child(3) { width: 12%; } /* Fournisseur */
        th:nth-child(4), td:nth-child(4) { width: 18%; } /* Pièce */
        th:nth-child(5), td:nth-child(5) { width: 5%; } /* Quantité */
        th:nth-child(6), td:nth-child(6) { width: 8%; } /* Prix */
        th:nth-child(7), td:nth-child(7) { width: 10%; } /* Status */
        th:nth-child(8), td:nth-child(8) { width: 12%; } /* Date création */
        th:nth-child(9), td:nth-child(9) { width: 12%; } /* Date commande */

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .supplier-header {
            background-color: #e9ecef;
            font-weight: bold;
            padding: 6px;
            font-size: 12px;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        .status-attente { color: #FFC107; font-weight: bold; }
        .status-commande { color: #0D6EFD; font-weight: bold; }
        .status-recu { color: #198754; font-weight: bold; }
        .status-urgent { color: #DC3545; font-weight: bold; }
        .status-termine { color: #0DCAF0; font-weight: bold; }
        .status-annule { color: #6C757D; font-weight: bold; }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #777;
            margin-top: 15px;
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
        }
        .actions {
            text-align: center;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 3px;
            background-color: #0D6EFD;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-print {
            background-color: #6C757D;
        }
        td[title] {
            cursor: help;
        }
        /* Style pour les dates */
        .date-cell {
            font-size: 10px;
            text-align: right;
        }
        @media print {
            .actions {
                display: none;
            }
            body {
                margin: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn btn-print" onclick="window.print();">Imprimer</button>
        <button class="btn" onclick="window.close();">Fermer</button>
    </div>

    <h1>LISTE DES COMMANDES</h1>
    <h2>' . $periodLabel . '</h2>
    
    <table>
        <thead>
            <tr>
                <th title="Code Barre">Code Barre</th>
                <th title="Client">Client</th>
                <th title="Fournisseur">Fournisseur</th>
                <th title="Pièce">Pièce</th>
                <th title="Quantité">Qté</th>';
    
    if ($includePrices) {
        $html .= '<th title="Prix Estimé">Prix</th>';
    }
    
    $html .= '
                <th title="Statut">Statut</th>
                <th title="Date de création">Créé le</th>
                <th title="Date de commande">Commandé le</th>
            </tr>
        </thead>
        <tbody>';
    
    // Classes de statut pour le styling
    $statusClasses = [
        'en_attente' => 'status-attente',
        'commande' => 'status-commande',
        'recue' => 'status-recu',
        'urgent' => 'status-urgent',
        'termine' => 'status-termine',
        'annulee' => 'status-annule'
    ];
    
    // Grouper les commandes par fournisseur si nécessaire
    if ($groupBySupplier) {
        $groupedCommandes = [];
        foreach ($commandes as $commande) {
            $fournisseur = $commande['fournisseur_nom'] ?: 'Non défini';
            if (!isset($groupedCommandes[$fournisseur])) {
                $groupedCommandes[$fournisseur] = [];
            }
            $groupedCommandes[$fournisseur][] = $commande;
        }
        
        // Générer le HTML pour chaque groupe
        foreach ($groupedCommandes as $fournisseur => $commandesGroup) {
            $colspan = $includePrices ? 9 : 8;
            $html .= '<tr><td colspan="' . $colspan . '" class="supplier-header">Fournisseur: ' . htmlspecialchars($fournisseur) . '</td></tr>';
            
            foreach ($commandesGroup as $commande) {
                $html .= generateCommandeHTML($commande, $statusClasses, $statuts, $includePrices);
            }
        }
    } else {
        // Sans groupement
        foreach ($commandes as $commande) {
            $html .= generateCommandeHTML($commande, $statusClasses, $statuts, $includePrices);
        }
    }
    
    $html .= '
        </tbody>
    </table>
    
    <div class="footer">
        Document généré le ' . date('d/m/Y à H:i') . '
    </div>

    <script>
        // Auto-impression si l\'utilisateur le souhaite
        document.addEventListener("DOMContentLoaded", function() {
            if (confirm("Voulez-vous imprimer ce document maintenant ?")) {
                window.print();
            }
        });
    </script>
</body>
</html>';
    
    // Envoyer le HTML au navigateur
    echo $html;
    exit;
}

/**
 * Fonction utilitaire pour générer une ligne de commande en HTML
 */
function generateCommandeHTML($commande, $statusClasses, $statuts, $includePrices) {
    $html = '<tr>';
    $html .= '<td>' . ($commande['code_barre'] ?: '-') . '</td>';
    $html .= '<td>' . htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']) . '</td>';
    $html .= '<td>' . htmlspecialchars($commande['fournisseur_nom'] ?: '-') . '</td>';
    $html .= '<td>' . htmlspecialchars($commande['nom_piece']) . '</td>';
    $html .= '<td>' . htmlspecialchars($commande['quantite']) . '</td>';
    
    if ($includePrices) {
        $html .= '<td>' . number_format($commande['prix_estime'], 2, ',', ' ') . ' €</td>';
    }
    
    // Appareil associé à la réparation
    $reparationInfo = $commande['reparation_id'] ? 
        ('#' . $commande['reparation_id'] . ' - ' . $commande['type_appareil'] . ' ' . $commande['marque'] . ' ' . $commande['modele']) : 
        '-';
    $html .= '<td>' . htmlspecialchars($reparationInfo) . '</td>';
    
    // Statut
    $statusClass = isset($statusClasses[$commande['statut']]) ? $statusClasses[$commande['statut']] : '';
    $statusLabel = isset($statuts[$commande['statut']]) ? $statuts[$commande['statut']] : $commande['statut'];
    $html .= '<td class="' . $statusClass . '">' . htmlspecialchars($statusLabel) . '</td>';
    
    // Date de création
    $html .= '<td class="date-cell">' . date('d/m/Y H:i', strtotime($commande['date_creation'])) . '</td>';
    
    // Date de commande
    $dateCommande = $commande['date_commande'] && $commande['date_commande'] !== '-' ? 
        date('d/m/Y H:i', strtotime($commande['date_commande'])) : 
        '-';
    $html .= '<td class="date-cell">' . $dateCommande . '</td>';
    
    $html .= '</tr>';
    
    return $html;
} 