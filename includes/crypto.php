<?php
// includes/crypto.php

/**
 * Chiffre des données avec AES-256-GCM
 * 
 * @param string $plaintext Texte à chiffrer
 * @param string $key Clé de chiffrement
 * @param string &$iv Référence pour stocker le vecteur d'initialisation généré
 * @return string Données chiffrées encodées en base64
 */
function encryptData($plaintext, $key, &$iv) {
    // Générer un IV aléatoire
    $iv = random_bytes(16);
    
    // Chiffrer les données
    $ciphertext = openssl_encrypt(
        $plaintext,
        ENCRYPTION_METHOD,
        $key,
        0, // options
        $iv,
        $tag // GCM tag pour l'authentification
    );
    
    if ($ciphertext === false) {
        throw new Exception("Erreur de chiffrement: " . openssl_error_string());
    }
    
    // Combiner le tag d'authentification avec le texte chiffré
    $encryptedData = $tag . $ciphertext;
    
    return base64_encode($encryptedData);
}

/**
 * Déchiffre des données chiffrées avec AES-256-GCM
 * 
 * @param string $encryptedData Données chiffrées encodées en base64
 * @param string $key Clé de déchiffrement
 * @param string $iv Vecteur d'initialisation utilisé pour le chiffrement
 * @return string Texte déchiffré ou false en cas d'échec
 */
function decryptData($encryptedData, $key, $iv) {
    // Décoder les données
    $encryptedBinary = base64_decode($encryptedData);
    
    // Extraire le tag d'authentification (16 octets pour GCM)
    $tag = substr($encryptedBinary, 0, 16);
    $ciphertext = substr($encryptedBinary, 16);
    
    // Déchiffrer les données
    $plaintext = openssl_decrypt(
        $ciphertext,
        ENCRYPTION_METHOD,
        $key,
        0, // options
        $iv,
        $tag
    );
    
    if ($plaintext === false) {
        throw new Exception("Erreur de déchiffrement: " . openssl_error_string());
    }
    
    return $plaintext;
}

/**
 * Dérive une clé à partir d'un mot de passe et d'un sel
 * 
 * @param string $password Mot de passe
 * @param string $salt Sel
 * @param int $iterations Nombre d'itérations (par défaut: valeur de la constante PBKDF2_ITERATIONS)
 * @param int $length Longueur de la clé en octets (par défaut: valeur de la constante KEY_LENGTH)
 * @return string Clé dérivée en format binaire
 */
function deriveKey($password, $salt, $iterations = PBKDF2_ITERATIONS, $length = KEY_LENGTH) {
    return hash_pbkdf2(
        'sha256',
        $password,
        $salt,
        $iterations,
        $length * 2, // Multiplication par 2 car hash_pbkdf2 retourne une chaîne hexadécimale
        true // Retourner des données binaires
    );
}

/**
 * Génère un sel aléatoire sécurisé
 * 
 * @param int $length Longueur du sel en octets (par défaut: valeur de la constante SALT_LENGTH)
 * @return string Sel aléatoire en format hexadécimal
 */
function generateSalt($length = SALT_LENGTH) {
    return bin2hex(random_bytes($length));
}

/**
 * Vérifie l'intégrité des données chiffrées
 * 
 * @param string $encryptedData Données chiffrées encodées en base64
 * @param string $key Clé de déchiffrement
 * @param string $iv Vecteur d'initialisation utilisé pour le chiffrement
 * @return bool True si les données sont intègres, false sinon
 */
function verifyDataIntegrity($encryptedData, $key, $iv) {
    try {
        $plaintext = decryptData($encryptedData, $key, $iv);
        return ($plaintext !== false);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Chiffre une chaîne de caractères pour stockage dans la base de données
 * 
 * @param string $string Chaîne à chiffrer
 * @param string $masterKey Clé maître
 * @return array Tableau contenant les données chiffrées et le vecteur d'initialisation
 */
function encryptString($string, $masterKey) {
    $iv = null;
    $encrypted = encryptData($string, $masterKey, $iv);
    
    return [
        'encrypted' => $encrypted,
        'iv' => bin2hex($iv) // Convertir en hexadécimal pour stockage
    ];
}

/**
 * Déchiffre une chaîne de caractères stockée dans la base de données
 * 
 * @param string $encryptedString Chaîne chiffrée
 * @param string $iv Vecteur d'initialisation en format hexadécimal
 * @param string $masterKey Clé maître
 * @return string Chaîne déchiffrée
 */
function decryptString($encryptedString, $iv, $masterKey) {
    // Convertir l'IV de format hexadécimal en binaire
    $binaryIv = hex2bin($iv);
    
    return decryptData($encryptedString, $masterKey, $binaryIv);
}