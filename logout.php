<?php
// logout.php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
if (isAuthenticated()) {
    // Enregistrer l'activité de déconnexion
    $user = User::getById($_SESSION['user_id']);
    if ($user) {
        $user->logActivity('logout', 'Déconnexion');
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

// Rediriger vers la page de connexion
header('Location: login.php?logout=1');
exit;