<?php
// init.php

// Charger la configuration en premier
require_once __DIR__ . '/config/config.php';

// Démarrer la session avec des paramètres sécurisés
session_start([
    'cookie_secure' => COOKIE_SECURE,
    'cookie_httponly' => COOKIE_HTTPONLY,
    'cookie_samesite' => COOKIE_SAMESITE,
    'gc_maxlifetime' => SESSION_LIFETIME,
    'use_strict_mode' => true
]);

// Charger les fonctions utilitaires
require_once INCLUDE_PATH . 'functions.php';
require_once INCLUDE_PATH . 'auth.php';
require_once INCLUDE_PATH . 'crypto.php';
require_once INCLUDE_PATH . 'password_generator.php';

// Charger les classes
require_once CLASS_PATH . 'Database.php';
require_once CLASS_PATH . 'User.php';
require_once CLASS_PATH . 'PasswordEntry.php';

// Définir les en-têtes de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; style-src \'self\'; img-src \'self\' data:;');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Fonction pour vérifier l'état de la session et rafraîchir le délai d'expiration
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        // La session a expiré
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?timeout=1');
        exit;
    }
    
    // Mettre à jour le temps d'activité
    $_SESSION['last_activity'] = time();
}

// Vérifier si l'utilisateur est authentifié
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['master_key']);
}

// Rediriger vers la page de connexion si non authentifié
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    
    checkSessionTimeout();
}

// Rediriger vers le tableau de bord si déjà authentifié
function redirectIfAuthenticated() {
    if (isAuthenticated()) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

// Fonction pour journaliser les erreurs
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] ERROR: {$message}{$contextStr}" . PHP_EOL;
    
    // Créer le répertoire de logs s'il n'existe pas
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

// Gestionnaire d'exceptions
set_exception_handler(function ($exception) {
    logError($exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    // En mode production, ne pas afficher les détails de l'erreur
    if (defined('APP_ENV') && APP_ENV === 'production') {
        echo 'Une erreur est survenue. Veuillez réessayer plus tard.';
    } else {
        echo 'Erreur: ' . $exception->getMessage();
    }
    
    exit;
});

// Fonction pour sortir en toute sécurité (purge les données sensibles)
function secureExit() {
    // Supprimer la clé maître de la session
    if (isset($_SESSION['master_key'])) {
        $_SESSION['master_key'] = null;
        unset($_SESSION['master_key']);
    }
    
    // Détruire la session
    session_unset();
    session_destroy();
    
    // Rediriger vers la page d'accueil
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}