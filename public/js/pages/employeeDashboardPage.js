import { Pagination } from '../components/Pagination.js';
import { loadPendingReviews, handleReviewAction } from './employeeReviewsPage.js';
import { loadPendingReports, handleReportAction } from './employeeReportsPage.js';

// Éléments DOM pour les avis
const reviewListContainer = document.querySelector('.review-list');
const reviewsPaginationContainer = document.getElementById('reviews-pagination');

// Éléments DOM pour les signalements
const reportListContainer = document.querySelector('.reported-rides-list');
const reportsPaginationContainer = document.getElementById('reports-pagination');

// Déclarations locales pour les instances de pagination
let reviewsPagination;
let reportsPagination;


document.addEventListener('DOMContentLoaded', () => {
    // Initialisation des paginations
    reviewsPagination = new Pagination('#reviews-pagination', (page) => loadPendingReviews(page, reviewsPagination));
    reportsPagination = new Pagination('#reports-pagination', (page) => loadPendingReports(page, reportsPagination));

    // Charger les données initiales
    loadPendingReviews(1, reviewsPagination);
    loadPendingReports(1, reportsPagination);

    // Attacher l'écouteur d'événements pour les actions de modération des avis
    if (reviewListContainer) {
        reviewListContainer.addEventListener('click', (event) => handleReviewAction(event, reviewsPagination));
    }
    // Attacher l'écouteur d'événements pour les actions de modération des signalements
    if (reportListContainer) {
        reportListContainer.addEventListener('click', (event) => handleReportAction(event, reportsPagination));
    }
});
