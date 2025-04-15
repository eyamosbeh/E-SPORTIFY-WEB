const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item=> {
	const li = item.parentElement;

	item.addEventListener('click', function () {
		allSideMenu.forEach(i=> {
			i.parentElement.classList.remove('active');
		})
		li.classList.add('active');
	})
});




// TOGGLE SIDEBAR
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
	sidebar.classList.toggle('hide');
})







const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

searchButton.addEventListener('click', function (e) {
	if(window.innerWidth < 576) {
		e.preventDefault();
		searchForm.classList.toggle('show');
		if(searchForm.classList.contains('show')) {
			searchButtonIcon.classList.replace('bx-search', 'bx-x');
		} else {
			searchButtonIcon.classList.replace('bx-x', 'bx-search');
		}
	}
})





if(window.innerWidth < 768) {
	sidebar.classList.add('hide');
} else if(window.innerWidth > 576) {
	searchButtonIcon.classList.replace('bx-x', 'bx-search');
	searchForm.classList.remove('show');
}


window.addEventListener('resize', function () {
	if(this.innerWidth > 576) {
		searchButtonIcon.classList.replace('bx-x', 'bx-search');
		searchForm.classList.remove('show');
	}
})



const switchMode = document.getElementById('switch-mode');

switchMode.addEventListener('change', function () {
	if(this.checked) {
		document.body.classList.add('dark');
	} else {
		document.body.classList.remove('dark');
	}
})

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
	// Gestion du blocage des utilisateurs
	document.querySelectorAll('.block-user').forEach(button => {
		button.addEventListener('click', function() {
			const userId = this.getAttribute('data-id');
			const userName = this.getAttribute('data-user');
			const isBlocked = this.getAttribute('data-blocked') === 'true';
			
			let confirmMessage = isBlocked ? 
				`Êtes-vous sûr de vouloir débloquer l'utilisateur ${userName} ?` :
				`Êtes-vous sûr de vouloir bloquer l'utilisateur ${userName} ?`;
			
			if (!confirm(confirmMessage)) {
				return; // Si l'utilisateur annule, on ne fait rien
			}
			
			fetch(`/dashboard/admin/toggle-block/${userId}`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-Requested-With': 'XMLHttpRequest'
				},
				credentials: 'same-origin'
			})
			.then(response => {
				console.log('Status de la réponse:', response.status);
				return response.json();
			})
			.then(data => {
				console.log('Données reçues:', data);
				if (data.success) {
					// Mettre à jour le bouton
					if (data.isBlocked) {
						this.textContent = 'Débloquer';
						this.style.backgroundColor = '#f44336';
						this.setAttribute('data-blocked', 'true');
					} else {
						this.textContent = 'Bloquer';
						this.style.backgroundColor = '#4CAF50';
						this.setAttribute('data-blocked', 'false');
					}
					// Afficher un message de succès
					alert(data.message);
				} else {
					// Afficher un message d'erreur
					alert(data.message);
				}
			})
			.catch(error => {
				console.error('Erreur détaillée:', error);
				alert('Une erreur est survenue');
			});
		});
	});
});