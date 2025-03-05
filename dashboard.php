<?php
// dashboard.php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
requireAuth();

// Récupérer les entrées de mot de passe de l'utilisateur
$passwordEntries = PasswordEntry::getAllByUserId($_SESSION['user_id']);

// Récupérer les statistiques
$totalEntries = count($passwordEntries);
$totalFavorites = count(array_filter($passwordEntries, function($entry) {
    return $entry->isFavorite();
}));

// Récupérer les catégories distinctes
$categories = [];
foreach ($passwordEntries as $entry) {
    $category = $entry->getCategory();
    if (!in_array($category, $categories)) {
        $categories[] = $category;
    }
}
$totalCategories = count($categories);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - <?php echo APP_NAME; ?></title>
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
                    <li class="active"><a href="dashboard.php"><span class="icon">🏠</span> Tableau de bord</a></li>
                    <li><a href="passwords.php"><span class="icon">🔑</span> Mots de passe</a></li>
                    <li><a href="categories.php"><span class="icon">📂</span> Catégories</a></li>
                    <li><a href="generator.php"><span class="icon">🎲</span> Générateur</a></li>
                    <li><a href="settings.php"><span class="icon">⚙️</span> Paramètres</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <div class="user-avatar">
                    <!-- Initial de l'utilisateur comme avatar -->
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
                <h2>Tableau de bord</h2>
                <div class="actions">
                    <a href="add_password.php" class="btn btn-primary">
                        <span class="icon">➕</span> Nouveau mot de passe
                    </a>
                </div>
            </header>
            
            <!-- Statistiques -->
            <section class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">🔑</div>
                    <div class="stat-content">
                        <h3>Total des mots de passe</h3>
                        <p class="stat-number"><?php echo $totalEntries; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <h3>Favoris</h3>
                        <p class="stat-number"><?php echo $totalFavorites; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📂</div>
                    <div class="stat-content">
                        <h3>Catégories</h3>
                        <p class="stat-number"><?php echo $totalCategories; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔒</div>
                    <div class="stat-content">
                        <h3>Niveau de sécurité</h3>
                        <div class="security-level">
                            <div class="security-bar" style="width: 75%;"></div>
                        </div>
                        <p class="security-label">Bon</p>
                    </div>
                </div>
            </section>
            
            <!-- Favoris -->
            <section class="password-section">
                <div class="section-header">
                    <h3>Favoris</h3>
                    <a href="passwords.php?filter=favorite" class="view-all">Voir tout</a>
                </div>
                
                <div class="password-list">
                    <?php
                    $favoritesShown = 0;
                    foreach ($passwordEntries as $entry) {
                        if ($entry->isFavorite() && $favoritesShown < 5):
                            $favoritesShown++;
                    ?>
                    <div class="password-item">
                        <div class="password-icon">
                            <?php echo substr($entry->getTitle(), 0, 1); ?>
                        </div>
                        <div class="password-details">
                            <h4><?php echo htmlspecialchars($entry->getTitle()); ?></h4>
                            <p class="username"><?php echo htmlspecialchars($entry->getUsername()); ?></p>
                        </div>
                        <div class="password-actions">
                            <a href="view_password.php?id=<?php echo $entry->getId(); ?>" class="action-btn view-btn" title="Voir">👁️</a>
                            <a href="edit_password.php?id=<?php echo $entry->getId(); ?>" class="action-btn edit-btn" title="Modifier">✏️</a>
                        </div>
                    </div>
                    <?php endif; } ?>
                    
                    <?php if ($favoritesShown === 0): ?>
                    <p class="empty-list">Aucun favori pour le moment. Marquez vos mots de passe importants comme favoris pour les retrouver ici rapidement.</p>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Récemment ajoutés -->
            <section class="password-section">
                <div class="section-header">
                    <h3>Récemment ajoutés</h3>
                    <a href="passwords.php?sort=newest" class="view-all">Voir tout</a>
                </div>
                
                <div class="password-list">
                    <?php
                    // Trier les entrées par date de création (les plus récentes d'abord)
                    usort($passwordEntries, function($a, $b) {
                        return strtotime($b->getCreatedAt()) - strtotime($a->getCreatedAt());
                    });
                    
                    $recentShown = 0;
                    foreach ($passwordEntries as $entry) {
                        if ($recentShown < 5):
                            $recentShown++;
                    ?>
                    <div class="password-item">
                        <div class="password-icon">
                            <?php echo substr($entry->getTitle(), 0, 1); ?>
                        </div>
                        <div class="password-details">
                            <h4><?php echo htmlspecialchars($entry->getTitle()); ?></h4>
                            <p class="username"><?php echo htmlspecialchars($entry->getUsername()); ?></p>
                            <p class="date">Ajouté le <?php echo date('d/m/Y', strtotime($entry->getCreatedAt())); ?></p>
                        </div>
                        <div class="password-actions">
                            <a href="view_password.php?id=<?php echo $entry->getId(); ?>" class="action-btn view-btn" title="Voir">👁️</a>
                            <a href="edit_password.php?id=<?php echo $entry->getId(); ?>" class="action-btn edit-btn" title="Modifier">✏️</a>
                        </div>
                    </div>
                    <?php endif; } ?>
                    
                    <?php if ($recentShown === 0): ?>
                    <p class="empty-list">Aucun mot de passe pour le moment. Ajoutez votre premier mot de passe pour commencer.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        // Script pour l'expiration de la session
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
    </script>
</body>
</html>