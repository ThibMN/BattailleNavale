const grid = document.getElementById('grid');
const message = document.getElementById('message');
const sunkShipsList = document.getElementById('sunk-ships');
const victoryMessage = document.getElementById('victory-message');
const scoreTable = document.getElementById('score-table');

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
  const row = cell.dataset.row;
  const col = cell.dataset.col;

  try {
    const response = await fetch(`bataille.php?numberRow=${row}&numberCol=${col}`);
    const result = await response.json();

    cell.classList.add('clicked');
    if (result.hit) {
      cell.classList.add('hit');
      message.textContent = `Touché ! ${result.ship ? result.ship.name : ''}`;
      if (result.ship && result.ship.sunk) {
        const li = document.createElement('li');
        li.textContent = `Tous les ${result.ship.name} sont coulés !`;
        sunkShipsList.appendChild(li);
      }
    } else {
      cell.classList.add('miss');
      message.textContent = "À l'eau !";
    }

    if (result.victory) {
      victoryMessage.style.display = 'block';
      updateScoreboard();
    }
  } catch (error) {
    console.error("Erreur lors de la requête : ", error);
    message.textContent = "Une erreur est survenue.";
  }
}

async function updateScoreboard() {
  const response = await fetch('scoreboard.php');
  const scores = await response.json();

  scoreTable.innerHTML = '';
  scores.forEach(({ player, victories }) => {
    const row = document.createElement('tr');
    row.innerHTML = `<td>${player}</td><td>${victories}</td>`;
    scoreTable.appendChild(row);
  });
}