<?php
$host = 'localhost';      
$dbname = 'bataille_navale';
$username = 'Player1';      
$password = '1234';          
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {

    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
