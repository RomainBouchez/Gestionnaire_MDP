<?php
// generator.php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
requireAuth();

// Récupérer les paramètres par défaut pour le générateur
$db = Database::getInstance();
$userSettings = $db->fetch("SELECT * FROM user_settings WHERE user_id = ?", [$_SESSION['user_id']]);

// Si aucun paramètre n'existe, utiliser les valeurs par défaut
if (!$userSettings) {
    $defaultLength = DEFAULT_PASSWORD_LENGTH;
    $includeUppercase = DEFAULT_INCLUDE_UPPERCASE ? 1 : 0;
    $includeLowercase = DEFAULT_INCLUDE_LOWERCASE ? 1 : 0;
    $includeNumbers = DEFAULT_INCLUDE_NUMBERS ? 1 : 0;
    $includeSymbols = DEFAULT_INCLUDE_SYMBOLS ? 1 : 0;
    $excludeSimilar = DEFAULT_EXCLUDE_SIMILAR ? 1 : 0;
} else {
    $defaultLength = $userSettings['default_password_length'] ?? DEFAULT_PASSWORD_LENGTH;
    $includeUppercase = $userSettings['include_uppercase'] ?? DEFAULT_INCLUDE_UPPERCASE;
    $includeLowercase = $userSettings['include_lowercase'] ?? DEFAULT_INCLUDE_LOWERCASE;
    $includeNumbers = $userSettings['include_numbers'] ?? DEFAULT_INCLUDE_NUMBERS;
    $includeSymbols = $userSettings['include_symbols'] ?? DEFAULT_INCLUDE_SYMBOLS;
    $excludeSimilar = $userSettings['exclude_similar'] ?? DEFAULT_EXCLUDE_SIMILAR;
}

// Générer un mot de passe par défaut pour l'affichage initial
$generatedPassword = generatePassword(
    $defaultLength,
    $includeUppercase,
    $includeLowercase,
    $includeNumbers,
    $includeSymbols,
    $excludeSimilar
);

// Évaluer la force du mot de passe généré
$strength = evaluatePasswordStrength($generatedPassword);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur de mot de passe - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
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
                    <li><a href="passwords.php"><span class="icon">🔑</span> Mots de passe</a></li>
                    <li><a href="categories.php"><span class="icon">📂</span> Catégories</a></li>
                    <li class="active"><a href="generator.php"><span class="icon">🎲</span> Générateur</a></li>
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
                <h2>Générateur de mot de passe</h2>
                <div class="actions">
                    <a href="add_password.php" class="btn btn-primary">
                        <span class="icon">➕</span> Nouveau mot de passe
                    </a>
                </div>
            </header>
            
            <section class="content-section">
                <div class="generator-container">
                    <div class="password-display">
                        <h3>Mot de passe généré</h3>
                        <div class="password-display-group">
                            <input type="text" id="generated-password" value="<?php echo htmlspecialchars($generatedPassword); ?>" readonly>
                            <button type="button" id="copy-password" class="btn btn-secondary" title="Copier">
                                <span class="icon">📋</span>
                            </button>
                        </div>
                        
                        <div class="password-strength-meter">
                            <div id="strength-bar" class="strength-bar strength-<?php echo strtolower($strength['strength']); ?>" style="width: <?php echo $strength['score']; ?>%;"></div>
                        </div>
                        <div class="password-feedback">
                            Force: <span id="strength-text"><?php echo $strength['strength']; ?></span>
                            <?php if (!empty($strength['feedback'])): ?>
                                <ul class="feedback-list">
                                    <?php foreach ($strength['feedback'] as $feedback): ?>
                                        <li><?php echo htmlspecialchars($feedback); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="generator-options">
                        <h3>Options</h3>
                        <form id="generator-form">
                            <div class="form-group">
                                <label for="password-length">Longueur</label>
                                <div class="range-container">
                                    <input type="range" id="password-length" min="8" max="64" step="1" value="<?php echo $defaultLength; ?>">
                                    <span id="length-value"><?php echo $defaultLength; ?></span>
                                </div>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" id="include-uppercase" <?php echo $includeUppercase ? 'checked' : ''; ?>>
                                    Inclure des majuscules (A-Z)
                                </label>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" id="include-lowercase" <?php echo $includeLowercase ? 'checked' : ''; ?>>
                                    Inclure des minuscules (a-z)
                                </label>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" id="include-numbers" <?php echo $includeNumbers ? 'checked' : ''; ?>>
                                    Inclure des chiffres (0-9)
                                </label>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" id="include-symbols" <?php echo $includeSymbols ? 'checked' : ''; ?>>
                                    Inclure des symboles (!@#$%^&*)
                                </label>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" id="exclude-similar" <?php echo $excludeSimilar ? 'checked' : ''; ?>>
                                    Exclure les caractères similaires (0, O, 1, l, I)
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="generate-btn" class="btn btn-primary">Générer</button>
                                <button type="button" id="save-defaults-btn" class="btn btn-secondary">Enregistrer comme paramètres par défaut</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="password-history">
                        <h3>Historique</h3>
                        <div class="history-list" id="history-list">
                            <!-- L'historique sera rempli dynamiquement par JavaScript -->
                            <p class="empty-history">L'historique des mots de passe générés apparaîtra ici.</p>
                        </div>
                        <small class="history-note">Note: L'historique est temporaire et sera effacé lorsque vous quitterez cette page.</small>
                    </div>
                </div>
            </section>
            
            <section class="content-section">
                <div class="password-tips">
                    <h3>Conseils pour des mots de passe sécurisés</h3>
                    <ul>
                        <li><strong>Longueur</strong> - Les mots de passe plus longs sont généralement plus sécurisés. Utilisez au moins 12 caractères.</li>
                        <li><strong>Complexité</strong> - Combinez des lettres majuscules et minuscules, des chiffres et des symboles.</li>
                        <li><strong>Unicité</strong> - Utilisez un mot de passe différent pour chaque service ou site web.</li>
                        <li><strong>Évitez les motifs</strong> - N'utilisez pas de séquences évidentes (123456, qwerty) ou des informations personnelles.</li>
                        <li><strong>Actualisation</strong> - Changez vos mots de passe régulièrement, en particulier pour les comptes sensibles.</li>
                    </ul>
                </div>
            </section>
        </main>
    </div>
    
    <!-- Notification pour le clipboard -->
    <div id="clipboard-notification" class="notification">Copié dans le presse-papiers!</div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Éléments du DOM
            const generatedPasswordInput = document.getElementById('generated-password');
            const copyPasswordBtn = document.getElementById('copy-password');
            const passwordLengthSlider = document.getElementById('password-length');
            const lengthValue = document.getElementById('length-value');
            const includeUppercase = document.getElementById('include-uppercase');
            const includeLowercase = document.getElementById('include-lowercase');
            const includeNumbers = document.getElementById('include-numbers');
            const includeSymbols = document.getElementById('include-symbols');
            const excludeSimilar = document.getElementById('exclude-similar');
            const generateBtn = document.getElementById('generate-btn');
            const saveDefaultsBtn = document.getElementById('save-defaults-btn');
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const historyList = document.getElementById('history-list');
            const clipboardNotification = document.getElementById('clipboard-notification');
            
            // Historique des mots de passe générés
            let passwordHistory = [];
            
            // Mettre à jour l'affichage de la longueur
            passwordLengthSlider.addEventListener('input', function() {
                lengthValue.textContent = this.value;
            });
            
            // Générer un mot de passe
            function generatePassword() {
                // S'assurer qu'au moins un type de caractère est sélectionné
                if (!includeUppercase.checked && !includeLowercase.checked && 
                    !includeNumbers.checked && !includeSymbols.checked) {
                    includeLowercase.checked = true;
                    alert('Au moins un ensemble de caractères doit être sélectionné. Les minuscules ont été activées automatiquement.');
                }
                
                // Préparer les paramètres
                const params = {
                    length: parseInt(passwordLengthSlider.value),
                    include_uppercase: includeUppercase.checked,
                    include_lowercase: includeLowercase.checked,
                    include_numbers: includeNumbers.checked,
                    include_symbols: includeSymbols.checked,
                    exclude_similar: excludeSimilar.checked
                };
                
                // Appeler l'API pour générer le mot de passe
                fetch('api/generate_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(params)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Afficher le mot de passe généré
                        generatedPasswordInput.value = data.password;
                        
                        // Mettre à jour l'indicateur de force
                        strengthBar.style.width = data.strength.score + '%';
                        strengthBar.className = 'strength-bar';
                        const strengthClass = 'strength-' + data.strength.strength.toLowerCase();
                        strengthBar.classList.add(strengthClass);
                        strengthText.textContent = data.strength.strength;
                        
                        // Ajouter à l'historique
                        addToHistory(data.password);
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la génération du mot de passe.');
                });
            }
            
            // Ajouter un mot de passe à l'historique
            function addToHistory(password) {
                // Limiter l'historique à 10 entrées
                if (passwordHistory.length >= 10) {
                    passwordHistory.pop(); // Supprimer le plus ancien
                }
                
                // Ajouter le nouveau mot de passe au début
                passwordHistory.unshift(password);
                
                // Mettre à jour l'affichage de l'historique
                updateHistoryDisplay();
            }
            
            // Mettre à jour l'affichage de l'historique
            function updateHistoryDisplay() {
                if (passwordHistory.length === 0) {
                    historyList.innerHTML = '<p class="empty-history">L\'historique des mots de passe générés apparaîtra ici.</p>';
                    return;
                }
                
                historyList.innerHTML = '';
                
                passwordHistory.forEach((password, index) => {
                    const item = document.createElement('div');
                    item.className = 'history-item';
                    
                    const passwordDisplay = document.createElement('div');
                    passwordDisplay.className = 'history-password';
                    passwordDisplay.textContent = password;
                    
                    const actions = document.createElement('div');
                    actions.className = 'history-actions';
                    
                    const useBtn = document.createElement('button');
                    useBtn.className = 'history-btn use-btn';
                    useBtn.innerHTML = '↑';
                    useBtn.title = 'Utiliser ce mot de passe';
                    useBtn.addEventListener('click', function() {
                        generatedPasswordInput.value = password;
                    });
                    
                    const copyBtn = document.createElement('button');
                    copyBtn.className = 'history-btn copy-btn';
                    copyBtn.innerHTML = '📋';
                    copyBtn.title = 'Copier dans le presse-papiers';
                    copyBtn.addEventListener('click', function() {
                        copyToClipboard(password);
                    });
                    
                    actions.appendChild(useBtn);
                    actions.appendChild(copyBtn);
                    
                    item.appendChild(passwordDisplay);
                    item.appendChild(actions);
                    
                    historyList.appendChild(item);
                });
            }
            
            // Copier le mot de passe dans le presse-papiers
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        // Afficher la notification
                        clipboardNotification.classList.add('show');
                        
                        // Masquer la notification après 2 secondes
                        setTimeout(() => {
                            clipboardNotification.classList.remove('show');
                        }, 2000);
                    })
                    .catch(err => {
                        console.error('Erreur lors de la copie dans le presse-papiers:', err);
                        
                        // Méthode de secours
                        const tempInput = document.createElement('input');
                        tempInput.value = text;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempInput);
                        
                        // Afficher la notification
                        clipboardNotification.classList.add('show');
                        
                        // Masquer la notification après 2 secondes
                        setTimeout(() => {
                            clipboardNotification.classList.remove('show');
                        }, 2000);
                    });
            }
            
            // Enregistrer les paramètres par défaut
            function saveDefaults() {
                const params = {
                    length: parseInt(passwordLengthSlider.value),
                    include_uppercase: includeUppercase.checked,
                    include_lowercase: includeLowercase.checked,
                    include_numbers: includeNumbers.checked,
                    include_symbols: includeSymbols.checked,
                    exclude_similar: excludeSimilar.checked
                };
                
                fetch('api/save_password_defaults.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(params)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Paramètres par défaut enregistrés avec succès.');
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de l\'enregistrement des paramètres.');
                });
            }
            
            // Événements
            generateBtn.addEventListener('click', generatePassword);
            saveDefaultsBtn.addEventListener('click', saveDefaults);
            
            // Copier le mot de passe affiché
            copyPasswordBtn.addEventListener('click', function() {
                copyToClipboard(generatedPasswordInput.value);
            });
            
            // Ajouter le mot de passe initial à l'historique
            addToHistory(generatedPasswordInput.value);
            
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