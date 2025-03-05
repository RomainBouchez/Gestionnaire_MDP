<?php
// includes/functions.php

/**
 * Fonctions utilitaires pour le gestionnaire de mots de passe
 */

/**
 * Nettoie et sécurise une chaîne pour l'affichage
 * 
 * @param string $str La chaîne à nettoyer
 * @return string La chaîne nettoyée
 */
function sanitizeOutput($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un jeton CSRF
 * 
 * @return string Jeton CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le jeton CSRF est valide
 * 
 * @param string $token Jeton CSRF à vérifier
 * @return bool True si le jeton est valide, false sinon
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Génère une chaîne aléatoire
 * 
 * @param int $length Longueur de la chaîne
 * @return string Chaîne aléatoire
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Formate une date à partir d'une chaîne SQL
 * 
 * @param string $dateStr Date au format SQL
 * @param string $format Format de sortie (par défaut : d/m/Y H:i)
 * @return string Date formatée
 */
function formatDate($dateStr, $format = 'd/m/Y H:i') {
    $date = new DateTime($dateStr);
    return $date->format($format);
}

/**
 * Calcule le temps écoulé depuis une date donnée
 * 
 * @param string $dateStr Date au format SQL
 * @return string Temps écoulé (ex: "il y a 2 heures")
 */
function timeAgo($dateStr) {
    $date = new DateTime($dateStr);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    } elseif ($diff->m > 0) {
        return $diff->m . ' mois';
    } elseif ($diff->d > 0) {
        return $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    } else {
        return 'à l\'instant';
    }
}

/**
 * Tronque un texte à une certaine longueur
 * 
 * @param string $text Texte à tronquer
 * @param int $length Longueur maximale
 * @param string $suffix Suffixe à ajouter si le texte est tronqué
 * @return string Texte tronqué
 */
function truncateText($text, $length = 50, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Génère un slug (URL conviviale) à partir d'une chaîne
 * 
 * @param string $str Chaîne d'entrée
 * @return string Slug généré
 */
function generateSlug($str) {
    // Remplacer les caractères non alphanumériques par des tirets
    $str = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($str)));
    // Supprimer les tirets en début et fin de chaîne
    $str = trim($str, '-');
    // Supprimer les tirets multiples
    $str = preg_replace('/-+/', '-', $str);
    
    return $str;
}

/**
 * Détecte l'URL d'un site à partir d'un nom
 * 
 * @param string $siteName Nom du site
 * @return string URL suggérée
 */
function suggestWebsiteUrl($siteName) {
    $siteName = strtolower(trim($siteName));
    
    // Liste des domaines populaires
    $popularDomains = [
        'facebook' => 'https://www.facebook.com',
        'twitter' => 'https://www.twitter.com',
        'instagram' => 'https://www.instagram.com',
        'linkedin' => 'https://www.linkedin.com',
        'github' => 'https://www.github.com',
        'youtube' => 'https://www.youtube.com',
        'amazon' => 'https://www.amazon.com',
        'google' => 'https://www.google.com',
        'gmail' => 'https://mail.google.com',
        'outlook' => 'https://outlook.live.com',
        'netflix' => 'https://www.netflix.com',
        'spotify' => 'https://www.spotify.com',
        'apple' => 'https://www.apple.com',
        'microsoft' => 'https://www.microsoft.com',
        'dropbox' => 'https://www.dropbox.com',
        'pinterest' => 'https://www.pinterest.com',
        'reddit' => 'https://www.reddit.com',
        'twitch' => 'https://www.twitch.tv',
        'discord' => 'https://discord.com'
    ];
    
    // Vérifier si le nom correspond à un domaine populaire
    foreach ($popularDomains as $key => $url) {
        if (strpos($siteName, $key) !== false) {
            return $url;
        }
    }
    
    // Sinon, suggérer un domaine générique
    return 'https://www.' . generateSlug($siteName) . '.com';
}

/**
 * Obtient l'icône favicon d'un site web
 * 
 * @param string $url URL du site
 * @return string URL du favicon ou icône par défaut
 */
function getFavicon($url) {
    if (empty($url)) {
        return 'assets/images/default-favicon.png';
    }
    
    // Extraire le domaine
    $parsedUrl = parse_url($url);
    if (!isset($parsedUrl['host'])) {
        return 'assets/images/default-favicon.png';
    }
    
    $domain = $parsedUrl['host'];
    
    // Retourner l'URL du service Google Favicon (ou autre service similaire)
    return 'https://www.google.com/s2/favicons?domain=' . $domain;
}

/**
 * Détecte si une requête provient d'un appareil mobile
 * 
 * @return bool True si l'appareil est mobile, false sinon
 */
function isMobileDevice() {
    return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
}

/**
 * Obtient l'adresse IP du client
 * 
 * @return string Adresse IP
 */
function getClientIP() {
    $ip = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Vérifier si l'IP est valide
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return 'unknown';
}

/**
 * Vérifie si une mise à jour est disponible
 * 
 * @param string $currentVersion Version actuelle
 * @param string $latestVersion Dernière version disponible
 * @return bool True si une mise à jour est disponible, false sinon
 */
function isUpdateAvailable($currentVersion, $latestVersion) {
    return version_compare($currentVersion, $latestVersion, '<');
}

/**
 * Envoie un email (wrapper simple)
 * 
 * @param string $to Adresse du destinataire
 * @param string $subject Sujet du mail
 * @param string $message Corps du message
 * @param array $headers En-têtes supplémentaires
 * @return bool True si l'email a été envoyé, false sinon
 */
function sendEmail($to, $subject, $message, $headers = []) {
    // Définir les en-têtes par défaut
    $defaultHeaders = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . APP_NAME . ' <noreply@example.com>'
    ];
    
    // Fusionner avec les en-têtes supplémentaires
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    // Envoyer l'email
    return mail($to, $subject, $message, implode("\r\n", $allHeaders));
}

/**
 * Vérifie si le délai entre deux actions est respecté
 * 
 * @param string $action Nom de l'action
 * @param int $delay Délai en secondes
 * @return bool True si le délai est respecté, false sinon
 */
function checkActionDelay($action, $delay = 60) {
    $lastActionTime = $_SESSION[$action . '_last_time'] ?? 0;
    $currentTime = time();
    
    if ($currentTime - $lastActionTime < $delay) {
        return false;
    }
    
    $_SESSION[$action . '_last_time'] = $currentTime;
    return true;
}

/**
 * Obtient la taille d'un dossier en octets
 * 
 * @param string $path Chemin du dossier
 * @return int Taille en octets
 */
function getDirSize($path) {
    $totalSize = 0;
    $files = scandir($path);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = $path . '/' . $file;
        
        if (is_dir($filePath)) {
            $totalSize += getDirSize($filePath);
        } else {
            $totalSize += filesize($filePath);
        }
    }
    
    return $totalSize;
}

/**
 * Formate une taille en octets en une chaîne lisible
 * 
 * @param int $bytes Taille en octets
 * @param int $precision Précision décimale
 * @return string Taille formatée (ex: "1.5 MB")
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}