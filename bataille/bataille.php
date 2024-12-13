<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require 'db.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require 'session_start.php';

if (!isset($_SESSION['partie_id'])) {
    $nouvellePartieId = creerNouvellePartie($pdo);
    $_SESSION['partie_id'] = $nouvellePartieId;
    echo json_encode(['message' => "Nouvelle partie créée avec l'ID : $nouvellePartieId"]);
} else {
    $partie_id = $_SESSION['partie_id'];
}

$numberRow = isset($_GET['numberRow']) ? intval($_GET['numberRow']) : null;
$numberCol = isset($_GET['numberCol']) ? intval($_GET['numberCol']) : null;
$joueur_actif = isset($_GET['currentPlayer']) ? $_GET['currentPlayer'] : null;

$partie_id = 1;

if ($numberRow === null || $numberCol === null || $partie_id === null) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$query = $pdo->prepare("
    SELECT b.nom, b.taille, pp.bateau_id 
    FROM positions_parties pp 
    JOIN bateaux b ON pp.bateau_id = b.id 
    WHERE pp.numberRow = ? AND pp.numberCol = ? AND pp.partie_id = ?");
$query->execute([$numberRow, $numberCol, $partie_id]);
$ship = $query->fetch();

if ($ship) {
    $pdo->prepare("
        DELETE FROM positions_parties 
        WHERE numberRow = ? AND numberCol = ? AND partie_id = ?
    ")->execute([$numberRow, $numberCol, $partie_id]);

    $remainingParts = $pdo->prepare("
        SELECT COUNT(*) FROM positions_parties WHERE bateau_id = ? AND partie_id = ?");
    $remainingParts->execute([$ship['bateau_id'], $partie_id]);
    $sunk = $remainingParts->fetchColumn() == 0;

    $remainingShips = $pdo->prepare("
        SELECT COUNT(*) FROM positions_parties WHERE partie_id = ?");
    $remainingShips->execute([$partie_id]);
    $victory = $remainingShips->fetchColumn() == 0;

    if ($victory) {
        $queryCopy = $pdo->prepare("
            INSERT INTO positions_parties (partie_id, numberRow, numberCol, bateau_id)
            SELECT ?, numberRow, numberCol, bateau_id FROM positions_bateaux
        ");
        $queryCopy->execute([$partie_id]);
    }

    echo json_encode([
        'hit' => true,
        'ship' => [
            'name' => $ship['nom'],
            'size' => $ship['taille'],
            'sunk' => $sunk
        ],
        'victory' => $victory,
        'nextPlayer' => $joueur_actif
    ]);
} else {
    $joueur_actif = $joueur_actif === 'Joueur 1' ? 'Joueur 2' : 'Joueur 1';
    echo json_encode(['hit' => false, 'nextPlayer' => $joueur_actif]);
}

function insererPositionsBateauxDansPartie($pdo, $partie_id) {
    $stmt = $pdo->prepare("
        INSERT INTO positions_parties (partie_id, numberRow, numberCol, bateau_id)
        SELECT ?, numberRow, numberCol, bateau_id 
        FROM positions_bateaux
    ");
    $stmt->execute([$partie_id]);
}

function creerNouvellePartie($pdo) {
    $stmt = $pdo->prepare("INSERT INTO parties (joueur) VALUES (?)");
    $stmt->execute(['Joueur1']);
    $partie_id = $pdo->lastInsertId();

    insererPositionsBateauxDansPartie($pdo, $partie_id);

    $_SESSION['partie_id'] = $partie_id;

    return $partie_id;
}
?>