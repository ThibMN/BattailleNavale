const grid = document.getElementById('grid');
const message = document.getElementById('message');
const currentPlayerDisplay = document.getElementById('current-player');
const sunkShipsList = document.getElementById('sunk-ships');
const victoryMessage = document.getElementById('victory-message');
const scoreTable = document.getElementById('score-table');

let currentPlayer = "Joueur 1";
let joueur1Score = 0;
let joueur2Score = 0;

for (let row = 0; row < 10; row++) {
  for (let col = 0; col < 10; col++) {
    const cell = document.createElement('div');
    cell.classList.add('cell');
    cell.dataset.row = row;
    cell.dataset.col = col;
    cell.addEventListener('click', () => handleCellClick(cell));
    grid.appendChild(cell);
  }
}

async function handleCellClick(cell) {
  if (cell.classList.contains('clicked')) return;

  const row = cell.dataset.row;
  const col = cell.dataset.col;

  try {
    console.log(currentPlayer);
    const response = await fetch(`bataille.php?numberRow=${row}&numberCol=${col}&currentPlayer=${currentPlayer}`);
    const result = await response.json();
    console.log(result.nextPlayer);

    cell.classList.add('clicked');
    if (result.hit) {
      cell.classList.add('hit');
      message.textContent = `Touché ! ${result.ship ? result.ship.name : ''}`;
      if (result.ship && result.ship.sunk) {
        const li = document.createElement('li');
        li.textContent = `Tous les ${result.ship.name} sont coulés !`;
        sunkShipsList.appendChild(li);
      }
      if (currentPlayer === "Joueur 1") {
        joueur1Score += 1;
      } else {
        joueur2Score += 1;
      }
      if (result.victory) {
        victoryMessage.style.display = 'block';
        let winner;
    
        if (joueur1Score > joueur2Score) {
            winner = "Joueur 1";
        } else if (joueur2Score > joueur1Score) {
            winner = "Joueur 2";
        } else {
            winner = "ex aequo";
        }
    
        // Envoyer uniquement le gagnant au serveur
        if (winner !== "ex aequo") {
            try {
                // Vérification pour éviter une double requête
                if (!victoryMessage.classList.contains('processed')) {
                    const updateResponse = await fetch('scoreboard.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ winner }),
                    });
                    victoryMessage.classList.add('processed'); // Marquer comme traité
                    const updateResult = await updateResponse.json();
    
                    if (updateResponse.ok) {
                        message.textContent = `Victoire pour ${winner} !`;
                        console.log(updateResult.message || 'Score enregistré avec succès.');
                    } else {
                        console.error(updateResult.error || 'Erreur lors de l\'enregistrement.');
                    }
                }
            } catch (error) {
                console.error("Erreur lors de l'envoi des données au serveur : ", error);
            }
        } else {
            message.textContent = "Match nul !";
        }
    
        // Mettre à jour le scoreboard
        updateScoreboard();
    }
    
    
    } else {
      cell.classList.add('miss');
      message.textContent = "À l'eau !";
      if (result.nextPlayer) {
        currentPlayer = result.nextPlayer;
        currentPlayerDisplay.textContent = `Au tour de : ${currentPlayer}`;
      }
    }
  } catch (error) {
    console.error("Erreur lors de la requête : ", error);
    message.textContent = "Une erreur est survenue.";
  }
}


async function updateScoreboard() {
  try {
    const response = await fetch('scoreboard.php');
    const scores = await response.json();

    if (response.ok) {
      scoreTable.innerHTML = '<tr><th>Joueur</th><th>Victoires</th></tr>';
      scores.forEach(({ player, victories }) => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${player}</td><td>${victories}</td>`;
        scoreTable.appendChild(row);
      });
    } else {
      console.error(scores.error || 'Erreur inconnue lors de la récupération du scoreboard');
    }
  } catch (error) {
    console.error("Erreur lors de la mise à jour du scoreboard :", error);
    message.textContent = "Impossible de charger le scoreboard.";
  }
}
