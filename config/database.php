<?php
// config/database.php
return [
    'host' => 'localhost',
    'database' => 'Gestionnaire_MDP',
    'username' => 'root', // À remplacer par votre nom d'utilisateur
    'password' => '', // À remplacer par votre mot de passe
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];