<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../config/database.php';

$stmt = $pdo->query("SELECT * FROM rachats");
$rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Your App');
$pdf->SetTitle('Liste des rachats');
$pdf->AddPage();

$html = '<h1>Liste des rachats</h1>
<table border="1">
<tr><th>Marque</th><th>Modèle</th><th>Prix</th><th>Date</th></tr>';

foreach ($rachats as $rachat) {
    $html .= sprintf(
        '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
        htmlspecialchars($rachat['marque']),
        htmlspecialchars($rachat['modele']),
        htmlspecialchars($rachat['prix']),
        htmlspecialchars($rachat['date_achat'])
    );
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('rachats-'.date('Ymd-His').'.pdf', 'D');