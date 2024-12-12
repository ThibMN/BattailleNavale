<?php
header('Content-Type: application/json');
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['winner'])) {
        echo json_encode(['error' => 'Aucun gagnant spécifié']);
        http_response_code(400);
        exit;
    }

    $winner = $data['winner'];

    if ($winner !== 'ex aequo') {
        // Vérifier si le joueur existe
        $checkQuery = $pdo->prepare("SELECT COUNT(*) FROM scoreboard WHERE player = ?");
        $checkQuery->execute([$winner]);
        $exists = $checkQuery->fetchColumn() > 0;

        if (!$exists) {
            // Ajouter le joueur s'il n'existe pas
            $insertQuery = $pdo->prepare("INSERT INTO scoreboard (player, victories) VALUES (?, 0)");
            $insertQuery->execute([$winner]);
        }

        // Mettre à jour les victoires
        $updateQuery = $pdo->prepare("UPDATE scoreboard SET victories = victories + 1 WHERE player = ?");
        $updateQuery->execute([$winner]);

        echo json_encode(['message' => "Victoire ajoutée pour $winner"]);
    } else {
        echo json_encode(['message' => "Match nul, aucun point ajouté"]);
    }
    exit;
}

// Si la méthode est GET, retourne le classement
$query = $pdo->query('SELECT * FROM scoreboard ORDER BY victories DESC');
$scores = $query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($scores);
?>
