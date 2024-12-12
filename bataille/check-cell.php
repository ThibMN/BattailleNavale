<?php
header('Content-Type: application/json');
require 'db.php'; // Connexion à la base de données

$row = $_GET['row'] ?? null;
$col = $_GET['col'] ?? null;

if ($row === null || $col === null) {
    echo json_encode(['error' => 'Coordonnées manquantes']);
    exit;
}

// Vérifier si un bateau est à cette position
$query = $pdo->prepare('SELECT b.nom, b.taille, pb.bateau_id FROM positions_bateaux pb 
                        JOIN bateaux b ON pb.bateau_id = b.id 
                        WHERE pb.row = ? AND pb.col = ?');
$query->execute([$row, $col]);
$ship = $query->fetch();

if ($ship) {
    // Marquer la case comme touchée
    $pdo->prepare('DELETE FROM positions_bateaux WHERE row = ? AND col = ?')->execute([$row, $col]);

    // Vérifier si le bateau est coulé
    $remainingParts = $pdo->prepare('SELECT COUNT(*) FROM positions_bateaux WHERE bateau_id = ?');
    $remainingParts->execute([$ship['bateau_id']]);
    $sunk = $remainingParts->fetchColumn() == 0;

    // Vérifier la victoire
    $remainingShips = $pdo->query('SELECT COUNT(*) FROM positions_bateaux')->fetchColumn();
    $victory = $remainingShips == 0;
    system.out.println($ship['nom']);
    system.out.println($ship['taille']);
    system.out.println($sunk);

    echo json_encode([
        'hit' => true,
        'ship' => [
            'name' => $ship['nom'],
            'size' => $ship['taille'],
            'sunk' => $sunk
        ],
        'victory' => $victory
    ]);

    // Mettre à jour le tableau des scores en cas de victoire
    if ($victory) {
        $pdo->query("UPDATE scoreboard SET victories = victories + 1 WHERE player = 'Joueur1'");
    }
} else {
    echo json_encode(['hit' => false]);
}
?>
