<?php
// register.php
require_once 'init.php';

// Rediriger vers le tableau de bord si déjà connecté
redirectIfAuthenticated();

$error = '';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les valeurs du formulaire
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation basique
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 12) {
        $error = 'Le mot de passe doit contenir au moins 12 caractères.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Le mot de passe doit contenir au moins une lettre majuscule.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Le mot de passe doit contenir au moins une lettre minuscule.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Le mot de passe doit contenir au moins un chiffre.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = 'Le mot de passe doit contenir au moins un caractère spécial.';
    } else {
        // Créer le compte utilisateur
        $user = new User();
        $result = $user->create($username, $email, $password);
        
        if ($result['success']) {
            // Rediriger vers la page de connexion avec un message de succès
            header('Location: login.php?registered=1');
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
    <title>Créer un compte - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Créer un compte <?php echo APP_NAME; ?></h1>
            <p>Créez un compte pour commencer à gérer vos mots de passe en toute sécurité</p>
        </header>
        
        <main>
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required autofocus
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe maître</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" required minlength="12">
                        <button type="button" class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                            <svg class="eye-icon" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="password-strength-meter">
                        <div class="strength-bar"></div>
                    </div>
                    <div class="password-feedback"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="12">
                        <button type="button" class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                            <svg class="eye-icon" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="password-requirements">
                    <p>Le mot de passe doit :</p>
                    <ul>
                        <li class="requirement" id="req-length">Contenir au moins 12 caractères</li>
                        <li class="requirement" id="req-uppercase">Contenir au moins une lettre majuscule</li>
                        <li class="requirement" id="req-lowercase">Contenir au moins une lettre minuscule</li>
                        <li class="requirement" id="req-number">Contenir au moins un chiffre</li>
                        <li class="requirement" id="req-special">Contenir au moins un caractère spécial</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <div class="security-notice">
                        <p><strong>Important :</strong> Votre mot de passe maître est la clé de votre coffre-fort numérique. 
                        Nous ne stockons pas ce mot de passe et ne pouvons pas le récupérer pour vous si vous l'oubliez. 
                        Assurez-vous de choisir un mot de passe fort que vous pouvez mémoriser.</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Créer un compte</button>
                </div>
            </form>
            
            <div class="auth-links">
                <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Tous droits réservés</p>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Script pour afficher/masquer les mots de passe
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
            
            // Vérification de la force du mot de passe en temps réel
            const passwordInput = document.getElementById('password');
            const strengthBar = document.querySelector('.strength-bar');
            const feedbackElement = document.querySelector('.password-feedback');
            
            // Éléments pour les exigences du mot de passe
            const requirementLength = document.getElementById('req-length');
            const requirementUppercase = document.getElementById('req-uppercase');
            const requirementLowercase = document.getElementById('req-lowercase');
            const requirementNumber = document.getElementById('req-number');
            const requirementSpecial = document.getElementById('req-special');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                // Vérifier les exigences
                const hasLength = password.length >= 12;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[^A-Za-z0-9]/.test(password);
                
                // Mettre à jour les indicateurs d'exigence
                toggleRequirement(requirementLength, hasLength);
                toggleRequirement(requirementUppercase, hasUppercase);
                toggleRequirement(requirementLowercase, hasLowercase);
                toggleRequirement(requirementNumber, hasNumber);
                toggleRequirement(requirementSpecial, hasSpecial);
                
                // Calculer le score
                let score = 0;
                if (password.length >= 16) score += 30;
                else if (password.length >= 12) score += 20;
                else if (password.length >= 8) score += 10;
                
                if (hasUppercase) score += 15;
                if (hasLowercase) score += 10;
                if (hasNumber) score += 15;
                if (hasSpecial) score += 20;
                
                // Pénalités pour les répétitions
                const repeats = (password.match(/(.)\1+/g) || []).length;
                score -= repeats * 2;
                
                // Limiter le score entre 0 et 100
                score = Math.max(0, Math.min(100, score));
                
                // Mettre à jour la barre de force
                strengthBar.style.width = score + '%';
                
                // Définir la classe de couleur en fonction du score
                strengthBar.className = 'strength-bar';
                if (score >= 80) {
                    strengthBar.classList.add('strength-high');
                    feedbackElement.textContent = 'Excellent mot de passe !';
                } else if (score >= 60) {
                    strengthBar.classList.add('strength-good');
                    feedbackElement.textContent = 'Bon mot de passe';
                } else if (score >= 40) {
                    strengthBar.classList.add('strength-medium');
                    feedbackElement.textContent = 'Mot de passe moyen';
                } else if (score >= 20) {
                    strengthBar.classList.add('strength-weak');
                    feedbackElement.textContent = 'Mot de passe faible';
                } else {
                    strengthBar.classList.add('strength-very-weak');
                    feedbackElement.textContent = 'Mot de passe très faible';
                }
            });
            
            // Vérification de correspondance des mots de passe
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            confirmPasswordInput.addEventListener('input', function() {
                if (passwordInput.value === this.value) {
                    this.setCustomValidity('');
                } else {
                    this.setCustomValidity('Les mots de passe ne correspondent pas');
                }
            });
            
            // Fonction pour mettre à jour les indicateurs d'exigence
            function toggleRequirement(element, fulfilled) {
                if (fulfilled) {
                    element.classList.add('fulfilled');
                    element.classList.remove('unfulfilled');
                } else {
                    element.classList.add('unfulfilled');
                    element.classList.remove('fulfilled');
                }
            }
        });
    </script>
</body>
</html>