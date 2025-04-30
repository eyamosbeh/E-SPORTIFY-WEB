// Configuration du graphique des réclamations
function initializeChart(stats) {
    const ctx = document.getElementById('reclamationsChart').getContext('2d');
    const loadingOverlay = document.getElementById('chartLoading');

    // Données pour le graphique
    const chartData = {
        labels: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'],
        datasets: [
            {
                label: 'Total des réclamations',
                data: Array(7).fill(stats.total),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Réclamations en attente',
                data: Array(7).fill(stats.enAttente),
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Réclamations résolues',
                data: Array(7).fill(stats.resolues),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true,
                tension: 0.4
            }
        ]
    };

    // Configuration du graphique
    const config = {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    },
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: false
                    }
                }
            }
        }
    };

    // Créer le graphique
    const myChart = new Chart(ctx, config);
    
    // Cacher le loading une fois le graphique chargé
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }

    return myChart;
} 