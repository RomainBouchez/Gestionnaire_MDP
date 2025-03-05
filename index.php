<?php
// index.php
require_once 'init.php';

// Rediriger vers le tableau de bord si déjà connecté
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Gestionnaire de mots de passe sécurisé</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Gestionnaire de mots de passe sécurisé avec chiffrement de bout en bout</p>
        </header>
        
        <main>
            <section class="features">
                <h2>Fonctionnalités</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h3>Stockage sécurisé</h3>
                        <p>Tous vos mots de passe sont chiffrés avec AES-256-GCM, même nous ne pouvons pas les voir.</p>
                    </div>
                    <div class="feature-card">
                        <h3>Générateur de mots de passe</h3>
                        <p>Créez des mots de passe forts et uniques pour chaque site ou service.</p>
                    </div>
                    <div class="feature-card">
                        <h3>Organisation facile</h3>
                        <p>Classez vos mots de passe par catégories et marquez vos favoris.</p>
                    </div>
                    <div class="feature-card">
                        <h3>Accès sécurisé</h3>
                        <p>Authentification robuste avec un seul mot de passe maître à retenir.</p>
                    </div>
                </div>
            </section>
            
            <section class="cta">
                <h2>Commencez dès maintenant</h2>
                <div class="button-group">
                    <a href="login.php" class="btn btn-primary">Connexion</a>
                    <a href="register.php" class="btn btn-secondary">Créer un compte</a>
                </div>
            </section>
            
            <section class="security-info">
                <h2>Comment nous protégeons vos données</h2>
                <ul>
                    <li><strong>Chiffrement local</strong> - Vos données sont chiffrées et déchiffrées localement sur votre appareil.</li>
                    <li><strong>Chiffrement côté serveur</strong> - Une deuxième couche de chiffrement protège vos données sur le serveur.</li>
                    <li><strong>Protection par mot de passe maître</strong> - Seule votre clé maître, dérivée de votre mot de passe, peut déchiffrer vos données.</li>
                    <li><strong>Aucun stockage de mot de passe en clair</strong> - Nous ne stockons jamais vos mots de passe en clair, même pas temporairement.</li>
                </ul>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Tous droits réservés</p>
        </footer>
    </div>
</body>
</html>