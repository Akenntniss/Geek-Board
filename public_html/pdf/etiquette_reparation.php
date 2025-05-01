<?php
// Fichier pour générer une étiquette de réparation au format 4x6 pouces

// Empêcher l'inclusion du header et footer standards
define('NO_HEADER_FOOTER', true);

// Vérifier qu'un ID de réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Erreur: ID de réparation non spécifié");
}

$reparation_id = intval($_GET['id']);

// Vider tout tampon de sortie existant pour éviter les problèmes d'en-têtes
if (ob_get_length()) ob_clean();

// Inclure la bibliothèque FPDF
require_once(BASE_PATH . '/fpdf/fpdf.php');

// Pas besoin d'inclure database.php car il est déjà inclus dans index.php
// require_once(BASE_PATH . '/config/database.php');

try {
    // Récupérer les informations de la réparation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom, c.prenom
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reparation) {
        die("Erreur: Réparation non trouvée");
    }

    // Créer une classe dérivée pour l'étiquette 4x6 pouces
    class EtiquetteReparation extends FPDF {
        // Dimensions en mm pour une étiquette 4x6 pouces
        // 4 inches = 101.6 mm, 6 inches = 152.4 mm
        
        function __construct() {
            // Orientation paysage (L) pour le format 4x6
            parent::__construct('L', 'mm', array(101.6, 152.4));
        }
        
        function Header() {
            // En-tête de l'étiquette
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'ÉTIQUETTE DE RÉPARATION', 0, 1, 'C');
            $this->Line(10, 17, 142, 17);
            $this->Ln(5);
        }
        
        function Footer() {
            // Pied de page avec date d'impression
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Étiquette générée le ' . date('d/m/Y H:i'), 0, 0, 'C');
        }
    }

    // Créer l'étiquette
    $pdf = new EtiquetteReparation();
    $pdf->AddPage();
    
    // Informations sur l'appareil
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'APPAREIL: ' . strtoupper($reparation['type_appareil']), 0, 1);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Modèle: ' . $reparation['modele'], 0, 1);
    
    // Date d'entrée
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, 'Date d\'entrée: ' . date('d/m/Y', strtotime($reparation['date_reception'])), 0, 1);
    
    // ID de la réparation
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'ID: ' . $reparation_id, 0, 1, 'C');
    
    // Client
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'Client: ' . $reparation['nom'] . ' ' . $reparation['prenom'], 0, 1);
    
    // Ligne de séparation
    $pdf->Line(10, $pdf->GetY(), 142, $pdf->GetY());
    $pdf->Ln(5);
    
    // Section des codes QR (simplifiée)
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 10, 'Scanner pour mettre à jour le statut:', 0, 0);
    $pdf->Cell(70, 10, 'Scanner pour consulter le dossier:', 0, 1);
    
    // URLs pour les QR codes
    $updateUrl = "/index.php?page=scanner&action=update&id=" . $reparation_id;
    $viewUrl = "/index.php?page=reparations&view=" . $reparation_id;
    
    // Ajouter des cellules simples à la place des QR codes
    $pdf->SetFont('Arial', 'B', 12);
    
    // Premier "QR code"
    $pdf->Cell(30, 30, '', 1, 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 30, "Scanner pour\nmettre à jour\nID: " . $reparation_id, 0, 0, 'L');
    
    // Deuxième "QR code"
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(10, 30, '', 0, 0);  // Espace
    $pdf->Cell(30, 30, '', 1, 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 30, "Scanner pour\nconsulter\nID: " . $reparation_id, 0, 1, 'L');
    
    // Sortie du PDF
    $pdf->Output('Etiquette_Reparation_' . $reparation_id . '.pdf', 'I');
    
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
} 