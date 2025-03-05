<?php
// includes/auth.php

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @return bool True si l'utilisateur est connecté, false sinon
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est authentifié et redirige si ce n'est pas le cas
 * 
 * @param string $redirect URL de redirection si non authentifié (par défaut: login.php)
 * @return void
 */
function requireLogin($redirect = 'login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Vérifie si une session est expirée
 * 
 * @return bool True si la session est expirée, false sinon
 */
function isSessionExpired() {
    if (!isset($_SESSION['last_activity'])) {
        return true;
    }
    
    $inactive = time() - $_SESSION['last_activity'];
    return $inactive >= SESSION_LIFETIME;
}

/**
 * Met à jour le temps d'activité de la session
 * 
 * @return void
 */
function updateSessionActivity() {
    $_SESSION['last_activity'] = time();
}

/**
 * Nettoie les données de session et détruit la session
 * 
 * @return void
 */
function logout() {
    // Supprimer la clé maître de la mémoire
    if (isset($_SESSION['master_key'])) {
        $_SESSION['master_key'] = null;
        unset($_SESSION['master_key']);
    }
    
    // Détruire la session
    session_unset();
    session_destroy();
    
    // Supprimer le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
}

/**
 * Régénère l'ID de session pour prévenir la fixation de session
 * 
 * @return void
 */
function regenerateSessionId() {
    // Sauvegarde des données de session
    $sessionData = $_SESSION;
    
    // Régénérer l'ID de session
    session_regenerate_id(true);
    
    // Restaurer les données de session
    $_SESSION = $sessionData;
}