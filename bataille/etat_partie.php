<?php
session_start();
header('Content-Type: application/json');

require 'db.php';

// Vérifier si l'ID de la partie est défini dans la session
if (!isset($_SESSION['partie_id'])) {
    error_log('ID de partie manquant dans la session.');
    echo json_encode(['error' => 'Aucune partie en cours.']);
    exit;
}

$partie_id = $_SESSION['partie_id'];


// Rechercher les informations de la partie
$query = $pdo->prepare("SELECT joueur_actuel, en_cours FROM parties_multijoueurs WHERE id = ?");
$query->execute([$partie_id]);
$partie = $query->fetch();

if (!$partie) {
    echo json_encode(['error' => 'Partie introuvable.']);
    exit;
}

// Si tout va bien, retourner les données de la partie
echo json_encode([
    'joueur_actuel' => $partie['joueur_actuel'],
    'en_cours' => $partie['en_cours']
]);
