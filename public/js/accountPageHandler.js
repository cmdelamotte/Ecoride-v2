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
            return;
        }
        vehicleBrandSelect.innerHTML = '<option value="" selected disabled>Chargement des marques...</option>';
        try {
            const response = await fetch('/api/brands');
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
            }
        } catch (error) {
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
                document.getElementById('vehicle-model').value = vehicleData.model_name || ''; // Utilise vehicleData.model_name
                document.getElementById('vehicle-color').value = vehicleData.color || '';
                document.getElementById('vehicle-license-plate').value = vehicleData.license_plate || ''; // Utilise license_plate
                document.getElementById('vehicle-registration-date').value = vehicleData.registration_date || ''; // Utilise registration_date
                document.getElementById('vehicle-seats').value = vehicleData.passenger_capacity || ''; // Utilise passenger_capacity
                document.getElementById('vehicle-electric').checked = vehicleData.is_electric || false;

            } else {
                vehicleFormTitle.textContent = "Ajouter un Véhicule";
                editingVehicleIdInput.value = "";
                if (vehicleBrandSelect) vehicleBrandSelect.value = "";
            }
            vehicleFormContainer.classList.remove('d-none');
            addVehicleBtn.classList.add('d-none');
            if (vehicleBrandSelect) vehicleBrandSelect.focus();
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
                    <span class="form-label">Modèle :</span> <span class="vehicle-model-display"></span>
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
                vehicleElement.setAttribute('data-model', vehicle.model_name);
                vehicleElement.setAttribute('data-plate', vehicle.license_plate);
                vehicleElement.setAttribute('data-color', vehicle.color || "");
                vehicleElement.setAttribute('data-year', vehicle.registration_date);
                vehicleElement.setAttribute('data-seats', vehicle.passenger_capacity);
                vehicleElement.setAttribute('data-is-electric', vehicle.is_electric);
                // Note: is_electric n'est pas dans le modèle Vehicle pour l'instant, à ajouter si nécessaire

                // Affiche les données
                vehicleElement.querySelector('.vehicle-brand-display').textContent = vehicle.brand_name;
                vehicleElement.querySelector('.vehicle-model-display').textContent = vehicle.model_name;
                vehicleElement.querySelector('.vehicle-plate-display').textContent = vehicle.license_plate;

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

    if (vehicleForm) {
        vehicleForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitButton = vehicleForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Enregistrement...';

            // Je récupère les données directement par l'ID des champs pour plus de robustesse,
            // au lieu de dépendre des attributs 'name' via FormData.
            const vehicleData = {
                brand_id: parseInt(document.getElementById('vehicle-brand-select').value),
                model: document.getElementById('vehicle-model').value,
                color: document.getElementById('vehicle-color').value,
                license_plate: document.getElementById('vehicle-license-plate').value,
                registration_date: document.getElementById('vehicle-registration-date').value,
                passenger_capacity: parseInt(document.getElementById('vehicle-seats').value),
                is_electric: document.getElementById('vehicle-electric').checked, // .checked pour une checkbox
                energy_type: document.getElementById('vehicle-energy-type') ? document.getElementById('vehicle-energy-type').value : ''
            };

            const editingId = editingVehicleIdInput.value;
            const url = editingId ? `/api/vehicles/${editingId}/update` : '/api/vehicles';
            const method = 'POST'; // POST est utilisé pour l'ajout et la mise à jour

            try {
                const response = await fetch(url, {
                    method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(vehicleData),
        credentials: 'same-origin' // ← indispensable pour envoyer la session
    });

    console.log('Fetch Response:', response);

                const data = await response.json(); // Always parse JSON

                if (data.success) {
                    alert(data.message);
                    hideVehicleForm();
                    window.location.reload();
                } else {
                    let errorMessage = data.error || 'Erreur lors de l\'ajout du véhicule.';
                    if (data.errors) {
                        // Prioritize specific field errors
                        errorMessage = ''; // Reset message to build from specific errors
                        for (const key in data.errors) {
                            errorMessage += `${data.errors[key]}\n`; // Add newline for each error
                        }
                        // Remove trailing newline if any
                        errorMessage = errorMessage.trim();
                    }
                    alert(errorMessage || 'Une erreur est survenue.'); // Fallback if no specific errors
                }
            } catch (error) {
                alert('Une erreur de communication est survenue. Veuillez vérifier votre connexion ou réessayer.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Enregistrer Véhicule';
            }
        });
    }

    // Gestion des clics sur les boutons d'action des véhicules (Modifier/Supprimer)
    if (vehiclesList) {
        vehiclesList.addEventListener('click', async (event) => {
            const target = event.target;
            const vehicleItem = target.closest('.vehicle-item');
            if (!vehicleItem) return;

            const vehicleId = vehicleItem.dataset.vehicleId;

            // Clic sur le bouton "Supprimer"
            if (target.classList.contains('delete-vehicle-btn')) {
                if (confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ? Cette action est irréversible.')) {
                    try {
                        const response = await fetch(`/api/vehicles/${vehicleId}/delete`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        const data = await response.json();
                        if (data.success) {
                            alert(data.message);
                            vehicleItem.remove(); // Supprime l'élément de la liste
                        } else {
                            alert('Erreur : ' + data.error);
                        }
                    } catch (error) {
                        alert('Une erreur de communication est survenue.');
                    }
                }
            }

            // Clic sur le bouton "Modifier"
            if (target.classList.contains('edit-vehicle-btn')) {
                const vehicleData = {
                    id: vehicleId,
                    brand_id: vehicleItem.dataset.brandId,
                    model_name: vehicleItem.dataset.model,
                    color: vehicleItem.dataset.color,
                    license_plate: vehicleItem.dataset.plate,
                    registration_date: vehicleItem.dataset.year,
                    passenger_capacity: vehicleItem.dataset.seats,
                    is_electric: vehicleItem.dataset.isElectric === 'true'
                };
                showVehicleForm(true, vehicleData);
            }
        });
    }
});