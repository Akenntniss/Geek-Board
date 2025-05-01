<?php
// Fichier de test minimal
header('Content-Type: application/json');

// Création d'un log simplifiée
$log_file = __DIR__ . '/../logs/test_' . date('Y-m-d') . '.log';
file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Test d'accès réussi\n", FILE_APPEND);

// Réponse JSON simple
echo json_encode([
    'success' => true,
    'message' => 'Test réussi - Accès OK',
    'timestamp' => time()
]); 