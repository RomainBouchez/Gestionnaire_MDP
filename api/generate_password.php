<?php
// api/generate_password.php
header('Content-Type: application/json');

// Initialiser l'application
require_once '../init.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentification requise'
    ]);
    exit;
}

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Récupérer les paramètres
$data = json_decode(file_get_contents('php://input'), true);

$length = isset($data['length']) ? (int)$data['length'] : DEFAULT_PASSWORD_LENGTH;
$includeUppercase = isset($data['include_uppercase']) ? (bool)$data['include_uppercase'] : DEFAULT_INCLUDE_UPPERCASE;
$includeLowercase = isset($data['include_lowercase']) ? (bool)$data['include_lowercase'] : true;
$includeNumbers = isset($data['include_numbers']) ? (bool)$data['include_numbers'] : DEFAULT_INCLUDE_NUMBERS;
$includeSymbols = isset($data['include_symbols']) ? (bool)$data['include_symbols'] : DEFAULT_INCLUDE_SYMBOLS;
$excludeSimilar = isset($data['exclude_similar']) ? (bool)$data['exclude_similar'] : DEFAULT_EXCLUDE_SIMILAR;

// Valider les paramètres
if ($length < 4 || $length > 100) {
    echo json_encode([
        'success' => false,
        'message' => 'La longueur doit être comprise entre 4 et 100 caractères'
    ]);
    exit;
}

// S'assurer qu'au moins un ensemble de caractères est sélectionné
if (!$includeUppercase && !$includeLowercase && !$includeNumbers && !$includeSymbols) {
    echo json_encode([
        'success' => false,
        'message' => 'Au moins un ensemble de caractères doit être sélectionné'
    ]);
    exit;
}

// Générer le mot de passe
try {
    $password = generatePassword(
        $length,
        $includeUppercase,
        $includeLowercase,
        $includeNumbers,
        $includeSymbols,
        $excludeSimilar
    );
    
    // Évaluer la robustesse du mot de passe
    $strength = evaluatePasswordStrength($password);
    
    echo json_encode([
        'success' => true,
        'password' => $password,
        'strength' => $strength
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}