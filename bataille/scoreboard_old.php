<?php
header('Content-Type: application/json');
require 'db.php';

$query = $pdo->query('SELECT * FROM scoreboard ORDER BY victories DESC');
$scores = $query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($scores);
?>
