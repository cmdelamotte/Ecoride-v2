import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';
import { Pagination } from '../components/Pagination.js';

// Éléments DOM pour les signalements
const reportListContainer = document.querySelector('.reported-rides-list');
const noPendingReportsMessage = document.getElementById('no-reported-rides');
const pendingReportCardTemplate = document.getElementById('reported-ride-card-template');
const reportsPaginationContainer = document.getElementById('reports-pagination');

// Ces variables ne sont plus exportées, elles sont gérées par l'orchestrateur (employeeDashboardPage.js)
let currentReportsPage = 1;
const REPORTS_PER_PAGE = 5;

/**
 * Crée et retourne un élément de carte de signalement en attente.
 * @param {object} reportData Les données du signalement.
 * @returns {HTMLElement} L'élément HTML de la carte.
 */
export const createPendingReportCard = (reportData) => {
    const card = pendingReportCardTemplate.content.cloneNode(true);

    const cardElement = card.querySelector('.card'); // Récupérer l'élément racine de la carte
    cardElement.dataset.reportId = reportData.id;

    card.querySelector('.report-ride-id').textContent = reportData.ride_id;
    card.querySelector('.report-submission-date').textContent = `Date signalement: ${new Date(reportData.created_at).toLocaleDateString('fr-FR')}`;
    card.querySelector('.report-ride-departure').textContent = reportData.departure_city;
    card.querySelector('.report-ride-arrival').textContent = reportData.arrival_city;
    card.querySelector('.report-ride-date').textContent = new Date(reportData.departure_time).toLocaleDateString('fr-FR');
    card.querySelector('.report-passenger-name').textContent = reportData.reporter_username;
    card.querySelector('.report-passenger-email').textContent = reportData.reporter_email;
    card.querySelector('.report-passenger-email').href = `mailto:${reportData.reporter_email}`;
    card.querySelector('.report-driver-name').textContent = reportData.reported_driver_username;
    card.querySelector('.report-driver-email').textContent = reportData.reported_driver_email;
    card.querySelector('.report-driver-email').href = `mailto:${reportData.reported_driver_email}`;
    card.querySelector('.report-reason-content').textContent = reportData.reason || 'Aucun motif spécifié.';

    // Attacher les IDs aux boutons pour les actions
    const creditButton = card.querySelector('.action-credit-driver');
    const contactButton = card.querySelector('.action-contact-driver');
    if (creditButton) creditButton.dataset.reportId = reportData.id;
    if (contactButton) contactButton.dataset.reportId = reportData.id;

    // Logique pour gérer l'état des boutons en fonction du statut du signalement
    if (reportData.report_status === 'under_investigation') {
        if (contactButton) {
            contactButton.textContent = 'Chauffeur contacté';
            contactButton.disabled = true; // Désactiver le bouton après contact
        }
        if (creditButton) {
            creditButton.disabled = true; // Désactiver le bouton de crédit si déjà en investigation
        }
        // Mettre à jour le texte du footer si nécessaire
        const cardFooter = card.querySelector('.card-footer');
        if (cardFooter) {
            cardFooter.textContent = 'Statut : En investigation';
            cardFooter.classList.remove('text-danger');
            cardFooter.classList.add('text-warning');
        }
    } else if (reportData.report_status === 'closed') {
        if (contactButton) {
            contactButton.textContent = 'Signalement clos';
            contactButton.disabled = true;
        }
        if (creditButton) {
            creditButton.textContent = 'Chauffeur crédité';
            creditButton.disabled = true;
        }
        const cardFooter = card.querySelector('.card-footer');
        if (cardFooter) {
            cardFooter.textContent = 'Statut : Clos';
            cardFooter.classList.remove('text-danger', 'text-warning');
            cardFooter.classList.add('text-success');
        }
    }

    return card;
};

/**
 * Charge et affiche les signalements en attente de modération.
 * @param {number} page Le numéro de page à charger.
 * @param {object} reportsPaginationInstance L'instance de Pagination pour les signalements.
 */
export const loadPendingReports = async (page = 1, reportsPaginationInstance) => {
    currentReportsPage = page;
    clearChildren(reportListContainer);
    noPendingReportsMessage.classList.add('d-none');
    reportsPaginationContainer.classList.add('d-none'); // Cacher la pagination pendant le chargement
    reportListContainer.appendChild(createElement('p', ['text-center', 'text-muted'], {}, 'Chargement des signalements...'));

    try {
        const response = await apiClient.getPendingReports(page, REPORTS_PER_PAGE);
        clearChildren(reportListContainer);

        if (response.success && response.reports.length > 0) {
            response.reports.forEach(report => {
                reportListContainer.appendChild(createPendingReportCard(report));
            });
            reportsPaginationContainer.classList.remove('d-none');
            reportsPaginationInstance.render(response.pagination.current_page, response.pagination.total_pages);
        } else {
            noPendingReportsMessage.classList.remove('d-none');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des signalements en attente:', error);
        displayFlashMessage('Erreur lors du chargement des signalements en attente.', 'danger');
        clearChildren(reportListContainer);
        reportListContainer.appendChild(createElement('p', ['text-center', 'text-danger'], {}, 'Impossible de charger les signalements.'));
    }
};

/**
 * Gère les actions de modération des signalements (créditer, contacter).
 * @param {Event} event L'événement de clic.
 * @param {object} reportsPaginationInstance L'instance de Pagination pour les signalements.
 */
export const handleReportAction = async (event, reportsPaginationInstance) => {
    const target = event.target;
    const reportId = target.dataset.reportId;
    if (!reportId) return;

    let apiCallPromise = null;
    let successMessage = '';
    let errorMessage = '';
    let updateCardOnly = false; // Indicateur pour savoir si on met à jour la carte ou on recharge la liste

    if (target.classList.contains('action-credit-driver')) {
        apiCallPromise = apiClient.creditDriver(reportId);
        successMessage = 'Chauffeur crédité avec succès.';
        errorMessage = "Erreur lors du crédit du chauffeur.";
        // Après crédit, le report doit disparaître de la liste "new"
    } else if (target.classList.contains('action-contact-driver')) {
        apiCallPromise = apiClient.contactDriver(reportId);
        successMessage = 'Chauffeur contacté avec succès.';
        errorMessage = "Erreur lors de la prise de contact avec le chauffeur.";
        updateCardOnly = true; // On met à jour la carte au lieu de recharger la liste
    }

    if (!apiCallPromise) return;

    target.disabled = true;
    target.textContent = '...';

    try {
        const response = await apiCallPromise;
        if (response.success) {
            displayFlashMessage(successMessage, 'success');
            if (updateCardOnly) {
                // Trouver la carte parente et mettre à jour son état
                const cardElement = target.closest('.card');
                if (cardElement) {
                    // Mettre à jour le statut dans les données de la carte (si on les avait)
                    // Pour l'instant, on va juste modifier les boutons et le footer
                    const creditButton = cardElement.querySelector('.action-credit-driver');
                    const contactButton = cardElement.querySelector('.action-contact-driver');
                    const cardFooter = cardElement.querySelector('.card-footer');

                    if (contactButton) {
                        contactButton.textContent = 'Chauffeur contacté';
                        contactButton.disabled = true;
                    }
                    if (creditButton) {
                        creditButton.disabled = true;
                    }
                    if (cardFooter) {
                        cardFooter.textContent = 'Statut : En investigation';
                        cardFooter.classList.remove('text-danger');
                        cardFooter.classList.add('text-warning');
                    }
                }
            } else {
                // Pour les actions qui retirent le report de la liste "new"
                loadPendingReports(currentReportsPage, reportsPaginationInstance);
            }
        } else {
            displayFlashMessage(response.message || errorMessage, 'danger');
            // Si le signalement a déjà été traité, recharger la liste pour le retirer
            if (response.message && response.message.includes("déjà été traité")) {
                loadPendingReports(currentReportsPage, reportsPaginationInstance);
            }
        }
    } catch (error) {
        console.error('Erreur API:', error);
        displayFlashMessage('Erreur de communication avec le serveur.', 'danger');
    } finally {
        target.disabled = false;
        if (!updateCardOnly) { // Si on n'a pas mis à jour la carte, restaurer le texte du bouton
            if (target.classList.contains('action-credit-driver')) {
                target.textContent = "Créditer le chauffeur";
            } else if (target.classList.contains('action-contact-driver')) {
                target.textContent = "Contacter le chauffeur";
            }
        }
    }
};