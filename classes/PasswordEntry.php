<?php
// classes/PasswordEntry.php
require_once CLASS_PATH . 'Database.php';

class PasswordEntry {
    private $db;
    private $id;
    private $userId;
    private $title;
    private $username;
    private $email;
    private $encryptedPassword;
    private $websiteUrl;
    private $notes;
    private $category;
    private $favorite;
    private $createdAt;
    private $updatedAt;
    private $iv; // Vecteur d'initialisation
    
    public function __construct($entryData = null) {
        $this->db = Database::getInstance();
        
        if (is_array($entryData)) {
            $this->id = $entryData['id'] ?? null;
            $this->userId = $entryData['user_id'] ?? null;
            $this->title = $entryData['title'] ?? null;
            $this->username = $entryData['username'] ?? null;
            $this->email = $entryData['email'] ?? null;
            $this->encryptedPassword = $entryData['password_encrypted'] ?? null;
            $this->websiteUrl = $entryData['website_url'] ?? null;
            $this->notes = $entryData['notes'] ?? null;
            $this->category = $entryData['category'] ?? 'General';
            $this->favorite = $entryData['favorite'] ?? false;
            $this->createdAt = $entryData['created_at'] ?? null;
            $this->updatedAt = $entryData['updated_at'] ?? null;
            $this->iv = $entryData['iv'] ?? null;
        }
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getWebsiteUrl() {
        return $this->websiteUrl;
    }
    
    public function getCategory() {
        return $this->category;
    }
    
    public function isFavorite() {
        return $this->favorite;
    }
    
    public function getCreatedAt() {
        return $this->createdAt;
    }
    
    public function getUpdatedAt() {
        return $this->updatedAt;
    }
    
    // Méthodes pour le chiffrement et déchiffrement
    
    // Chiffrer le mot de passe avec la clé maître
    public static function encryptPassword($password, $masterKey) {
        // Générer un IV aléatoire
        $iv = random_bytes(16); // 16 octets pour AES
        
        // Chiffrer le mot de passe
        $encrypted = openssl_encrypt(
            $password,
            ENCRYPTION_METHOD,
            $masterKey,
            0, // options
            $iv,
            $tag // Pour les modes d'authentification comme GCM
        );
        
        if ($encrypted === false) {
            throw new Exception("Erreur de chiffrement: " . openssl_error_string());
        }
        
        // Combiner le tag d'authentification avec le texte chiffré
        $encryptedWithTag = $tag . $encrypted;
        
        return [
            'encrypted' => base64_encode($encryptedWithTag),
            'iv' => bin2hex($iv)
        ];
    }
    
    // Déchiffrer le mot de passe avec la clé maître
    public static function decryptPassword($encryptedWithTag, $iv, $masterKey) {
        // Décoder le texte chiffré et l'IV
        $encryptedData = base64_decode($encryptedWithTag);
        $iv = hex2bin($iv);
        
        // Extraire le tag d'authentification (16 octets pour GCM)
        $tag = substr($encryptedData, 0, 16);
        $encrypted = substr($encryptedData, 16);
        
        // Déchiffrer le mot de passe
        $decrypted = openssl_decrypt(
            $encrypted,
            ENCRYPTION_METHOD,
            $masterKey,
            0, // options
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            throw new Exception("Erreur de déchiffrement: " . openssl_error_string());
        }
        
        return $decrypted;
    }
    
    // Créer une nouvelle entrée de mot de passe
    public function create($userId, $title, $username, $email, $password, $websiteUrl, $notes, $category, $favorite, $masterKey) {
        try {
            // Chiffrer le mot de passe
            $encryptedData = self::encryptPassword($password, $masterKey);
            
            // Chiffrer les notes si elles ne sont pas vides
            $encryptedNotes = null;
            if (!empty($notes)) {
                $notesEncrypted = self::encryptPassword($notes, $masterKey);
                $encryptedNotes = $notesEncrypted['encrypted'];
                // Nous utilisons le même IV pour simplifier
            }
            
            // Insérer l'entrée dans la base de données
            $entryId = $this->db->insert(
                "INSERT INTO password_entries (user_id, title, username, email, password_encrypted, website_url, notes, category, favorite, iv) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $title,
                    $username,
                    $email,
                    $encryptedData['encrypted'],
                    $websiteUrl,
                    $encryptedNotes,
                    $category,
                    $favorite ? 1 : 0,
                    $encryptedData['iv']
                ]
            );
            
            if ($entryId) {
                $this->id = $entryId;
                $this->userId = $userId;
                $this->title = $title;
                $this->username = $username;
                $this->email = $email;
                $this->encryptedPassword = $encryptedData['encrypted'];
                $this->websiteUrl = $websiteUrl;
                $this->notes = $encryptedNotes;
                $this->category = $category;
                $this->favorite = $favorite;
                $this->iv = $encryptedData['iv'];
                
                // Enregistrer l'activité
                $user = User::getById($userId);
                if ($user) {
                    $user->logActivity('create_password', "Nouvelle entrée créée: {$title}");
                }
                
                return [
                    'success' => true,
                    'message' => 'Entrée créée avec succès',
                    'entry_id' => $entryId
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la création de l\'entrée'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    // Mettre à jour une entrée existante
    public function update($id, $title, $username, $email, $password, $websiteUrl, $notes, $category, $favorite, $masterKey) {
        try {
            // Vérifier que l'entrée existe et appartient à l'utilisateur
            $entry = $this->db->fetch(
                "SELECT * FROM password_entries WHERE id = ? AND user_id = ?",
                [$id, $this->userId]
            );
            
            if (!$entry) {
                return [
                    'success' => false,
                    'message' => 'Entrée non trouvée ou accès non autorisé'
                ];
            }
            
            // Chiffrer le mot de passe si modifié
            $encryptedPassword = $entry['password_encrypted'];
            $iv = $entry['iv'];
            
            if ($password !== null) {
                $encryptedData = self::encryptPassword($password, $masterKey);
                $encryptedPassword = $encryptedData['encrypted'];
                $iv = $encryptedData['iv'];
            }
            
            // Chiffrer les notes si modifiées
            $encryptedNotes = $entry['notes'];
            if ($notes !== null) {
                $notesEncrypted = self::encryptPassword($notes, $masterKey);
                $encryptedNotes = $notesEncrypted['encrypted'];
            }
            
            // Mettre à jour l'entrée
            $updated = $this->db->update(
                "UPDATE password_entries SET 
                title = ?, 
                username = ?, 
                email = ?, 
                password_encrypted = ?, 
                website_url = ?, 
                notes = ?, 
                category = ?, 
                favorite = ?,
                iv = ?
                WHERE id = ?",
                [
                    $title,
                    $username,
                    $email,
                    $encryptedPassword,
                    $websiteUrl,
                    $encryptedNotes,
                    $category,
                    $favorite ? 1 : 0,
                    $iv,
                    $id
                ]
            );
            
            if ($updated) {
                $this->title = $title;
                $this->username = $username;
                $this->email = $email;
                $this->encryptedPassword = $encryptedPassword;
                $this->websiteUrl = $websiteUrl;
                $this->notes = $encryptedNotes;
                $this->category = $category;
                $this->favorite = $favorite;
                $this->iv = $iv;
                
                // Enregistrer l'activité
                $user = User::getById($this->userId);
                if ($user) {
                    $user->logActivity('update_password', "Entrée mise à jour: {$title}");
                }
                
                return [
                    'success' => true,
                    'message' => 'Entrée mise à jour avec succès'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'entrée'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    // Supprimer une entrée
    public function delete($id, $userId) {
        try {
            // Vérifier que l'entrée existe et appartient à l'utilisateur
            $entry = $this->db->fetch(
                "SELECT title FROM password_entries WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );
            
            if (!$entry) {
                return [
                    'success' => false,
                    'message' => 'Entrée non trouvée ou accès non autorisé'
                ];
            }
            
            // Supprimer l'entrée
            $deleted = $this->db->delete(
                "DELETE FROM password_entries WHERE id = ?",
                [$id]
            );
            
            if ($deleted) {
                // Enregistrer l'activité
                $user = User::getById($userId);
                if ($user) {
                    $user->logActivity('delete_password', "Entrée supprimée: {$entry['title']}");
                }
                
                return [
                    'success' => true,
                    'message' => 'Entrée supprimée avec succès'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'entrée'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    // Récupérer une entrée par ID
    public static function getById($id, $userId, $masterKey) {
        $db = Database::getInstance();
        $entryData = $db->fetch(
            "SELECT * FROM password_entries WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
        
        if ($entryData) {
            $entry = new PasswordEntry($entryData);
            
            // Déchiffrer le mot de passe
            try {
                $decryptedPassword = self::decryptPassword(
                    $entryData['password_encrypted'],
                    $entryData['iv'],
                    $masterKey
                );
                
                // Déchiffrer les notes si elles existent
                $decryptedNotes = null;
                if (!empty($entryData['notes'])) {
                    $decryptedNotes = self::decryptPassword(
                        $entryData['notes'],
                        $entryData['iv'],
                        $masterKey
                    );
                }
                
                return [
                    'success' => true,
                    'entry' => $entry,
                    'password' => $decryptedPassword,
                    'notes' => $decryptedNotes
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Erreur de déchiffrement: ' . $e->getMessage()
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Entrée non trouvée'
        ];
    }
    
    // Récupérer toutes les entrées d'un utilisateur
    public static function getAllByUserId($userId) {
        $db = Database::getInstance();
        $entries = $db->fetchAll(
            "SELECT id, title, username, email, website_url, category, favorite, created_at, updated_at 
            FROM password_entries 
            WHERE user_id = ? 
            ORDER BY favorite DESC, title ASC",
            [$userId]
        );
        
        $result = [];
        foreach ($entries as $entryData) {
            $result[] = new PasswordEntry($entryData);
        }
        
        return $result;
    }
    
    // Rechercher des entrées
    public static function search($userId, $query) {
        $db = Database::getInstance();
        $searchTerm = "%{$query}%";
        
        $entries = $db->fetchAll(
            "SELECT id, title, username, email, website_url, category, favorite, created_at, updated_at 
            FROM password_entries 
            WHERE user_id = ? AND (title LIKE ? OR username LIKE ? OR email LIKE ? OR website_url LIKE ? OR category LIKE ?) 
            ORDER BY favorite DESC, title ASC",
            [$userId, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]
        );
        
        $result = [];
        foreach ($entries as $entryData) {
            $result[] = new PasswordEntry($entryData);
        }
        
        return $result;
    }
}