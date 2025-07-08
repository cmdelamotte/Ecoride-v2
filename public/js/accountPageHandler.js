document.addEventListener('DOMContentLoaded', () => {
    const roleForm = document.getElementById('role-form');
    const driverInfoSection = document.getElementById('driver-info-section');
    const preferencesForm = document.getElementById('driver-preferences-form');
    const vehicleBrandSelect = document.getElementById('vehicle-brand-select');
    const addVehicleBtn = document.getElementById('add-vehicle-btn');
    const vehicleFormContainer = document.getElementById('vehicle-form-container');
    const vehicleForm = document.getElementById('vehicle-form');
    const cancelVehicleFormBtn = document.getElementById('cancel-vehicle-form-btn');
    const vehicleFormTitle = document.getElementById('vehicle-form-title');
    const editingVehicleIdInput = document.getElementById('editing-vehicle-id');
    const vehiclesList = document.getElementById('vehicles-list');

    // Fonction pour peupler le sélecteur de marques
    async function populateBrandSelect() {
        if (!vehicleBrandSelect) {
            console.warn("L'élément select pour les marques n'a pas été trouvé.");
            return;
        }
        vehicleBrandSelect.innerHTML = '<option value="" selected disabled>Chargement des marques...</option>';
        try {
            const response = await fetch('/api/get_brands');
            if (!response.ok) throw new Error(`Erreur HTTP ${response.status}`);
            const data = await response.json();

            if (data.success && data.brands && data.brands.length > 0) {
                vehicleBrandSelect.innerHTML = ''; // Vide le message de chargement
                const defaultOption = document.createElement('option');
                defaultOption.value = "";
                defaultOption.textContent = "Sélectionnez une marque...";
                defaultOption.disabled = true;
                defaultOption.selected = true;
                vehicleBrandSelect.appendChild(defaultOption);

                data.brands.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.id;
                    option.textContent = brand.name;
                    vehicleBrandSelect.appendChild(option);
                });
            } else {
                vehicleBrandSelect.innerHTML = '<option value="" selected disabled>Aucune marque disponible</option>';
                console.warn("Aucune marque récupérée ou API échec.", data.message || '');
            }
        } catch (error) {
            console.error("Erreur fetch des marques:", error);
            vehicleBrandSelect.innerHTML = '<option value="" selected disabled>Erreur chargement marques</option>';
        }
    }

    // Fonction pour afficher/masquer le formulaire d'ajout/modification de véhicule
    function showVehicleForm(isEditing = false, vehicleData = null) {
        if (vehicleFormContainer && vehicleForm && vehicleFormTitle && editingVehicleIdInput && addVehicleBtn && vehicleBrandSelect) {
            vehicleForm.reset();

            if (isEditing && vehicleData) {
                vehicleFormTitle.textContent = "Modifier le Véhicule";
                editingVehicleIdInput.value = vehicleData.id || "";

                // Pré-remplissage du <select> pour la marque
                if (vehicleBrandSelect) vehicleBrandSelect.value = vehicleData.brand_id || "";

                // Pré-remplissage des autres champs (à adapter si les noms de champs changent)
                document.getElementById('vehicle-model').value = vehicleData.model || ""; // Utilise vehicleData.model
                document.getElementById('vehicle-color').value = vehicleData.color || "";
                document.getElementById('vehicle-license-plate').value = vehicleData.registration_number || ""; // Utilise registration_number
                document.getElementById('vehicle-registration-date').value = vehicleData.year || ""; // Utilise year
                document.getElementById('vehicle-seats').value = vehicleData.available_seats || ""; // Utilise available_seats
                document.getElementById('vehicle-electric').checked = vehicleData.is_electric || false;

            } else {
                vehicleFormTitle.textContent = "Ajouter un Véhicule";
                editingVehicleIdInput.value = "";
                if (vehicleBrandSelect) vehicleBrandSelect.value = "";
            }
            vehicleFormContainer.classList.remove('d-none');
            addVehicleBtn.classList.add('d-none');
            if (vehicleBrandSelect) vehicleBrandSelect.focus();
        } else {
            console.warn("Éléments manquants pour initialiser showVehicleForm.");
        }
    }

    // Fonction pour masquer le formulaire véhicule
    function hideVehicleForm() {
        if (vehicleFormContainer && addVehicleBtn) {
            vehicleFormContainer.classList.add('d-none');
            addVehicleBtn.classList.remove('d-none');
        }
    }

    /**
     * Affiche la liste des véhicules de l'utilisateur.
     * @param {Array} vehiclesData - Les données des véhicules.
     */
    function displayUserVehicles(vehiclesData) {
        if (!vehiclesList) {
            console.warn("Élément pour la liste des véhicules non trouvé.");
            return;
        }
        vehiclesList.innerHTML = ''; // Vide la liste existante

        if (!vehiclesData || vehiclesData.length === 0) {
            vehiclesList.innerHTML = '<p class="text-muted">Vous n\'avez pas encore de véhicule enregistré.</p>';
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = `
            <div class="vehicle-item card card-body mb-2">
                <p class="mb-1">
                    <span class="form-label">Marque :</span> <span class="vehicle-brand-display"></span><br>
                    <span class="form-label">Modèle :</span> <span class="vehicle-model-display"></span> - 
                    <span class="form-label">Plaque :</span> <span class="vehicle-plate-display"></span>
                </p>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary edit-vehicle-btn">Modifier</button>
                    <button type="button" class="btn btn-sm btn-outline-danger mt-1 mt-sm-0 ms-sm-1 delete-vehicle-btn">Supprimer</button>
                </div>
            </div>
        `;

        vehiclesData.forEach(vehicle => {
            const clone = template.content.cloneNode(true);
            const vehicleElement = clone.querySelector('.vehicle-item');
            if (vehicleElement) {
                // Stocke les données complètes du véhicule dans les data-attributes pour l'édition future
                vehicleElement.setAttribute('data-vehicle-id', vehicle.id);
                vehicleElement.setAttribute('data-brand-id', vehicle.brand_id);
                vehicleElement.setAttribute('data-model', vehicle.model);
                vehicleElement.setAttribute('data-plate', vehicle.registration_number);
                vehicleElement.setAttribute('data-color', vehicle.color || "");
                vehicleElement.setAttribute('data-year', vehicle.year);
                vehicleElement.setAttribute('data-seats', vehicle.available_seats);
                // Note: is_electric n'est pas dans le modèle Vehicle pour l'instant, à ajouter si nécessaire

                // Affiche les données
                vehicleElement.querySelector('.vehicle-brand-display').textContent = vehicle.brand_name;
                vehicleElement.querySelector('.vehicle-model-display').textContent = vehicle.model;
                vehicleElement.querySelector('.vehicle-plate-display').textContent = vehicle.registration_number;

                vehiclesList.appendChild(clone);
            }
        });
    }

    // Appel de la fonction au chargement de la page si le sélecteur existe
    if (vehicleBrandSelect) {
        populateBrandSelect();
    }

    // Appel de la fonction pour afficher les véhicules au chargement de la page
    // La variable `initialVehiclesData` est injectée par PHP dans la vue.
    if (vehiclesList && typeof initialVehiclesData !== 'undefined') {
        displayUserVehicles(initialVehiclesData);
    }

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

    // Écouteurs d'événements pour le formulaire de véhicule
    if (addVehicleBtn) {
        addVehicleBtn.addEventListener('click', () => {
            showVehicleForm(false); // Mode ajout
        });
    }

    if (cancelVehicleFormBtn) {
        cancelVehicleFormBtn.addEventListener('click', hideVehicleForm);
    }
});