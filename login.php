<?php
// login.php
require_once 'init.php';

// Rediriger vers le tableau de bord si déjà connecté
redirectIfAuthenticated();

$error = '';
$successMessage = '';

// Vérifier s'il y a un message de timeout
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $successMessage = 'Votre session a expiré pour des raisons de sécurité. Veuillez vous reconnecter.';
}

// Vérifier s'il y a un message de déconnexion
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $successMessage = 'Vous avez été déconnecté avec succès.';
}

// Vérifier s'il y a un message de création de compte
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $successMessage = 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.';
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les valeurs du formulaire
    $usernameOrEmail = $_POST['username_email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Tenter l'authentification
        $user = new User();
        $result = $user->authenticate($usernameOrEmail, $password);
        
        if ($result['success']) {
            // Stocker les informations de session
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['master_key'] = $result['master_key'];
            $_SESSION['last_activity'] = time();
            
            // Rediriger vers le tableau de bord
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Connexion à <?php echo APP_NAME; ?></h1>
            <p>Entrez vos identifiants pour accéder à vos mots de passe</p>
        </header>
        
        <main>
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username_email">Nom d'utilisateur ou Email</label>
                    <input type="text" id="username_email" name="username_email" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe maître</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                            <svg class="eye-icon" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
                </div>
            </form>
            
            <div class="auth-links">
                <p>Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
                <p><a href="reset_password.php">Mot de passe oublié ?</a></p>
            </div>
        </main>
        
        <footer>
            <p class="security-note">Note : Votre mot de passe maître n'est jamais envoyé ni stocké sur nos serveurs.</p>
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Tous droits réservés</p>
        </footer>
    </div>
    
    <script>
        // Script pour afficher/masquer le mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-password');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
                    // Modifier l'icône ou le texte du bouton si nécessaire
                    this.setAttribute('aria-label', type === 'password' ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
                });
            });
        });
    </script>
</body>
</html>