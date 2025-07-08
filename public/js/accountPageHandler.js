document.addEventListener('DOMContentLoaded', () => {
    const roleForm = document.getElementById('role-form');
    const driverInfoSection = document.getElementById('driver-info-section');
    const preferencesForm = document.getElementById('driver-preferences-form');

    if (roleForm) {
        roleForm.addEventListener('submit', (event) => {
            event.preventDefault(); // Empêche le rechargement de la page

            const formData = new FormData(roleForm);
            const selectedRole = formData.get('user_role_form');
            const submitButton = roleForm.querySelector('button[type="submit"]');

            // Désactiver le bouton pour éviter les clics multiples
            submitButton.disabled = true;
            submitButton.textContent = 'Enregistrement...';

            fetch('/account/update-role', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ role: selectedRole })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);

                    // Mettre à jour l'affichage de la section chauffeur
                    if (driverInfoSection) {
                        if (data.new_functional_role === 'driver' || data.new_functional_role === 'passenger_driver') {
                            driverInfoSection.classList.remove('d-none');
                        } else {
                            driverInfoSection.classList.add('d-none');
                        }
                    }
                } else {
                    alert('Erreur : ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur lors de la mise à jour du rôle:', error);
                alert('Une erreur de communication est survenue.');
            })
            .finally(() => {
                // Réactiver le bouton à la fin de la requête
                submitButton.disabled = false;
                submitButton.textContent = 'Enregistrer mon rôle';
            });
        });
    }

    if (preferencesForm) {
        preferencesForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const prefSmoker = document.getElementById('pref-smoker').checked;
            const prefAnimals = document.getElementById('pref-animals').checked;
            const prefMusic = document.getElementById('pref-music').checked;
            const prefCustom = document.getElementById('pref-custom').value.trim();

            const submitButton = preferencesForm.querySelector('button[type="submit"]');

            submitButton.disabled = true;
            submitButton.textContent = 'Enregistrement...';

            fetch('/account/update-preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    pref_smoker: prefSmoker,
                    pref_animals: prefAnimals,
                    pref_music: prefMusic,
                    pref_custom: prefCustom
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                } else {
                    alert('Erreur : ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur lors de la mise à jour des préférences:', error);
                alert('Une erreur de communication est survenue.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Enregistrer les Préférences';
            });
        });
    }
});
