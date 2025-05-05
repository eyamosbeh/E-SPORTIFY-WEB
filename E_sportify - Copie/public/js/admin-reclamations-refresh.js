// Rafraîchit la liste des réclamations en temps réel
function renderReclamationsTable(reclamations) {
    const tbody = document.querySelector('#adminReclamationsTable tbody');
    if (!tbody) return;

    // Créer un tableau pour stocker les lignes existantes
    const existingRows = new Map();
    tbody.querySelectorAll('tr').forEach(tr => {
        const id = tr.getAttribute('data-id');
        if (id) existingRows.set(id, tr);
    });

    // Sauvegarder l'état des traductions actuelles
    const translationStates = new Map();
    tbody.querySelectorAll('.translate-button').forEach(button => {
        const id = button.getAttribute('data-reclamation-id');
        if (id) {
            const container = button.closest('.description-content').querySelector('.description-container');
            const translatedText = container.querySelector('.translated-text');
            if (translatedText && translatedText.style.display === 'block') {
                translationStates.set(id, {
                    isTranslated: true,
                    translatedText: translatedText.textContent
                });
            }
        }
    });

    // Mettre à jour ou ajouter des lignes
    reclamations.forEach(r => {
        const existingRow = existingRows.get(r.id?.toString());
        if (existingRow) {
            // Mettre à jour uniquement le statut si la ligne existe déjà
            const statusCell = existingRow.querySelector('td:nth-child(4)');
            if (statusCell) {
                statusCell.innerHTML = `<span class="badge bg-${r.statut === 'Résolue' ? 'success' : 'warning'}">${r.statut}</span>`;
            }
        } else {
            // Créer une nouvelle ligne si elle n'existe pas
            const tr = document.createElement('tr');
            tr.setAttribute('data-id', r.id);
            tr.innerHTML = `
                <td>${r.dateCreation}</td>
                <td>${r.categorie || ''}</td>
                <td class="description-cell">
                    <div class="description-content">
                        <div class="description-container">
                            <span class="original-text">${r.description ? (r.description.slice(0, 50) + '...') : ''}</span>
                            <span class="translated-text"></span>
                        </div>
                        <button class="btn btn-sm btn-info translate-button" 
                                onclick="translateDescription(this, '${r.description?.replace(/'/g, "\'") || ''}')"
                                data-reclamation-id="${r.id}">
                            <i class="fas fa-language"></i> Traduire
                        </button>
                    </div>
                </td>
                <td><span class="badge bg-${r.statut === 'Résolue' ? 'success' : 'warning'}">${r.statut}</span></td>
                <td>
                    <a href="/admin/reclamation/${r.id}/repondre" class="btn btn-sm btn-success">
                        <i class="fas fa-reply"></i> Répondre
                    </a>
                    <a href="/admin/reclamation/${r.id}/export-pdf" class="btn btn-sm btn-danger ms-1" target="_blank" title="Exporter en PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </td>
            `;
            tbody.appendChild(tr);
        }
        existingRows.delete(r.id?.toString());
    });

    // Supprimer les lignes qui n'existent plus
    existingRows.forEach(row => row.remove());

    // Restaurer l'état des traductions
    translationStates.forEach((state, id) => {
        const button = tbody.querySelector(`button[data-reclamation-id="${id}"]`);
        if (button && state.isTranslated) {
            const container = button.closest('.d-flex').querySelector('.description-container');
            const originalText = container.querySelector('.original-text');
            const translatedText = container.querySelector('.translated-text');

            originalText.style.display = 'none';
            translatedText.textContent = state.translatedText;
            translatedText.style.display = 'block';
            button.style.backgroundColor = '#17a2b8';
            button.querySelector('i').style.color = 'white';
        }
    });
}

let lastUpdate = null;
let currentPage = 1;
let isLoading = false;

function fetchAndUpdateReclamations(page = 1, reset = false) {
    if (isLoading) return;
    isLoading = true;

    const url = new URL('/admin/api/reclamations/list', window.location.origin);
    url.searchParams.append('page', page);
    if (lastUpdate && !reset) {
        url.searchParams.append('last_update', lastUpdate);
    }

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (reset) {
                renderReclamationsTable(data.reclamations);
            } else if (data.reclamations && data.reclamations.length > 0) {
                updateReclamationsTable(data.reclamations);
            }
            lastUpdate = data.timestamp;
            currentPage = data.currentPage;

            // Mettre à jour le bouton "Charger plus" si nécessaire
            const loadMoreBtn = document.getElementById('loadMoreReclamations');
            if (loadMoreBtn) {
                loadMoreBtn.style.display = data.hasMore ? 'block' : 'none';
            }
        })
        .finally(() => {
            isLoading = false;
        });
}

// Fonction pour vérifier si une ligne a changé
function hasRowChanged(oldData, newData) {
    return oldData.statut !== newData.statut;
}

// Fonction pour mettre à jour uniquement le statut d'une ligne
function updateRowStatus(row, reclamation) {
    const statusCell = row.querySelector('td:nth-child(4)');
    if (statusCell) {
        statusCell.innerHTML = `<span class="badge bg-${reclamation.statut === 'Résolue' ? 'success' : 'warning'}">${reclamation.statut}</span>`;
    }
}

function updateReclamationsTable(newReclamations) {
    const tbody = document.querySelector('#adminReclamationsTable tbody');
    if (!tbody) return;

    // Mettre à jour les lignes existantes ou ajouter de nouvelles lignes
    newReclamations.forEach(reclamation => {
        let row = tbody.querySelector(`tr[data-id="${reclamation.id}"]`);
        if (row) {
            // Mettre à jour uniquement le statut si la ligne existe
            updateRowStatus(row, reclamation);
        } else {
            // Ajouter la nouvelle ligne au début du tableau
            const newRow = createNewRow(reclamation);
            tbody.insertBefore(newRow, tbody.firstChild);
        }
    });
}

// Fonction pour créer une nouvelle ligne
function createNewRow(reclamation) {
    const tr = document.createElement('tr');
    tr.setAttribute('data-id', reclamation.id);
    tr.innerHTML = `
        <td>${reclamation.dateCreation}</td>
        <td>${reclamation.categorie || ''}</td>
        <td class="description-cell">
            <div class="description-container">
                <span class="original-text">${reclamation.description ? (reclamation.description.slice(0, 50) + '...') : ''}</span>
                <span class="translated-text"></span>
            </div>
        </td>
        <td><span class="badge bg-${reclamation.statut === 'Résolue' ? 'success' : 'warning'}">${reclamation.statut}</span></td>
        <td>
            <button class="btn btn-sm btn-info translate-button" 
                    onclick="translateDescription(this, '${reclamation.description?.replace(/'/g, "\'") || ''}')"
                    data-reclamation-id="${reclamation.id}">
                <i class="fas fa-language"></i> Traduire
            </button>
            <a href="/admin/reclamation/${reclamation.id}/repondre" class="btn btn-sm btn-success ms-1">
                <i class="fas fa-reply"></i> Répondre
            </a>
            <a href="/admin/reclamation/${reclamation.id}/pdf" class="btn btn-sm btn-danger ms-1" target="_blank" title="Exporter en PDF">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </td>
    `;
    return tr;
}

// Stockage des données actuelles
let currentReclamations = new Map();

// Fonction principale de mise à jour
function renderReclamationsTable(reclamations) {
    const tbody = document.querySelector('#adminReclamationsTable tbody');
    if (!tbody) return;

    const newReclamations = new Map(reclamations.map(r => [r.id?.toString(), r]));

    // Mettre à jour ou ajouter des lignes
    newReclamations.forEach((reclamation, id) => {
        const existingRow = tbody.querySelector(`tr[data-id="${id}"]`);
        const currentReclamation = currentReclamations.get(id);

        if (existingRow) {
            // Mettre à jour uniquement si nécessaire
            if (currentReclamation && hasRowChanged(currentReclamation, reclamation)) {
                updateRowStatus(existingRow, reclamation);
            }
        } else {
            // Ajouter une nouvelle ligne
            tbody.appendChild(createNewRow(reclamation));
        }
    });

    // Supprimer les lignes qui n'existent plus
    tbody.querySelectorAll('tr[data-id]').forEach(row => {
        const id = row.getAttribute('data-id');
        if (!newReclamations.has(id)) {
            row.remove();
        }
    });

    // Mettre à jour le stockage
    currentReclamations = newReclamations;
}

// Rafraîchissement toutes les 10 secondes
setInterval(fetchAndUpdateReclamations, 10000);

// Première charge et initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter le bouton "Charger plus"
    const table = document.getElementById('adminReclamationsTable');
    if (table) {
        const loadMoreContainer = document.createElement('div');
        loadMoreContainer.className = 'text-center mt-3 mb-3';
        loadMoreContainer.innerHTML = `
            <button id="loadMoreReclamations" class="btn btn-primary" style="display: none;">
                Charger plus de réclamations
            </button>
        `;
        table.parentNode.insertBefore(loadMoreContainer, table.nextSibling);

        // Gestionnaire d'événements pour le bouton "Charger plus"
        document.getElementById('loadMoreReclamations').addEventListener('click', () => {
            fetchAndUpdateReclamations(currentPage + 1);
        });
    }

    // Charger les premières réclamations
    fetchAndUpdateReclamations(1, true);

    // Rafraîchissement périodique toutes les 10 secondes
    setInterval(() => fetchAndUpdateReclamations(currentPage), 10000);
});
