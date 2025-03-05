<?php
// includes/password_generator.php

/**
 * Génère un mot de passe aléatoire selon les paramètres spécifiés
 * 
 * @param int $length Longueur du mot de passe
 * @param bool $includeUppercase Inclure des lettres majuscules
 * @param bool $includeLowercase Inclure des lettres minuscules
 * @param bool $includeNumbers Inclure des chiffres
 * @param bool $includeSymbols Inclure des symboles
 * @param bool $excludeSimilar Exclure les caractères similaires (0, O, 1, l, I, etc.)
 * @return string Le mot de passe généré
 */
function generatePassword(
    $length = DEFAULT_PASSWORD_LENGTH,
    $includeUppercase = DEFAULT_INCLUDE_UPPERCASE,
    $includeLowercase = true,
    $includeNumbers = DEFAULT_INCLUDE_NUMBERS,
    $includeSymbols = DEFAULT_INCLUDE_SYMBOLS,
    $excludeSimilar = DEFAULT_EXCLUDE_SIMILAR
) {
    // Définir les ensembles de caractères
    $lowercaseChars = 'abcdefghijklmnopqrstuvwxyz';
    $uppercaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numberChars = '0123456789';
    $symbolChars = '!@#$%^&*()-_=+[]{};:,.<>?/|';
    
    // Exclure les caractères similaires si demandé
    if ($excludeSimilar) {
        $lowercaseChars = str_replace(['l', 'i', 'o'], '', $lowercaseChars);
        $uppercaseChars = str_replace(['I', 'O'], '', $uppercaseChars);
        $numberChars = str_replace(['0', '1'], '', $numberChars);
    }
    
    // Construire l'ensemble de caractères à utiliser
    $charset = '';
    if ($includeLowercase) {
        $charset .= $lowercaseChars;
    }
    if ($includeUppercase) {
        $charset .= $uppercaseChars;
    }
    if ($includeNumbers) {
        $charset .= $numberChars;
    }
    if ($includeSymbols) {
        $charset .= $symbolChars;
    }
    
    // Si aucun ensemble n'est sélectionné, utiliser les minuscules par défaut
    if (empty($charset)) {
        $charset = $lowercaseChars;
    }
    
    $charsetLength = strlen($charset);
    $password = '';
    
    // Générer le mot de passe
    for ($i = 0; $i < $length; $i++) {
        $randomIndex = random_int(0, $charsetLength - 1);
        $password .= $charset[$randomIndex];
    }
    
    // Vérifier que le mot de passe contient au moins un caractère de chaque type demandé
    $containsLowercase = $includeLowercase ? preg_match('/[a-z]/', $password) : true;
    $containsUppercase = $includeUppercase ? preg_match('/[A-Z]/', $password) : true;
    $containsNumber = $includeNumbers ? preg_match('/[0-9]/', $password) : true;
    $containsSymbol = $includeSymbols ? preg_match('/[^a-zA-Z0-9]/', $password) : true;
    
    // Si le mot de passe ne répond pas aux critères, en générer un nouveau
    if (!$containsLowercase || !$containsUppercase || !$containsNumber || !$containsSymbol) {
        return generatePassword(
            $length,
            $includeUppercase,
            $includeLowercase,
            $includeNumbers,
            $includeSymbols,
            $excludeSimilar
        );
    }
    
    return $password;
}

/**
 * Évalue la robustesse d'un mot de passe
 * 
 * @param string $password Le mot de passe à évaluer
 * @return array Score (0-100) et commentaire sur la robustesse
 */
function evaluatePasswordStrength($password) {
    $length = strlen($password);
    $score = 0;
    $feedback = [];
    
    // Longueur (jusqu'à 30 points)
    if ($length >= 16) {
        $score += 30;
    } elseif ($length >= 12) {
        $score += 25;
    } elseif ($length >= 8) {
        $score += 15;
    } elseif ($length >= 6) {
        $score += 10;
    } else {
        $feedback[] = 'Le mot de passe est trop court.';
    }
    
    // Complexité (jusqu'à 70 points)
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasNumbers = preg_match('/[0-9]/', $password);
    $hasSymbols = preg_match('/[^a-zA-Z0-9]/', $password);
    
    if ($hasLowercase) $score += 10;
    else $feedback[] = 'Ajoutez des lettres minuscules.';
    
    if ($hasUppercase) $score += 15;
    else $feedback[] = 'Ajoutez des lettres majuscules.';
    
    if ($hasNumbers) $score += 15;
    else $feedback[] = 'Ajoutez des chiffres.';
    
    if ($hasSymbols) $score += 20;
    else $feedback[] = 'Ajoutez des symboles.';
    
    // Pénalité pour les répétitions
    $repeats = 0;
    for ($i = 0; $i < $length - 1; $i++) {
        if ($password[$i] === $password[$i + 1]) {
            $repeats++;
        }
    }
    $score -= $repeats * 2;
    
    // Mots courants et séquences
    $commonPatterns = [
        'password', '123456', 'qwerty', 'abc123', 'letmein', 'welcome',
        'monkey', 'admin', '1234', 'azerty', '111111', 'iloveyou'
    ];
    
    foreach ($commonPatterns as $pattern) {
        if (stripos($password, $pattern) !== false) {
            $score -= 20;
            $feedback[] = 'Évitez les mots ou séquences courantes.';
            break;
        }
    }
    
    // Limiter le score entre 0 et 100
    $score = max(0, min(100, $score));
    
    // Évaluation finale
    $strength = '';
    if ($score >= 90) {
        $strength = 'Excellent';
    } elseif ($score >= 70) {
        $strength = 'Fort';
    } elseif ($score >= 50) {
        $strength = 'Bon';
    } elseif ($score >= 30) {
        $strength = 'Faible';
    } else {
        $strength = 'Très faible';
    }
    
    return [
        'score' => $score,
        'strength' => $strength,
        'feedback' => $feedback
    ];
}