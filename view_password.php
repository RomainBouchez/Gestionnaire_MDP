<?php
// view_password.php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
requireAuth();

// Récupérer l'ID du mot de passe
$passwordId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($passwordId <= 0) {
    header('Location: passwords.php');
    exit;
}

// Récupérer les détails du mot de passe
$passwordDetails = PasswordEntry::getById($passwordId, $_SESSION['user_id'], $_SESSION['master_key']);

// Vérifier si le mot de passe existe et appartient à l'utilisateur
if (!$passwordDetails['success']) {
    header('Location: passwords.php?error=not_found');
    exit;
}

$entry = $passwordDetails['entry'];
$password = $passwordDetails['password'];
$notes = $passwordDetails['notes'];

// Message de copie réussie
$copySuccess = false;
if (isset($_GET['copied']) && $_GET['copied'] == 1) {
    $copySuccess = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du mot de passe - <?php echo APP_NAME; ?></title>
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
                <h2>Détails du mot de passe</h2>
                <div class="actions">
                    <a href="edit_password.php?id=<?php echo $passwordId; ?>" class="btn btn-secondary">
                        <span class="icon">✏️</span> Modifier
                    </a>
                    <a href="passwords.php" class="btn btn-secondary">
                        <span class="icon">←</span> Retour
                    </a>
                </div>
            </header>
            
            <?php if($copySuccess): ?>
                <div class="alert alert-success">
                    Mot de passe copié dans le presse-papier.
                </div>
            <?php endif; ?>
            
            <section class="content-section">
                <div class="password-details-container">
                    <div class="password-header">
                        <div class="password-title-group">
                            <div class="password-icon large">
                                <?php echo substr($entry->getTitle(), 0, 1); ?>
                            </div>
                            <div>
                                <h3><?php echo htmlspecialchars($entry->getTitle()); ?></h3>
                                <p class="password-category">
                                    <?php echo htmlspecialchars($entry->getCategory()); ?>
                                    <?php if($entry->isFavorite()): ?>
                                        <span class="favorite-badge">⭐ Favori</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="password-actions">
                            <form method="post" action="delete_password.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce mot de passe ?');">
                                <input type="hidden" name="id" value="<?php echo $passwordId; ?>">
                                <button type="submit" class="btn btn-danger">
                                    <span class="icon">🗑️</span> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="password-details-grid">
                        <?php if(!empty($entry->getUsername())): ?>
                        <div class="detail-item">
                            <h4>Nom d'utilisateur</h4>
                            <div class="copyable-field">
                                <p><?php echo htmlspecialchars($entry->getUsername()); ?></p>
                                <button type="button" class="copy-btn" data-copy="<?php echo htmlspecialchars($entry->getUsername()); ?>" title="Copier">
                                    <span class="icon">📋</span>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($entry->getEmail())): ?>
                        <div class="detail-item">
                            <h4>Email</h4>
                            <div class="copyable-field">
                                <p><?php echo htmlspecialchars($entry->getEmail()); ?></p>
                                <button type="button" class="copy-btn" data-copy="<?php echo htmlspecialchars($entry->getEmail()); ?>" title="Copier">
                                    <span class="icon">📋</span>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <h4>Mot de passe</h4>
                            <div class="copyable-field">
                                <div class="password-field">
                                    <input type="password" id="password-display" value="<?php echo htmlspecialchars($password); ?>" readonly>
                                    <button type="button" class="toggle-password" title="Afficher/Masquer">
                                        <svg class="eye-icon" viewBox="0 0 24 24" width="24" height="24">
                                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                        </svg>
                                    </button>
                                </div>
                                <button type="button" class="copy-btn" data-copy="<?php echo htmlspecialchars($password); ?>" title="Copier">
                                    <span class="icon">📋</span>
                                </button>
                            </div>
                            <div class="password-strength-meter">
                                <div class="strength-bar <?php 
                                    $score = evaluatePasswordStrength($password)['score'];
                                    if ($score >= 80) echo 'strength-high';
                                    elseif ($score >= 60) echo 'strength-good';
                                    elseif ($score >= 40) echo 'strength-medium';
                                    elseif ($score >= 20) echo 'strength-weak';
                                    else echo 'strength-very-weak';
                                ?>" style="width: <?php echo $score; ?>%;"></div>
                            </div>
                            <div class="password-feedback">
                                <?php echo evaluatePasswordStrength($password)['strength']; ?>
                            </div>
                        </div>
                        
                        <?php if(!empty($entry->getWebsiteUrl())): ?>
                        <div class="detail-item">
                            <h4>Site Web</h4>
                            <div class="copyable-field">
                                <p>
                                    <a href="<?php echo htmlspecialchars($entry->getWebsiteUrl()); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo htmlspecialchars($entry->getWebsiteUrl()); ?>
                                    </a>
                                </p>
                                <button type="button" class="copy-btn" data-copy="<?php echo htmlspecialchars($entry->getWebsiteUrl()); ?>" title="Copier">
                                    <span class="icon">📋</span>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($notes)): ?>
                        <div class="detail-item full-width">
                            <h4>Notes</h4>
                            <div class="notes-content">
                                <?php echo nl2br(htmlspecialchars($notes)); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <h4>Date de création</h4>
                            <p><?php echo date('d/m/Y à H:i', strtotime($entry->getCreatedAt())); ?></p>
                        </div>
                        
                        <div class="detail-item">
                            <h4>Dernière modification</h4>
                            <p><?php echo date('d/m/Y à H:i', strtotime($entry->getUpdatedAt())); ?></p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Afficher/masquer le mot de passe
            const toggleButton = document.querySelector('.toggle-password');
            const passwordInput = document.getElementById('password-display');
            
            toggleButton.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
            });
            
            // Copier dans le presse-papier
            const copyButtons = document.querySelectorAll('.copy-btn');
            
            copyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const textToCopy = this.getAttribute('data-copy');
                    
                    // Créer un élément temporaire
                    const temp = document.createElement('textarea');
                    temp.value = textToCopy;
                    document.body.appendChild(temp);
                    
                    // Sélectionner et copier le texte
                    temp.select();
                    document.execCommand('copy');
                    
                    // Supprimer l'élément temporaire
                    document.body.removeChild(temp);
                    
                    // Afficher une confirmation
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span class="icon">✓</span>';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                    }, 2000);
                });
            });
            
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
    
    <style>
        .password-details-container {
            padding: 1rem;
        }
        
        .password-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .password-title-group {
            display: flex;
            align-items: center;
        }
        
        .password-icon.large {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            margin-right: 1.5rem;
        }
        
        .password-category {
            color: var(--secondary-color);
            margin-top: 0.25rem;
        }
        
        .favorite-badge {
            margin-left: 0.5rem;
            color: #f1c40f;
        }
        
        .password-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .detail-item {
            margin-bottom: 1.5rem;
        }
        
        .detail-item h4 {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .copyable-field {
            display: flex;
            align-items: center;
        }
        
        .copyable-field p {
            margin: 0;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 0.5rem 0;
        }
        
        .password-field {
            flex: 1;
            position: relative;
        }
        
        .password-field input {
            width: 100%;
            padding-right: 40px;
            font-family: monospace;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--secondary-color);
            cursor: pointer;
        }
        
        .copy-btn {
            background: none;
            border: none;
            color: var(--secondary-color);
            cursor: pointer;
            padding: 0.5rem;
            margin-left: 0.5rem;
        }
        
        .copy-btn:hover {
            color: var(--primary-color);
        }
        
        .notes-content {
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            white-space: pre-line;
        }
        
        @media (max-width: 768px) {
            .password-details-grid {
                grid-template-columns: 1fr;
            }
            
            .password-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .password-actions {
                margin-top: 1rem;
                align-self: flex-end;
            }
        }
    </style>
</body>
</html>