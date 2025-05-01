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

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// En-têtes
$sheet->setCellValue('A1', 'Marque');
$sheet->setCellValue('B1', 'Modèle');
$sheet->setCellValue('C1', 'Prix');
$sheet->setCellValue('D1', 'Date achat');

// Données
$row = 2;
foreach ($rachats as $rachat) {
    $sheet->setCellValue('A'.$row, htmlspecialchars($rachat['marque']));
    $sheet->setCellValue('B'.$row, htmlspecialchars($rachat['modele']));
    $sheet->setCellValue('C'.$row, htmlspecialchars($rachat['prix']));
    $sheet->setCellValue('D'.$row, htmlspecialchars($rachat['date_achat']));
    $row++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="rachats-'.date('Ymd-His').'.xlsx"');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');