<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Nous Contacter</h1>
        <p class="banner-subtitle lead">Une question ? Une suggestion ? N'hésitez pas !</p>
    </div>
</section>

<section class="content-section py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">

            <div class="col-lg-5 d-flex">
                <div class="card w-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="card-title mb-4">Informations</h2>
                        <div class="flex-grow-1">
                            <p><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Adresse : 123 Rue Écologie, 35000 Rennes, France (Exemple)</p>
                            <p><i class="bi bi-envelope-fill me-2 text-primary"></i>Email : <a href="mailto:ecoride@contact.fr" class="link">ecoride@contact.fr</a></p>
                            <p><i class="bi bi-telephone-fill me-2 text-primary"></i>Téléphone : 01 23 45 67 89 (Exemple)</p>
                        </div>
                        <hr class="my-4">
                        <p class="mb-0">Vous pouvez aussi nous suivre sur les réseaux sociaux via les liens en bas de page.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 d-flex">
                <div class="card w-100"> <div class="card-body">
                        <h2 class="card-title mb-4">Envoyer un message</h2>
                        <form id="contact-form" novalidate>
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label for="contact-name" class="form-label">Votre Nom</label>
                                    <div class="form-input-custom d-flex align-items-center">
                                        <i class="bi bi-person me-2"></i>
                                        <input type="text" class="form-control-custom flex-grow-1" id="contact-name" name="name" placeholder="Ex: Dupont" aria-label="Votre Nom" required>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="contact-email" class="form-label">Votre Email</label>
                                    <div class="form-input-custom d-flex align-items-center">
                                        <i class="bi bi-envelope me-2"></i>
                                        <input type="email" class="form-control-custom flex-grow-1" id="contact-email" name="email" placeholder="Ex: nom@email.com" aria-label="Votre Email" required>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <label for="contact-subject" class="form-label">Sujet</label>
                                    <div class="form-input-custom d-flex align-items-center">
                                        <i class="bi bi-chat-left-text me-2"></i>
                                        <input type="text" class="form-control-custom flex-grow-1" id="contact-subject" name="subject" placeholder="Sujet de votre message" aria-label="Sujet" required>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <label for="contact-message" class="form-label">Message</label>
                                    <div class="form-input-custom d-flex align-items-start">
                                        <i class="bi bi-pencil me-2 pt-1"></i>
                                        <textarea class="form-control-custom flex-grow-1" id="contact-message" name="message" rows="5" placeholder="Écrivez votre message ici..." aria-label="Message" required style="resize: vertical;"></textarea>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div id="message-contact" class="alert d-none" role="alert"></div>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn primary-btn">Envoyer le message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php $pageScripts = ['/js/pages/contactPage.js']; ?>