// Script de notification admin pour dashboard
let lastCount = 0;
let lastCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');

function fetchNewReclamations() {
    fetch('/admin/api/reclamations/count?since=' + encodeURIComponent(lastCheck))
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                showAdminNotification(data.count);
                updateBadge(data.count);
            } else {
                updateBadge(0);
            }
            // Met à jour la date de dernière vérification
            lastCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
        })
        .catch(console.error);
}

function showAdminNotification(count) {
    if (window.bootstrap && window.bootstrap.Toast) {
        let toast = document.getElementById('adminNewReclamationToast');
        if (toast) {
            document.getElementById('adminNewReclamationCount').textContent = count;
            new bootstrap.Toast(toast).show();
        }
    } else {
        alert('Nouvelle(s) réclamation(s) reçue(s) : ' + count);
    }
}

function updateBadge(count) {
    let badge = document.getElementById('adminReclamationsBadge');
    if (badge) {
        badge.textContent = count > 0 ? count : '';
        badge.style.display = count > 0 ? 'inline-block' : 'none';
        badge.title = count > 0 ? `${count} nouvelle(s) réclamation(s) depuis votre dernière visite` : '';
    }
}

// Clique sur le badge : fait défiler vers la table des réclamations
window.addEventListener('DOMContentLoaded', function() {
    let badge = document.getElementById('adminReclamationsBadge');
    if (badge) {
        badge.addEventListener('click', function() {
            const table = document.querySelector('.table-responsive');
            if (table) {
                table.scrollIntoView({behavior: 'smooth'});
            }
        });
    }
});

setInterval(fetchNewReclamations, 15000); // Vérifie toutes les 15s

document.addEventListener('DOMContentLoaded', function() {
    updateBadge(0);
});
