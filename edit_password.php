<?php
// edit_password.php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
requireAuth();

$error = '';
$success = '';

// Vérifier si un ID a été fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Rediriger vers la liste des mots de passe
    header('Location: passwords.php');
    exit;
}

$passwordId = (int)$_GET['id'];

// Récupérer l'entrée de mot de passe
$passwordEntryResult = PasswordEntry::getById($passwordId, $_SESSION['user_id'], $_SESSION['master_key']);

if (!$passwordEntryResult['success']) {
    // L'entrée n'existe pas ou n'appartient pas à l'utilisateur
    header('Location: passwords.php');
    exit;
}

$passwordEntry = $passwordEntryResult['entry'];
$decryptedPassword = $passwordEntryResult['password'];
$decryptedNotes = $passwordEntryResult['notes'];

// Récupérer toutes les catégories
$db = Database::getInstance();
$categories = $db->fetchAll(
    "SELECT name FROM categories WHERE user_id = ? UNION SELECT 'General' AS name ORDER BY name",
    [$_SESSION['user_id']]
);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $title = $_POST['title'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $websiteUrl = $_POST['website_url'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $category = $_POST['category'] ?? 'General';
    $favorite = isset($_POST['favorite']) ? true : false;
    
    // Validation de base
    if (empty($title)) {
        $error = 'Le titre est obligatoire.';
    } elseif (empty($password)) {
        $error = 'Le mot de passe est obligatoire.';
    } else {
        // Mettre à jour l'entrée de mot de passe
        $result = $passwordEntry->update(
            $passwordId,
            $title,
            $username,
            $email,
            $password,
            $websiteUrl,
            $notes,
            $category,
            $favorite,
            $_SESSION['master_key']
        );
        
        if ($result['success']) {
            $success = 'Mot de passe mis à jour avec succès.';
            
            // Récupérer les informations mises à jour
            $passwordEntryResult = PasswordEntry::getById($passwordId, $_SESSION['user_id'], $_SESSION['master_key']);
            $passwordEntry = $passwordEntryResult['entry'];
            $decryptedPassword = $passwordEntryResult['password'];
            $decryptedNotes = $passwordEntryResult['notes'];
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
    <title>Modifier un mot de passe - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/passwords.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation latérale -->
        <aside class="sidebar">
            <div class="brand">
                <h1><?php echo APP_NAME; ?></h1>
            </div>
            
            <nav>
                <ul>
                    <li><a href="dashboard.php"><span class="icon">🏠</span> Tableau de bord</a></li>
                    <li class="active"><a href="passwords.php"><span class="icon">🔑</span> Mots de passe</a></li>
                    <li><a href="categories.php"><span class="icon">📂</span> Catégories</a></li>
                    <li><a href="generator.php"><span class="icon">🎲</span> Générateur</a></li>
                    <li><a href="settings.php"><span class="icon">⚙️</span> Paramètres</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <div class="user-avatar">
                    <span><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
                </div>
                <div class="user-details">
                    <p class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <a href="logout.php" class="logout-link">Déconnexion</a>
                </div>
            </div>
        </aside>
        
        <!-- Contenu principal -->
        <main class="main-content">
            <header class="dashboard-header">
                <h2>Modifier un mot de passe</h2>
                <div class="actions">
                    <a href="passwords.php" class="btn btn-secondary">
                        <span class="icon">←</span> Retour
                    </a>
                </div>
            </header>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <section class="content-section">
                <form action="edit_password.php?id=<?php echo $passwordId; ?>" method="POST" class="password-form">
                    <div class="form-group">
                        <label for="title">Titre *</label>
                        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($passwordEntry->getTitle()); ?>">
                        <small>Nom du site ou du service (ex: Facebook, Gmail, etc.)</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($passwordEntry->getUsername()); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($passwordEntry->getEmail()); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <div class="password-input-group">
                            <input type="password" id="password" name="password" required value="<?php echo htmlspecialchars($decryptedPassword); ?>">
                            <button type="button" class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                                <svg class="eye-icon" viewBox="0 0 24 24" width="24" height="24">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </button>
                            <button type="button" id="generate-password" class="btn btn-secondary">Générer</button>
                        </div>
                        <div class="password-strength-meter">
                            <div class="strength-bar"></div>
                        </div>
                        <div class="password-feedback"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="website_url">URL du site</label>
                        <input type="url" id="website_url" name="website_url" value="<?php echo htmlspecialchars($passwordEntry->getWebsiteUrl()); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Catégorie</label>
                            <select id="category" name="category">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo ($passwordEntry->getCategory() === $cat['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="favorite" <?php echo ($passwordEntry->isFavorite()) ? 'checked' : ''; ?>>
                                Marquer comme favori
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($decryptedNotes ?? ''); ?></textarea>
                        <small>Vous pouvez ajouter des notes supplémentaires ou des détails sur ce compte</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        <a href="passwords.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
    
    <!-- Fenêtre modale pour le générateur de mot de passe -->
    <div id="password-generator-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Générateur de mot de passe</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="password-length">Longueur</label>
                    <input type="range" id="password-length" min="8" max="64" value="16">
                    <span id="length-value">16</span>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="include-uppercase" checked>
                        Inclure des majuscules (A-Z)
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="include-lowercase" checked>
                        Inclure des minuscules (a-z)
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="include-numbers" checked>
                        Inclure des chiffres (0-9)
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="include-symbols" checked>
                        Inclure des symboles (!@#$%^&*)
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="exclude-similar" checked>
                        Exclure les caractères similaires (0, O, 1, l, I)
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="generated-password">Mot de passe généré</label>
                    <div class="password-display-group">
                        <input type="text" id="generated-password" readonly>
                        <button type="button" id="regenerate-password" class="btn btn-secondary">
                            <span class="icon">🔄</span>
                        </button>
                        <button type="button" id="copy-password" class="btn btn-secondary">
                            <span class="icon">📋</span>
                        </button>
                    </div>
                </div>
                
                <div class="password-strength-meter">
                    <div id="generator-strength-bar" class="strength-bar"></div>
                </div>
                <div id="generator-feedback" class="password-feedback"></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="use-password" class="btn btn-primary">Utiliser ce mot de passe</button>
                <button type="button" class="btn btn-secondary close-modal">Annuler</button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Afficher/masquer le mot de passe
            const toggleButtons = document.querySelectorAll('.toggle-password');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                });
            });
            
            // Évaluation de la force du mot de passe
            const passwordInput = document.getElementById('password');
            const strengthBar = document.querySelector('.strength-bar');
            const feedbackElement = document.querySelector('.password-feedback');
            
            passwordInput.addEventListener('input', evaluatePasswordStrength);
            
            // Évaluer la force du mot de passe initial
            evaluatePasswordStrength();
            
            function evaluatePasswordStrength() {
                const password = passwordInput.value;
                
                // Calculer le score
                let score = 0;
                
                // Longueur
                if (password.length >= 16) score += 30;
                else if (password.length >= 12) score += 20;
                else if (password.length >= 8) score += 10;
                else if (password.length > 0) score += 5;
                
                // Complexité
                if (/[A-Z]/.test(password)) score += 15;
                if (/[a-z]/.test(password)) score += 10;
                if (/[0-9]/.test(password)) score += 15;
                if (/[^A-Za-z0-9]/.test(password)) score += 20;
                
                // Pénalités pour les répétitions
                const repeats = (password.match(/(.)\1+/g) || []).length;
                score -= repeats * 2;
                
                // Limiter le score entre 0 et 100
                score = Math.max(0, Math.min(100, score));
                
                // Mettre à jour la barre de force
                strengthBar.style.width = score + '%';
                
                // Définir la classe de couleur en fonction du score
                strengthBar.className = 'strength-bar';
                let feedback = '';
                
                if (score >= 80) {
                    strengthBar.classList.add('strength-high');
                    feedback = 'Excellent mot de passe !';
                } else if (score >= 60) {
                    strengthBar.classList.add('strength-good');
                    feedback = 'Bon mot de passe';
                } else if (score >= 40) {
                    strengthBar.classList.add('strength-medium');
                    feedback = 'Mot de passe moyen';
                } else if (score >= 20) {
                    strengthBar.classList.add('strength-weak');
                    feedback = 'Mot de passe faible';
                } else {
                    strengthBar.classList.add('strength-very-weak');
                    feedback = 'Mot de passe très faible';
                }
                
                feedbackElement.textContent = feedback;
                
                return { score, feedback };
            }
            
            // Générateur de mot de passe
            const generateBtn = document.getElementById('generate-password');
            const modal = document.getElementById('password-generator-modal');
            const closeModalBtns = document.querySelectorAll('.close-modal');
            const passwordLengthSlider = document.getElementById('password-length');
            const lengthValue = document.getElementById('length-value');
            const includeUppercase = document.getElementById('include-uppercase');
            const includeLowercase = document.getElementById('include-lowercase');
            const includeNumbers = document.getElementById('include-numbers');
            const includeSymbols = document.getElementById('include-symbols');
            const excludeSimilar = document.getElementById('exclude-similar');
            const generatedPasswordInput = document.getElementById('generated-password');
            const regenerateBtn = document.getElementById('regenerate-password');
            const copyBtn = document.getElementById('copy-password');
            const usePasswordBtn = document.getElementById('use-password');
            const generatorStrengthBar = document.getElementById('generator-strength-bar');
            const generatorFeedback = document.getElementById('generator-feedback');
            
            // Ouvrir la fenêtre modale
            generateBtn.addEventListener('click', function() {
                modal.classList.add('show');
                generatePassword();
            });
            
            // Fermer la fenêtre modale
            closeModalBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    modal.classList.remove('show');
                });
            });
            
            // Cliquer en dehors de la fenêtre modale pour la fermer
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.remove('show');
                }
            });
            
            // Slider de longueur
            passwordLengthSlider.addEventListener('input', function() {
                lengthValue.textContent = this.value;
                generatePassword();
            });
            
            // Options de génération
            [includeUppercase, includeLowercase, includeNumbers, includeSymbols, excludeSimilar].forEach(checkbox => {
                checkbox.addEventListener('change', generatePassword);
            });
            
            // Régénérer le mot de passe
            regenerateBtn.addEventListener('click', generatePassword);
            
            // Copier le mot de passe
            copyBtn.addEventListener('click', function() {
                generatedPasswordInput.select();
                document.execCommand('copy');
                
                // Afficher un message de confirmation
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="icon">✓</span>';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
            
            // Utiliser le mot de passe généré
            usePasswordBtn.addEventListener('click', function() {
                passwordInput.value = generatedPasswordInput.value;
                modal.classList.remove('show');
                evaluatePasswordStrength();
            });
            
            // Fonction pour générer un mot de passe
            function generatePassword() {
                const length = parseInt(passwordLengthSlider.value);
                const useUppercase = includeUppercase.checked;
                const useLowercase = includeLowercase.checked;
                const useNumbers = includeNumbers.checked;
                const useSymbols = includeSymbols.checked;
                const avoidSimilar = excludeSimilar.checked;
                
                // S'assurer qu'au moins un ensemble de caractères est sélectionné
                if (!useUppercase && !useLowercase && !useNumbers && !useSymbols) {
                    includeLowercase.checked = true;
                }
                
                // Construire les ensembles de caractères
                let charset = '';
                let lowercaseChars = 'abcdefghijklmnopqrstuvwxyz';
                let uppercaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                let numberChars = '0123456789';
                let symbolChars = '!@#$%^&*()-_=+[]{};:,.<>?/|';
                
                // Exclure les caractères similaires si demandé
                if (avoidSimilar) {
                    lowercaseChars = lowercaseChars.replace(/[lo]/g, '');
                    uppercaseChars = uppercaseChars.replace(/[IO]/g, '');
                    numberChars = numberChars.replace(/[01]/g, '');
                }
                
                if (useLowercase) charset += lowercaseChars;
                if (useUppercase) charset += uppercaseChars;
                if (useNumbers) charset += numberChars;
                if (useSymbols) charset += symbolChars;
                
                // Générer le mot de passe
                let password = '';
                const charsetLength = charset.length;
                
                for (let i = 0; i < length; i++) {
                    const randomIndex = Math.floor(Math.random() * charsetLength);
                    password += charset[randomIndex];
                }
                
                // S'assurer que le mot de passe contient au moins un caractère de chaque ensemble demandé
                let containsLowercase = !useLowercase || /[a-z]/.test(password);
                let containsUppercase = !useUppercase || /[A-Z]/.test(password);
                let containsNumber = !useNumbers || /[0-9]/.test(password);
                let containsSymbol = !useSymbols || /[^a-zA-Z0-9]/.test(password);
                
                // Si le mot de passe ne répond pas aux critères, en générer un nouveau
                if (!containsLowercase || !containsUppercase || !containsNumber || !containsSymbol) {
                    return generatePassword();
                }
                
                // Afficher le mot de passe généré
                generatedPasswordInput.value = password;
                
                // Évaluer la force du mot de passe généré
                let score = 0;
                if (length >= 16) score += 30;
                else if (length >= 12) score += 20;
                else if (length >= 8) score += 10;
                
                if (useUppercase) score += 15;
                if (useLowercase) score += 10;
                if (useNumbers) score += 15;
                if (useSymbols) score += 20;
                
                const repeats = (password.match(/(.)\1+/g) || []).length;
                score -= repeats * 2;
                
                score = Math.max(0, Math.min(100, score));
                
                generatorStrengthBar.style.width = score + '%';
                generatorStrengthBar.className = 'strength-bar';
                
                let feedback = '';
                if (score >= 80) {
                    generatorStrengthBar.classList.add('strength-high');
                    feedback = 'Excellent mot de passe !';
                } else if (score >= 60) {
                    generatorStrengthBar.classList.add('strength-good');
                    feedback = 'Bon mot de passe';
                } else if (score >= 40) {
                    generatorStrengthBar.classList.add('strength-medium');
                    feedback = 'Mot de passe moyen';
                } else if (score >= 20) {
                    generatorStrengthBar.classList.add('strength-weak');
                    feedback = 'Mot de passe faible';
                } else {
                    generatorStrengthBar.classList.add('strength-very-weak');
                    feedback = 'Mot de passe très faible';
                }
                
                generatorFeedback.textContent = feedback;
                
                return password;
            }
            
            // Session timeout
            let sessionTimeout;
            
            function resetSessionTimer() {
                clearTimeout(sessionTimeout);
                sessionTimeout = setTimeout(function() {
                    alert('Votre session va expirer pour des raisons de sécurité. Veuillez vous reconnecter.');
                    window.location.href = 'logout.php';
                }, <?php echo SESSION_LIFETIME * 1000; ?>);
            }
            
            // Réinitialiser le minuteur sur les événements utilisateur
            ['mousemove', 'mousedown', 'keypress', 'touchmove', 'scroll'].forEach(function(evt) {
                document.addEventListener(evt, resetSessionTimer);
            });
            
            // Initialiser le minuteur
            resetSessionTimer();
        });
    </script>
</body>
</html>