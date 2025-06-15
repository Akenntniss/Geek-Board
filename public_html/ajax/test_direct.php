<?php
// Test direct de la requête POST
header('Content-Type: application/json');

// Données de test identiques à celles envoyées par le JavaScript
$testData = [
    'client_id' => '514',
    'fournisseur_id' => '4',
    'statut' => 'en_attente',
    'pieces' => [
        [
            'nom_piece' => 'Test Pièce',
            'code_barre' => '123456789',
            'prix_estime' => '25.50',
            'quantite' => '1',
            'reparation_id' => ''
        ]
    ]
];

// Simuler la requête POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Créer un fichier temporaire avec les données JSON
$jsonData = json_encode($testData);
$tempFile = tempnam(sys_get_temp_dir(), 'test_input');
file_put_contents($tempFile, $jsonData);

// Rediriger php://input vers notre fichier temporaire
stream_wrapper_unregister('php');
stream_wrapper_register('php', 'TestInputWrapper');

class TestInputWrapper {
    public $context;
    private $position = 0;
    private static $data = '';
    
    public static function setData($data) {
        self::$data = $data;
    }
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        if ($path === 'php://input') {
            $this->position = 0;
            return true;
        }
        return false;
    }
    
    public function stream_read($count) {
        $ret = substr(self::$data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    public function stream_eof() {
        return $this->position >= strlen(self::$data);
    }
    
    public function stream_tell() {
        return $this->position;
    }
    
    public function stream_seek($offset, $whence) {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = strlen(self::$data) + $offset;
                break;
        }
        return true;
    }
    
    public function stream_stat() {
        return array();
    }
}

// Définir les données pour notre wrapper
TestInputWrapper::setData($jsonData);

echo "Test direct de add_multiple_commandes.php\n";
echo "Données envoyées: " . $jsonData . "\n\n";
echo "Réponse:\n";

// Capturer la sortie
ob_start();

try {
    // Inclure le script
    include 'add_multiple_commandes.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'inclusion: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur fatale: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

$output = ob_get_clean();
echo $output;

// Nettoyer
unlink($tempFile);
stream_wrapper_restore('php');
?> 