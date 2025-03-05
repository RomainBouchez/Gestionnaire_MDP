<?php
// classes/User.php
require_once CLASS_PATH . 'Database.php';

class User {
    private $db;
    private $id;
    private $username;
    private $email;
    
    public function __construct($userData = null) {
        $this->db = Database::getInstance();
        
        if (is_array($userData)) {
            $this->id = $userData['id'] ?? null;
            $this->username = $userData['username'] ?? null;
            $this->email = $userData['email'] ?? null;
        }
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    // Créer un nouvel utilisateur
    public function create($username, $email, $masterPassword) {
        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Nom d\'utilisateur ou email déjà utilisé'
            ];
        }
        
        // Générer un sel aléatoire pour la clé maître
        $masterKeySalt = bin2hex(random_bytes(32));
        
        // Dériver la clé maître du mot de passe principal
        $masterKey = $this->deriveMasterKey($masterPassword, $masterKeySalt);
        
        // Hacher la clé maître pour vérification ultérieure
        $masterKeyHash = password_hash($masterKey, PASSWORD_HASH_ALGO);
        
        // Hacher le mot de passe principal pour l'authentification
        $passwordHash = password_hash($masterPassword, PASSWORD_HASH_ALGO);
        
        // Insérer l'utilisateur dans la base de données
        $userId = $this->db->insert(
            "INSERT INTO users (username, email, password_hash, master_key_salt, master_key_hash) VALUES (?, ?, ?, ?, ?)",
            [$username, $email, $passwordHash, $masterKeySalt, $masterKeyHash]
        );
        
        if ($userId) {
            // Créer les paramètres par défaut pour l'utilisateur
            $this->db->insert(
                "INSERT INTO user_settings (user_id) VALUES (?)",
                [$userId]
            );
            
            $this->id = $userId;
            $this->username = $username;
            $this->email = $email;
            
            return [
                'success' => true,
                'message' => 'Compte créé avec succès',
                'user_id' => $userId
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erreur lors de la création du compte'
        ];
    }
    
    // Authentifier un utilisateur
    public function authenticate($usernameOrEmail, $password) {
        // Récupérer l'utilisateur
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE username = ? OR email = ?",
            [$usernameOrEmail, $usernameOrEmail]
        );
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Identifiants invalides'
            ];
        }
        
        // Authentification réussie
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->email = $user['email'];
        
        // Dériver la clé maître pour l'utiliser dans la session
        $masterKey = $this->deriveMasterKey($password, $user['master_key_salt']);
        
        // Vérifier que la clé maître est correcte
        if (!password_verify($masterKey, $user['master_key_hash'])) {
            return [
                'success' => false,
                'message' => 'Erreur de vérification de la clé maître'
            ];
        }
        
        // Enregistrer l'activité de connexion
        $this->logActivity('login', 'Connexion réussie');
        
        return [
            'success' => true,
            'message' => 'Authentification réussie',
            'user_id' => $this->id,
            'master_key' => $masterKey // Cette clé sera stockée temporairement en session
        ];
    }
    
    // Dériver la clé maître à partir du mot de passe et du sel
    private function deriveMasterKey($masterPassword, $salt) {
        // Utilisation de PBKDF2 pour dériver une clé de 256 bits (32 octets)
        return hash_pbkdf2(
            'sha256',
            $masterPassword,
            $salt,
            PBKDF2_ITERATIONS,
            KEY_LENGTH * 2, // Multiplication par 2 car hash_pbkdf2 retourne une chaîne hexadécimale
            true // Retourner des données binaires
        );
    }
    
    // Méthode pour journaliser l'activité
    public function logActivity($action, $description = '') {
        if (!$this->id) {
            return false;
        }
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return $this->db->insert(
            "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [$this->id, $action, $description, $ipAddress, $userAgent]
        );
    }
    
    // Récupérer un utilisateur par son ID
    public static function getById($userId) {
        $db = Database::getInstance();
        $userData = $db->fetch("SELECT id, username, email FROM users WHERE id = ?", [$userId]);
        
        if ($userData) {
            return new User($userData);
        }
        
        return null;
    }
    
    // Mettre à jour le mot de passe
    public function updatePassword($currentPassword, $newPassword) {
        // Vérifier le mot de passe actuel
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$this->id]);
        
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Mot de passe actuel incorrect'
            ];
        }
        
        // Générer un nouveau sel pour la clé maître
        $masterKeySalt = bin2hex(random_bytes(32));
        
        // Dériver la nouvelle clé maître
        $masterKey = $this->deriveMasterKey($newPassword, $masterKeySalt);
        
        // Hacher la nouvelle clé maître
        $masterKeyHash = password_hash($masterKey, PASSWORD_HASH_ALGO);
        
        // Hacher le nouveau mot de passe principal
        $passwordHash = password_hash($newPassword, PASSWORD_HASH_ALGO);
        
        // Mettre à jour l'utilisateur
        $updated = $this->db->update(
            "UPDATE users SET password_hash = ?, master_key_salt = ?, master_key_hash = ? WHERE id = ?",
            [$passwordHash, $masterKeySalt, $masterKeyHash, $this->id]
        );
        
        if ($updated) {
            // Enregistrer l'activité
            $this->logActivity('password_change', 'Mot de passe modifié');
            
            return [
                'success' => true,
                'message' => 'Mot de passe mis à jour avec succès',
                'master_key' => $masterKey // Cette clé sera stockée temporairement en session
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du mot de passe'
        ];
    }
}