class BattleshipGame {
  constructor(gridId, messageId, currentPlayerId, sunkShipsId, victoryMessageId, scoreTableId) {
    this.grid = document.getElementById(gridId);
    this.message = document.getElementById(messageId);
    this.currentPlayerDisplay = document.getElementById(currentPlayerId);
    this.sunkShipsList = document.getElementById(sunkShipsId);
    this.victoryMessage = document.getElementById(victoryMessageId);
    this.scoreTable = document.getElementById(scoreTableId);

    this.currentPlayer = "Joueur 1";
    this.joueur1Score = 0;
    this.joueur2Score = 0;
    this.finish = false;

    this.initGrid();
  }

  initGrid() {
    for (let row = 0; row < 10; row++) {
      for (let col = 0; col < 10; col++) {
        const cell = document.createElement('div');
        cell.classList.add('cell');
        cell.dataset.row = row;
        cell.dataset.col = col;
        cell.addEventListener('click', () => this.handleCellClick(cell));
        this.grid.appendChild(cell);
      }
    }
    
    this.updateScoreboard();
  }

  async handleCellClick(cell) {
    if (cell.classList.contains('clicked')) return;
    if (this.finish) return;

    const row = cell.dataset.row;
    const col = cell.dataset.col;

    try {
      const response = await fetch(`bataille.php?numberRow=${row}&numberCol=${col}&currentPlayer=${this.currentPlayer}`);
      const result = await response.json();

      cell.classList.add('clicked');
      if (result.hit) {
        cell.classList.add('hit');
        this.message.textContent = `Touché ! ${result.ship ? result.ship.name : ''}`;

        if (result.ship && result.ship.sunk) {
          const li = document.createElement('li');
          li.textContent = `Tous les ${result.ship.name} sont coulés !`;
          this.sunkShipsList.appendChild(li);
        }

        if (this.currentPlayer === "Joueur 1") {
          this.joueur1Score++;
        } else {
          this.joueur2Score++;
        }

        if (result.victory) {
          this.displayVictory();
        }
      } else {
        cell.classList.add('miss');
        this.message.textContent = "À l'eau !";
        if (result.nextPlayer) {
          this.currentPlayer = result.nextPlayer;
          this.currentPlayerDisplay.textContent = `Au tour de : ${this.currentPlayer}`;
        }
      }
    } catch (error) {
      console.error("Erreur lors de la requête : ", error);
      this.message.textContent = "Une erreur est survenue.";
    }
  }

  async displayVictory() {
    this.victoryMessage.style.display = 'block';
    this.currentPlayerDisplay.style.display = 'none';
    let winner;

    if (this.joueur1Score > this.joueur2Score) {
      winner = "Joueur 1";
    } else if (this.joueur2Score > this.joueur1Score) {
      winner = "Joueur 2";
    } else {
      winner = "égalité";
    }

    if (winner !== "égalité") {
      try {
        if (!this.victoryMessage.classList.contains('processed')) {
          const response = await fetch('scoreboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ winner }),
          });
          this.victoryMessage.classList.add('processed');

          const result = await response.json();
          if (response.ok) {
            this.message.textContent = `Victoire pour ${winner} !`;
            console.log(result.message || 'Score enregistré avec succès.');
          } else {
            console.error(result.error || 'Erreur lors de l\'enregistrement.');
          }
        }
      } catch (error) {
        console.error("Erreur lors de l'envoi des données au serveur : ", error);
      }
    } else {
      this.message.textContent = "Match nul !";
    }
    this.finish = true;
    this.updateScoreboard();
  }

  async updateScoreboard() {
    try {
      const response = await fetch('scoreboard.php');
      const scores = await response.json();

      if (response.ok) {
        this.scoreTable.innerHTML = '<tr><th>Joueur</th><th>Victoires</th></tr>';
        scores.forEach(({ player, victories }) => {
          const row = document.createElement('tr');
          row.innerHTML = `<td>${player}</td><td>${victories}</td>`;
          this.scoreTable.appendChild(row);
        });
      } else {
        console.error(scores.error || 'Erreur inconnue lors de la récupération du scoreboard');
      }
    } catch (error) {
      console.error("Erreur lors de la mise à jour du scoreboard :", error);
      this.message.textContent = "Impossible de charger le scoreboard.";
    }
  }
}

const game = new BattleshipGame('grid', 'message', 'current-player', 'sunk-ships', 'victory-message', 'score-table');
