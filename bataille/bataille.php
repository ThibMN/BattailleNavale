<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require 'db.php'; // Connexion à la base de données

// Vérifier si une session est déjà active
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close(); // Fermer la session avant de redémarrer
}

require 'session_start.php'; // Démarre ou redémarre la session

// Vérifier si une session existe déjà pour cette partie
if (!isset($_SESSION['partie_id'])) {
    // Si aucune partie n'est en cours, créer une nouvelle partie
    $nouvellePartieId = creerNouvellePartie($pdo);
    $_SESSION['partie_id'] = $nouvellePartieId; // Stocker l'ID de la partie dans la session
    echo json_encode(['message' => "Nouvelle partie créée avec l'ID : $nouvellePartieId"]);
} else {
    // Si une partie est déjà en cours, récupérer l'ID de la partie
    $partie_id = $_SESSION['partie_id'];
}

// Récupérer les paramètres
$numberRow = $_GET['numberRow'] ?? null;
$numberCol = $_GET['numberCol'] ?? null;
$partie_id = 1; // ID de la partie en cours (depuis la session)

if ($numberRow === null || $numberCol === null || $partie_id === null) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

// Vérifier si un bateau est à cette position pour la partie donnée
$query = $pdo->prepare("
    SELECT b.nom, b.taille, pp.bateau_id 
    FROM positions_parties pp 
    JOIN bateaux b ON pp.bateau_id = b.id 
    WHERE pp.numberRow = ? AND pp.numberCol = ? AND pp.partie_id = ?");
$query->execute([$numberRow, $numberCol, $partie_id]);
$ship = $query->fetch();

if ($ship) {
    // Marquer la case comme touchée pour la partie en cours
    $pdo->prepare("
        DELETE FROM positions_parties 
        WHERE numberRow = ? AND numberCol = ? AND partie_id = ?
    ")->execute([$numberRow, $numberCol, $partie_id]);

    // Vérifier si le bateau est coulé
    $remainingParts = $pdo->prepare("
        SELECT COUNT(*) FROM positions_parties WHERE bateau_id = ? AND partie_id = ?");
    $remainingParts->execute([$ship['bateau_id'], $partie_id]);
    $sunk = $remainingParts->fetchColumn() == 0;

    // Vérifier la victoire
    $remainingShips = $pdo->prepare("
        SELECT COUNT(*) FROM positions_parties WHERE partie_id = ?");
    $remainingShips->execute([$partie_id]);
    $victory = $remainingShips->fetchColumn() == 0;

    // Mise à jour des scores en cas de victoire
    if ($victory) {
        // Marquer la partie comme terminée
        $pdo->prepare("UPDATE parties SET en_cours = 0 WHERE id = ?")->execute([$partie_id]);
        
        // Mise à jour du score du joueur
        $pdo->query("UPDATE scoreboard SET victories = victories + 1 WHERE player = 'Joueur1'");

        // Réinsérer les bateaux dans positions_parties
        $queryCopy = $pdo->prepare("
            INSERT INTO positions_parties (partie_id, numberRow, numberCol, bateau_id)
            SELECT ?, numberRow, numberCol, bateau_id FROM positions_bateaux
        ");
        $queryCopy->execute([$partie_id]); // Exécute l'insertion dans positions_parties
    }

    // Renvoyer les résultats
    echo json_encode([
        'hit' => true,
        'ship' => [
            'name' => $ship['nom'],
            'size' => $ship['taille'],
            'sunk' => $sunk
        ],
        'victory' => $victory
    ]);
} else {
    echo json_encode(['hit' => false]);
}

function insererPositionsBateauxDansPartie($pdo, $partie_id) {
    // Insérer les positions des bateaux dans positions_parties
    $stmt = $pdo->prepare("
        INSERT INTO positions_parties (partie_id, numberRow, numberCol, bateau_id)
        SELECT ?, numberRow, numberCol, bateau_id 
        FROM positions_bateaux
    ");
    $stmt->execute([$partie_id]);
}

// Fonction pour créer une nouvelle partie
function creerNouvellePartie($pdo) {
    // Insérer une nouvelle partie dans la table parties
    $stmt = $pdo->prepare("INSERT INTO parties (joueur) VALUES (?)");
    $stmt->execute(['Joueur1']);
    $partie_id = $pdo->lastInsertId(); // Récupérer l'ID de la nouvelle partie

    // Copier les positions des bateaux dans positions_parties
    insererPositionsBateauxDansPartie($pdo, $partie_id);

    // Enregistrer l'ID de la partie dans la session
    $_SESSION['partie_id'] = $partie_id;

    return $partie_id;
}
?>