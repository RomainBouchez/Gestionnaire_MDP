<?php
// categories.php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
requireAuth();

$error = '';
$success = '';

// Récupérer toutes les catégories de l'utilisateur
$db = Database::getInstance();
$categories = $db->fetchAll(
    "SELECT name, 
    (SELECT COUNT(*) FROM password_entries WHERE user_id = ? AND category = c.name) as entry_count 
    FROM categories c 
    WHERE user_id = ? 
    UNION 
    SELECT 'General' as name, 
    (SELECT COUNT(*) FROM password_entries WHERE user_id = ? AND category = 'General') as entry_count 
    ORDER BY name",
    [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]
);

// Traitement du formulaire d'ajout de catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $categoryName = trim($_POST['category_name'] ?? '');
        
        if (empty($categoryName)) {
            $error = 'Le nom de la catégorie ne peut pas être vide.';
        } elseif (strtolower($categoryName) === 'general') {
            $error = 'Vous ne pouvez pas créer une catégorie nommée "General" car elle existe déjà par défaut.';
        } else {
            // Vérifier si la catégorie existe déjà
            $existingCategory = $db->fetch(
                "SELECT name FROM categories WHERE user_id = ? AND LOWER(name) = LOWER(?)",
                [$_SESSION['user_id'], $categoryName]
            );
            
            if ($existingCategory) {
                $error = 'Cette catégorie existe déjà.';
            } else {
                // Ajouter la nouvelle catégorie
                $db->insert(
                    "INSERT INTO categories (user_id, name) VALUES (?, ?)",
                    [$_SESSION['user_id'], $categoryName]
                );
                
                // Enregistrer l'activité
                $user = User::getById($_SESSION['user_id']);
                if ($user) {
                    $user->logActivity('create_category', "Nouvelle catégorie créée: {$categoryName}");
                }
                
                $success = 'Catégorie ajoutée avec succès.';
                
                // Rafraîchir la liste des catégories
                $categories = $db->fetchAll(
                    "SELECT name, 
                    (SELECT COUNT(*) FROM password_entries WHERE user_id = ? AND category = c.name) as entry_count 
                    FROM categories c 
                    WHERE user_id = ? 
                    UNION 
                    SELECT 'General' as name, 
                    (SELECT COUNT(*) FROM password_entries WHERE user_id = ? AND category = 'General') as entry_count 
                    ORDER BY name",
                    [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]
                );
            }
        }
    } elseif ($_POST['action'] === 'rename') {
        $oldName = trim($_POST['old_name'] ?? '');
        $newName = trim($_POST['new_name'] ?? '');
        
        if (empty($newName)) {
            $error = 'Le nouveau nom de la catégorie ne peut pas être vide.';
        } elseif (strtolower($oldName) === 'general') {
            $error = 'Vous ne pouvez pas renommer la catégorie "General" car c\'est une catégorie par défaut.';
        } elseif (strtolower($newName) === 'general') {
            $error = 'Vous ne pouvez pas utiliser "General" comme nom de catégorie car il est réservé.';
        } else {
            // Vérifier si la nouvelle catégorie existe déjà
            $existingCategory = $db->fetch(
                "SELECT name FROM categories WHERE user_id = ? AND LOWER(name) = LOWER(?) AND LOWER(name) != LOWER(?)",
                [$_SESSION['user_id'], $newName, $oldName]
            );
            
            if ($existingCategory) {
                $error = 'Une catégorie avec ce nom existe déjà.';
            } else {
                // Mettre à jour le nom de la catégorie
                $db->update(
                    "UPDATE categories SET name = ? WHERE user_id = ? AND name = ?",
                    [$newName, $_SESSION['user_id'], $oldName]
                );
                
                // Mettre à jour les entrées de mot de passe associées
                $db->update(
                    "UPDATE password_entries SET category = ? WHERE user_id = ? AND category = ?",
                    [$newName, $_SESSION['user_id'], $oldName]
                );
                
                // Enregistrer l'activité
                $user = User::getById($_SESSION['user_id']);
                if ($user) {
                    $user->logActivity('rename_category', "Catégorie renommée: {$oldName} → {$newName}");
                }
                
                $success = 'Catégorie renommée avec succès.';
                
                // Rafraîchir la liste des catégories
                $categories = $db->fetchAll(
                    "SELECT name, 
                    (SELECT COUNT(*) FROM password_entries WHERE user_id = ? AND category = c.name) as entry_count 
                    FROM categories c 
                    WHERE user_id = ? 
                    UNION 
                    SELECT 'General' as name, 
                    (SELECT COUNT(*) FROM password_entries WHERE user_id = ? AND category = 'General') as entry_count 
                    ORDER BY name",
                    [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]
                );
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $categoryName = trim($_POST['category_name'] ?? '');
        
        if (strtolower($categoryName) === 'general') {
            $error = 'Vous ne pouvez pas supprimer la catégorie "General" car c\'est une catégorie par défaut.';
        } else {
            // Vérifier si la catégorie existe
            $existingCategory = $db->fetch(
                "SELECT name FROM categories WHERE user_id = ? AND name = ?",
                [$_SESSION['user_id'], $categoryName]
            );
            
            if (!$existingCategory) {
                $error = 'Cette catégorie n\'existe pas.';
            } else {
                // Vérifier si la catégorie contient des entrées
                $entryCount = $db->fetch(
                    "SELECT COUNT(*) as count FROM password_entries WHERE user_id = ? AND category = ?",
                    [$_SESSION['user_id'], $categoryName]
                );
                
                // Si option de transfert sélectionnée
                if (isset($_POST['transfer']) && $_POST['transfer'] === 'yes') {
                    $targetCategory = trim($_POST['target_category'] ?? 'General');
                    
                    // Déplacer toutes les entrées vers la catégorie cible
                    $db->update(
                        "UPDATE password_entries SET category = ? WHERE user_id = ? AND category = ?",
                        [$targetCategory, $_SESSION['user_id'], $categoryName]
                    );
                }
                
                // Supprimer la catégorie
                $db->delete(
                    "DELETE FROM categories WHERE user_id = ? AND name = ?",
                    [$_SESSION['user_id'], $categoryName]
                );
                
                // Enregistrer l'activité
                $user = User::getById($_SESSION['user_id']);
                if ($user) {
                    $user->logActivity('delete_category', "Catégorie supprimée: {$categoryName}");
                }
                
                $success = 'Catégorie supprimée avec succès.';
                
                // Rafraîchir la liste des catégories
                $categories = $db->fetchAll(
                    "SELECT name, 
                    (SELECT COUNT(*) FROM password_entries WHERE user_id = ? AND category = c.name) as entry_count 
                    FROM categories c 
                    WHERE user_id = ? 
                    UNION 
                    SELECT 'General' as name, 
                    (SELECT COUNT(*) FROM password_entries WHERE user_id = ? AND category = 'General') as entry_count 
                    ORDER BY name",
                    [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]
                );
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/categories.css">
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
                    <li class="active"><a href="categories.php"><span class="icon">📂</span> Catégories</a></li>
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
                <h2>Gestion des catégories</h2>
                <div class="actions">
                    <button id="add-category-btn" class="btn btn-primary">
                        <span class="icon">➕</span> Nouvelle catégorie
                    </button>
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
            
            <!-- Liste des catégories -->
            <section class="content-section">
                <div class="category-list">
                    <div class="category-header">
                        <div class="category-name">Nom de la catégorie</div>
                        <div class="category-count">Entrées</div>
                        <div class="category-actions">Actions</div>
                    </div>
                    
                    <?php if(empty($categories)): ?>
                        <div class="empty-state">
                            <p class="empty-message">Vous n'avez pas encore créé de catégories personnalisées.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($categories as $category): ?>
                            <div class="category-item">
                                <div class="category-name">
                                    <span class="category-icon">📂</span>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </div>
                                <div class="category-count">
                                    <?php echo (int)$category['entry_count']; ?> entrée(s)
                                </div>
                                <div class="category-actions">
                                    <a href="passwords.php?category=<?php echo urlencode($category['name']); ?>" class="action-btn view-btn" title="Voir les entrées">👁️</a>
                                    
                                    <?php if(strtolower($category['name']) !== 'general'): ?>
                                        <button type="button" class="action-btn rename-btn" data-name="<?php echo htmlspecialchars($category['name']); ?>" title="Renommer">✏️</button>
                                        <button type="button" class="action-btn delete-btn" data-name="<?php echo htmlspecialchars($category['name']); ?>" data-count="<?php echo (int)$category['entry_count']; ?>" title="Supprimer">🗑️</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    
    <!-- Modal d'ajout de catégorie -->
    <div id="add-category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nouvelle catégorie</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-category-form" action="categories.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="category_name">Nom de la catégorie</label>
                        <input type="text" id="category_name" name="category_name" required>
                        <small>Utilisez des noms courts et descriptifs comme "Travail", "Finance", "Réseaux sociaux", etc.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                        <button type="button" class="btn btn-secondary close-modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de renommage de catégorie -->
    <div id="rename-category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Renommer la catégorie</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="rename-category-form" action="categories.php" method="POST">
                    <input type="hidden" name="action" value="rename">
                    <input type="hidden" id="old_name" name="old_name" value="">
                    
                    <div class="form-group">
                        <label for="new_name">Nouveau nom</label>
                        <input type="text" id="new_name" name="new_name" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Renommer</button>
                        <button type="button" class="btn btn-secondary close-modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de suppression de catégorie -->
    <div id="delete-category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Supprimer la catégorie</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="delete-category-form" action="categories.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="category_to_delete" name="category_name" value="">
                    
                    <div id="category-has-entries" style="display: none;">
                        <p>Cette catégorie contient des entrées. Que souhaitez-vous faire ?</p>
                        
                        <div class="form-group radio-group">
                            <label>
                                <input type="radio" name="transfer" value="yes" checked>
                                Transférer les entrées vers une autre catégorie
                            </label>
                            
                            <div class="form-group sub-option">
                                <label for="target_category">Catégorie cible</label>
                                <select id="target_category" name="target_category">
                                    <option value="General">General</option>
                                    <?php foreach($categories as $cat): ?>
                                        <?php if($cat['name'] !== 'General'): ?>
                                            <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <p class="confirmation-message">Êtes-vous sûr de vouloir supprimer cette catégorie ? Cette action est irréversible.</p>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                        <button type="button" class="btn btn-secondary close-modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des modals
            const addCategoryBtn = document.getElementById('add-category-btn');
            const addCategoryModal = document.getElementById('add-category-modal');
            const renameCategoryModal = document.getElementById('rename-category-modal');
            const deleteCategoryModal = document.getElementById('delete-category-modal');
            const closeModalButtons = document.querySelectorAll('.close-modal');
            
            // Bouton d'ajout de catégorie
            addCategoryBtn.addEventListener('click', function() {
                addCategoryModal.classList.add('show');
            });
            
            // Boutons de renommage de catégorie
            const renameButtons = document.querySelectorAll('.rename-btn');
            renameButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const categoryName = this.getAttribute('data-name');
                    document.getElementById('old_name').value = categoryName;
                    document.getElementById('new_name').value = categoryName;
                    renameCategoryModal.classList.add('show');
                });
            });
            
            // Boutons de suppression de catégorie
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const categoryName = this.getAttribute('data-name');
                    const entryCount = parseInt(this.getAttribute('data-count'));
                    
                    document.getElementById('category_to_delete').value = categoryName;
                    
                    // Gérer l'affichage des options de transfert
                    const categoryHasEntries = document.getElementById('category-has-entries');
                    if (entryCount > 0) {
                        categoryHasEntries.style.display = 'block';
                        
                        // Retirer la catégorie actuelle des options de transfert
                        const targetCategorySelect = document.getElementById('target_category');
                        for (let i = 0; i < targetCategorySelect.options.length; i++) {
                            if (targetCategorySelect.options[i].value === categoryName) {
                                targetCategorySelect.remove(i);
                                break;
                            }
                        }
                    } else {
                        categoryHasEntries.style.display = 'none';
                    }
                    
                    deleteCategoryModal.classList.add('show');
                });
            });
            
            // Fermer les modals
            closeModalButtons.forEach(button => {
                button.addEventListener('click', function() {
                    addCategoryModal.classList.remove('show');
                    renameCategoryModal.classList.remove('show');
                    deleteCategoryModal.classList.remove('show');
                });
            });
            
            // Cliquer en dehors des modals pour les fermer
            window.addEventListener('click', function(event) {
                if (event.target === addCategoryModal) {
                    addCategoryModal.classList.remove('show');
                } else if (event.target === renameCategoryModal) {
                    renameCategoryModal.classList.remove('show');
                } else if (event.target === deleteCategoryModal) {
                    deleteCategoryModal.classList.remove('show');
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
    </script>
</body>
</html>