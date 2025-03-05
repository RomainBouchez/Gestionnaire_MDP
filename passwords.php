<?php
// passwords.php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
requireAuth();

// Récupérer les paramètres de filtrage et de tri
$filter = $_GET['filter'] ?? '';
$sort = $_GET['sort'] ?? '';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Récupérer toutes les entrées de mot de passe de l'utilisateur
$passwordEntries = PasswordEntry::getAllByUserId($_SESSION['user_id']);

// Appliquer les filtres
if (!empty($search)) {
    $passwordEntries = PasswordEntry::search($_SESSION['user_id'], $search);
} else {
    // Filtrer par catégorie
    if (!empty($category)) {
        $passwordEntries = array_filter($passwordEntries, function($entry) use ($category) {
            return $entry->getCategory() === $category;
        });
    }
    
    // Filtrer par favoris
    if ($filter === 'favorite') {
        $passwordEntries = array_filter($passwordEntries, function($entry) {
            return $entry->isFavorite();
        });
    }
}

// Appliquer le tri
if ($sort === 'newest') {
    usort($passwordEntries, function($a, $b) {
        return strtotime($b->getCreatedAt()) - strtotime($a->getCreatedAt());
    });
} elseif ($sort === 'oldest') {
    usort($passwordEntries, function($a, $b) {
        return strtotime($a->getCreatedAt()) - strtotime($b->getCreatedAt());
    });
} elseif ($sort === 'name_asc') {
    usort($passwordEntries, function($a, $b) {
        return strcmp($a->getTitle(), $b->getTitle());
    });
} elseif ($sort === 'name_desc') {
    usort($passwordEntries, function($a, $b) {
        return strcmp($b->getTitle(), $a->getTitle());
    });
}

// Récupérer les catégories distinctes pour le filtre
$categories = [];
foreach ($passwordEntries as $entry) {
    $cat = $entry->getCategory();
    if (!in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}
sort($categories);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mots de passe - <?php echo APP_NAME; ?></title>
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
                <h2>Tous les mots de passe<?php echo !empty($category) ? ' - ' . htmlspecialchars($category) : ''; ?></h2>
                <div class="actions">
                    <a href="add_password.php" class="btn btn-primary">
                        <span class="icon">➕</span> Nouveau mot de passe
                    </a>
                </div>
            </header>
            
            <!-- Filtres et recherche -->
            <section class="filters-section">
                <form action="passwords.php" method="GET" class="search-form">
                    <div class="search-container">
                        <input type="text" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-btn">🔍</button>
                    </div>
                    
                    <div class="filters-container">
                        <select name="category" class="filter-select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="sort" class="filter-select">
                            <option value="" <?php echo $sort === '' ? 'selected' : ''; ?>>Trier par</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Plus récents</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Plus anciens</option>
                        </select>
                        
                        <button type="submit" class="btn btn-secondary">Appliquer</button>
                        <a href="passwords.php" class="btn btn-secondary">Réinitialiser</a>
                    </div>
                </form>
                
                <div class="filter-tags">
                    <?php if (!empty($search)): ?>
                        <div class="filter-tag">
                            Recherche: <?php echo htmlspecialchars($search); ?>
                            <a href="<?php echo removeQueryParam('search'); ?>" class="remove-tag">×</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($category)): ?>
                        <div class="filter-tag">
                            Catégorie: <?php echo htmlspecialchars($category); ?>
                            <a href="<?php echo removeQueryParam('category'); ?>" class="remove-tag">×</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($filter === 'favorite'): ?>
                        <div class="filter-tag">
                            Favoris uniquement
                            <a href="<?php echo removeQueryParam('filter'); ?>" class="remove-tag">×</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Liste des mots de passe -->
            <section class="password-section">
                <?php if (empty($passwordEntries)): ?>
                    <div class="empty-state">
                        <?php if (!empty($search) || !empty($category) || !empty($filter)): ?>
                            <p class="empty-message">Aucun résultat ne correspond à vos critères de recherche.</p>
                            <a href="passwords.php" class="btn btn-secondary">Réinitialiser les filtres</a>
                        <?php else: ?>
                            <p class="empty-message">Vous n'avez pas encore enregistré de mots de passe.</p>
                            <a href="add_password.php" class="btn btn-primary">Ajouter votre premier mot de passe</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="password-count">
                        <p><?php echo count($passwordEntries); ?> mot(s) de passe trouvé(s)</p>
                    </div>
                    
                    <div class="password-grid">
                        <?php foreach ($passwordEntries as $entry): ?>
                            <div class="password-card">
                                <div class="password-card-header">
                                    <div class="password-icon">
                                        <?php echo strtoupper(substr($entry->getTitle(), 0, 1)); ?>
                                    </div>
                                    <div class="password-title">
                                        <h3><?php echo htmlspecialchars($entry->getTitle()); ?></h3>
                                        <?php if ($entry->isFavorite()): ?>
                                            <span class="favorite-badge" title="Favori">⭐</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="password-card-body">
                                    <?php if (!empty($entry->getUsername())): ?>
                                        <p class="password-detail">
                                            <span class="detail-label">Nom d'utilisateur:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($entry->getUsername()); ?></span>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry->getEmail())): ?>
                                        <p class="password-detail">
                                            <span class="detail-label">Email:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($entry->getEmail()); ?></span>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry->getWebsiteUrl())): ?>
                                        <p class="password-detail">
                                            <span class="detail-label">Site web:</span>
                                            <a href="<?php echo htmlspecialchars($entry->getWebsiteUrl()); ?>" target="_blank" class="detail-value website-link">
                                                <?php echo htmlspecialchars($entry->getWebsiteUrl()); ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <p class="password-detail">
                                        <span class="detail-label">Catégorie:</span>
                                        <span class="detail-value category-badge"><?php echo htmlspecialchars($entry->getCategory()); ?></span>
                                    </p>
                                </div>
                                
                                <div class="password-card-footer">
                                    <div class="password-date">
                                        Ajouté le <?php echo date('d/m/Y', strtotime($entry->getCreatedAt())); ?>
                                    </div>
                                    <div class="password-actions">
                                        <a href="view_password.php?id=<?php echo $entry->getId(); ?>" class="action-btn view-btn" title="Voir">👁️</a>
                                        <a href="edit_password.php?id=<?php echo $entry->getId(); ?>" class="action-btn edit-btn" title="Modifier">✏️</a>
                                        <button type="button" class="action-btn delete-btn" data-id="<?php echo $entry->getId(); ?>" title="Supprimer">🗑️</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmer la suppression</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette entrée ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <form id="delete-form" action="delete_password.php" method="POST">
                    <input type="hidden" id="delete-id" name="id" value="">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                    <button type="button" class="btn btn-secondary close-modal">Annuler</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion de la modal de suppression
            const deleteModal = document.getElementById('delete-modal');
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const closeModalButtons = document.querySelectorAll('.close-modal');
            const deleteIdInput = document.getElementById('delete-id');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    deleteIdInput.value = id;
                    deleteModal.classList.add('show');
                });
            });
            
            closeModalButtons.forEach(button => {
                button.addEventListener('click', function() {
                    deleteModal.classList.remove('show');
                });
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === deleteModal) {
                    deleteModal.classList.remove('show');
                }
            });
            
            // Validation du formulaire de recherche
            const searchForm = document.querySelector('.search-form');
            searchForm.addEventListener('submit', function(event) {
                const searchInput = this.querySelector('input[name="search"]');
                if (searchInput.value.trim() === '') {
                    searchInput.name = ''; // Empêcher l'envoi du paramètre vide
                }
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
        
        // Fonction pour copier le texte dans le presse-papiers
        function copyToClipboard(text) {
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Afficher une notification
            showNotification('Copié dans le presse-papiers');
        }
        
        // Fonction pour afficher une notification
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.classList.add('notification');
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 2000);
        }
    </script>
</body>
</html>

<?php
// Fonction pour supprimer un paramètre de l'URL actuelle
function removeQueryParam($param) {
    $params = $_GET;
    unset($params[$param]);
    return '?' . http_build_query($params);
}
?>