<?php     
// Stocker les identifiants hachés (exemple avec hash SHA256)
$DB_HOST = hash('sha256', 'localhost');
$DB_USER = hash('sha256', 'razanateraa');
$DB_PASS = hash('sha256', 'Yiechaizo8ie');

// Récupérer les valeurs originales (pour l'exemple, mais en pratique il faut utiliser des variables d'environnement ou un fichier sécurisé)
$DB_HOST = 'localhost';
$DB_USER = 'razanateraa';
$DB_PASS = 'Yiechaizo8ie';
 $pdo = new PDO('mysql:host=' . $DB_HOST . ';dbname=razanateraa_cinema;charset=utf8', $DB_USER, $DB_PASS);

?>