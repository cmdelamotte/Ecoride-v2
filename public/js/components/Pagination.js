import { createElement, clearChildren } from '../utils/domHelpers.js';

export class Pagination {
    constructor(containerSelector, onPageChangeCallback) {
        this.container = document.querySelector(containerSelector);
        if (!this.container) {
            console.error(`Conteneur de pagination introuvable: ${containerSelector}`);
            return;
        }
        this.onPageChangeCallback = onPageChangeCallback;
    }

    render(currentPage, totalPages, currentSearchParams) {
        clearChildren(this.container);

        const navElement = this.container.closest('nav[aria-label="Navigation des pages de résultats"]');

        if (totalPages <= 1) {
            if (navElement) navElement.classList.add('d-none');
            return;
        }

        if (navElement) navElement.classList.remove('d-none');

        // Bouton Précédent
        const prevDisabled = currentPage === 1;
        const prevLi = createElement('li', ['page-item']);
        if (prevDisabled) {
            prevLi.classList.add('disabled');
        }
        const prevLink = createElement('a', ['page-link'], { href: '#' }, 'Précédent');
        if (!prevDisabled) {
            prevLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.onPageChangeCallback(currentPage - 1);
            });
        }
        prevLi.appendChild(prevLink);
        this.container.appendChild(prevLi);

        // Numéros de page
        for (let i = 1; i <= totalPages; i++) {
            const pageLi = createElement('li', ['page-item']);
            if (i === currentPage) {
                pageLi.classList.add('active');
            }
            const pageLink = createElement('a', ['page-link'], { href: '#' }, i);
            
            if (i !== currentPage) {
                pageLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.onPageChangeCallback(i);
                });
            }
            pageLi.appendChild(pageLink);
            this.container.appendChild(pageLi);
        }

        // Bouton Suivant
        const nextDisabled = currentPage === totalPages;
        const nextLi = createElement('li', ['page-item']);
        if (nextDisabled) {
            nextLi.classList.add('disabled');
        }
        const nextLink = createElement('a', ['page-link'], { href: '#' }, 'Suivant');
        if (!nextDisabled) {
            nextLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.onPageChangeCallback(currentPage + 1);
            });
        }
        nextLi.appendChild(nextLink);
        this.container.appendChild(nextLi);
    }
}
