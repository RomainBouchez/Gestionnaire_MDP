<?php
// config/config.php
define('APP_NAME', 'SecurePass Manager');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/password-manager');
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDE_PATH', ROOT_PATH . 'includes/');
define('CLASS_PATH', ROOT_PATH . 'classes/');
define('VIEW_PATH', ROOT_PATH . 'views/');
define('ASSET_PATH', ROOT_PATH . 'assets/');

// Paramètres de sécurité
define('ENCRYPTION_METHOD', 'aes-256-gcm'); // Méthode de chiffrement
define('PASSWORD_HASH_ALGO', PASSWORD_ARGON2ID); // Algorithme de hachage pour les mots de passe
define('SESSION_LIFETIME', 900); // 15 minutes en secondes
define('PBKDF2_ITERATIONS', 100000); // Nombre d'itérations pour PBKDF2
define('SALT_LENGTH', 32); // Longueur du sel en octets
define('KEY_LENGTH', 32); // Longueur de la clé en octets (256 bits)

// Paramètres de génération de mot de passe par défaut
define('DEFAULT_PASSWORD_LENGTH', 16);
define('DEFAULT_INCLUDE_UPPERCASE', true);
define('DEFAULT_INCLUDE_LOWERCASE', true);
define('DEFAULT_INCLUDE_NUMBERS', true);
define('DEFAULT_INCLUDE_SYMBOLS', true);
define('DEFAULT_EXCLUDE_SIMILAR', true);

// Chemin du fichier de journalisation
define('LOG_FILE', ROOT_PATH . 'logs/app.log');

// Paramètres des cookies
define('COOKIE_SECURE', true); // Cookies sécurisés (HTTPS uniquement)
define('COOKIE_HTTPONLY', true); // Cookies accessibles uniquement via HTTP
define('COOKIE_SAMESITE', 'Strict'); // Protection CSRF