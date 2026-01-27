/**
 * JavaScript pour le slider de départs Colis224 - Version 2.10.1
 * Gestion avancée: défilement automatique, filtres, recherche, modes d'affichage
 */

(function($) {
    'use strict';

    class DeparturesSlider {
        constructor(container) {
            this.container = $(container);
            this.display = this.container.find('.colis224-departures-display');
            this.slider = this.container.find('.departures-slider');
            this.cards = this.slider.find('.departure-card');
            this.currentIndex = 0;
            this.autoScrollInterval = null;
            this.isAutoScrolling = false;
            this.currentView = 'slider'; // slider, grid, list
            this.isDarkMode = localStorage.getItem('colis224_dark_mode') === 'true';

            this.init();
        }

        init() {
            // Activer le défilement automatique si mode slider
            if (this.currentView === 'slider') {
                this.startAutoScroll();
            }

            // Pause au survol
            this.slider.on('mouseenter', () => this.pauseAuto());
            this.slider.on('mouseleave', () => this.startAutoScroll());

            // Flèches de navigation
            this.initNavigationArrows();

            // Toggle filtres avancés
            this.initAdvancedFiltersToggle();

            // Filtres
            this.initFilters();

            // Recherche rapide
            this.initCitySearch();

            // Mode d'affichage (Slider/Grid/List)
            this.initViewToggle();

            // Export PDF
            this.initExportPDF();

            // Mode sombre/clair
            this.initDarkMode();

            // Dupliquer les cards pour un défilement infini fluide
            if (this.cards.length > 0 && this.currentView === 'slider') {
                this.setupInfiniteScroll();
            }

            // Notification email (subscribe)
            this.initEmailNotification();
        }

        setupInfiniteScroll() {
            // Dupliquer les cartes pour créer un effet de boucle infinie
            const cardsClone = this.cards.clone();
            this.slider.append(cardsClone);

            // Ajouter la classe pour l'animation CSS
            this.slider.addClass('auto-scroll');
        }

        startAutoScroll() {
            if (this.isAutoScrolling || this.currentView !== 'slider') return;

            this.isAutoScrolling = true;
            this.slider.removeClass('paused');

            // Réinitialiser l'animation CSS
            const sliderElement = this.slider[0];
            if (sliderElement) {
                sliderElement.style.animation = 'none';

                // Force reflow
                void sliderElement.offsetWidth;

                // Réappliquer l'animation
                sliderElement.style.animation = null;

                // Calculer la durée en fonction du nombre de cartes
                const cardCount = this.cards.length;
                const duration = cardCount * 4; // 4 secondes par carte

                sliderElement.style.animationDuration = duration + 's';
            }
        }

        pauseAuto() {
            this.isAutoScrolling = false;
            this.slider.addClass('paused');
        }

        initNavigationArrows() {
            const self = this;
            
            // Flèche gauche
            $('#slider-arrow-left').on('click', function() {
                self.pauseAuto();
                self.navigateSlider('prev');
                // Reprendre le défilement après 3 secondes
                setTimeout(() => self.startAutoScroll(), 3000);
            });
            
            // Flèche droite
            $('#slider-arrow-right').on('click', function() {
                self.pauseAuto();
                self.navigateSlider('next');
                // Reprendre le défilement après 3 secondes
                setTimeout(() => self.startAutoScroll(), 3000);
            });
        }

        navigateSlider(direction) {
            const cardCount = this.cards.length;
            if (cardCount === 0) return;

            const cardWidth = this.cards.first().outerWidth(true);
            const sliderElement = this.slider[0];
            
            // Calculer le décalage actuel
            const currentTransform = sliderElement.style.transform || 'translateX(0px)';
            const currentOffset = parseFloat(currentTransform.match(/-?\d+\.?\d*/)) || 0;

            if (direction === 'next') {
                const newOffset = currentOffset - cardWidth;
                // Si on atteint la fin, revenir au début
                if (Math.abs(newOffset) >= cardWidth * cardCount) {
                    sliderElement.style.transform = 'translateX(0px)';
                } else {
                    sliderElement.style.transform = `translateX(${newOffset}px)`;
                }
            } else if (direction === 'prev') {
                const newOffset = currentOffset + cardWidth;
                // Si on est au début, aller à la fin
                if (newOffset > 0) {
                    sliderElement.style.transform = `translateX(-${cardWidth * (cardCount - 1)}px)`;
                } else {
                    sliderElement.style.transform = `translateX(${newOffset}px)`;
                }
            }

            // Ajouter une transition fluide
            sliderElement.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            setTimeout(() => {
                sliderElement.style.transition = '';
            }, 500);
        }

        initAdvancedFiltersToggle() {
            const toggleBtn = $('#btn-toggle-advanced');
            const advancedSection = $('#advanced-filters');

            toggleBtn.on('click', function() {
                // Toggle la visibilité de la section
                advancedSection.slideToggle(300);

                // Toggle la classe active pour rotation de la flèche
                toggleBtn.toggleClass('active');
            });
        }

        initFilters() {
            const self = this;
            const filters = {
                departureCountry: $('#filter-departure-country'),
                arrivalCountry: $('#filter-arrival-country'),
                transportType: $('#filter-transport-type'),
                month: $('#filter-month')
            };

            // Écouter les changements de filtres
            Object.values(filters).forEach(filter => {
                filter.on('change', () => {
                    self.applyFilters();
                    self.updateActiveFilters();
                });
            });

            // Bouton reset
            $('#btn-filter-reset').on('click', () => {
                Object.values(filters).forEach(filter => filter.val(''));
                $('#filter-city-search').val('');
                self.applyFilters();
                self.updateActiveFilters();
            });
        }

        initCitySearch() {
            const self = this;
            let searchTimeout;

            $('#filter-city-search').on('input', function() {
                clearTimeout(searchTimeout);
                const searchTerm = $(this).val().toLowerCase().trim();

                searchTimeout = setTimeout(() => {
                    if (searchTerm.length === 0) {
                        // Afficher toutes les cartes
                        self.cards.show();
                    } else if (searchTerm.length >= 2) {
                        // Filtrer par ville
                        self.cards.each(function() {
                            const $card = $(this);
                            const cities = $card.find('.city').text().toLowerCase();

                            if (cities.includes(searchTerm)) {
                                $card.show();
                            } else {
                                $card.hide();
                            }
                        });
                    }

                    // Vérifier s'il y a des résultats
                    self.checkNoResults();
                    self.updateActiveFilters();
                }, 300);
            });
        }

        initViewToggle() {
            const self = this;
            let viewState = 0; // 0: slider, 1: grid, 2: list

            $('#btn-view-toggle').on('click', function() {
                viewState = (viewState + 1) % 3;

                // Mettre à jour le bouton
                const $btn = $(this);
                const $icon = $btn.find('.view-icon');
                const $text = $btn.find('.view-text');

                // Retirer les classes de vue existantes
                self.slider.removeClass('departures-slider departures-grid departures-list auto-scroll paused');

                switch(viewState) {
                    case 0: // Slider
                        self.currentView = 'slider';
                        self.slider.addClass('departures-slider');
                        $icon.text('📋');
                        $text.text('Vue Grille');
                        self.setupInfiniteScroll();
                        self.startAutoScroll();
                        break;

                    case 1: // Grid
                        self.currentView = 'grid';
                        self.slider.addClass('departures-grid');
                        $icon.text('📝');
                        $text.text('Vue Liste');
                        self.pauseAuto();
                        break;

                    case 2: // List
                        self.currentView = 'list';
                        self.slider.addClass('departures-list');
                        $icon.text('🎬');
                        $text.text('Vue Slider');
                        self.pauseAuto();
                        break;
                }
            });
        }

        initExportPDF() {
            const self = this;

            $('#btn-export-pdf').on('click', function() {
                // Préparation pour l'impression
                $(this).prop('disabled', true).html('⏳ Génération...');

                // Utiliser l'impression du navigateur (converti en PDF)
                setTimeout(() => {
                    window.print();
                    $(this).prop('disabled', false).html('📄 Exporter PDF');
                }, 500);

                // Alternative: Envoyer une requête AJAX pour générer un PDF côté serveur
                /*
                $.ajax({
                    url: colis224Departures.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'colis224_export_departures_pdf',
                        nonce: colis224Departures.nonce,
                        month: $('#filter-month').val()
                    },
                    success: (response) => {
                        if (response.success) {
                            // Télécharger le PDF
                            window.location.href = response.data.pdf_url;
                        }
                    }
                });
                */
            });
        }

        initDarkMode() {
            const self = this;

            // Appliquer le mode sombre au chargement si activé
            if (self.isDarkMode) {
                self.container.addClass('dark-mode');
            }

            // Ajouter un bouton toggle (optionnel, à ajouter dans le HTML)
            const $darkModeToggle = $('<button>', {
                class: 'btn-dark-mode-toggle',
                html: self.isDarkMode ? '☀️ Mode Clair' : '🌙 Mode Sombre',
                css: {
                    position: 'fixed',
                    bottom: '20px',
                    right: '20px',
                    padding: '12px 20px',
                    background: self.isDarkMode ? '#f7fafc' : '#2d3748',
                    color: self.isDarkMode ? '#2d3748' : '#f7fafc',
                    border: 'none',
                    borderRadius: '25px',
                    cursor: 'pointer',
                    fontWeight: '600',
                    fontSize: '14px',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                    zIndex: '1000',
                    transition: 'all 0.3s ease'
                }
            });

            $('body').append($darkModeToggle);

            $darkModeToggle.on('click', function() {
                self.isDarkMode = !self.isDarkMode;
                localStorage.setItem('colis224_dark_mode', self.isDarkMode);

                if (self.isDarkMode) {
                    self.container.addClass('dark-mode');
                    $(this).html('☀️ Mode Clair').css({
                        background: '#f7fafc',
                        color: '#2d3748'
                    });
                } else {
                    self.container.removeClass('dark-mode');
                    $(this).html('🌙 Mode Sombre').css({
                        background: '#2d3748',
                        color: '#f7fafc'
                    });
                }
            });

            // Hover effect
            $darkModeToggle.hover(
                function() { $(this).css('transform', 'scale(1.05)'); },
                function() { $(this).css('transform', 'scale(1)'); }
            );
        }

        initEmailNotification() {
            const self = this;

            // Option pour s'abonner aux notifications email de nouveaux départs
            console.log('📧 Initialisation formulaire abonnement email');
            console.log('colis224Departures:', typeof colis224Departures !== 'undefined' ? 'OK' : 'UNDEFINED');

            const $notifyBtn = $('<div>', {
                class: 'email-notification-banner',
                html: `
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; margin: 20px 0; text-align: center; color: white;">
                        <h4 style="margin: 0 0 10px 0;">📧 Restez informé des nouveaux départs</h4>
                        <p style="margin: 0 0 15px 0; opacity: 0.9;">Recevez une notification par email dès qu'un nouveau départ est ajouté</p>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="email" id="notify-email" placeholder="Votre adresse email" style="padding: 12px; border-radius: 8px; border: none; width: 300px; max-width: 100%; box-sizing: border-box;">
                            <button id="btn-subscribe-notifications" style="padding: 12px 24px; background: white; color: #667eea; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                S'abonner
                            </button>
                        </div>
                    </div>
                `
            });

            this.container.append($notifyBtn);
            console.log('✅ Formulaire ajouté au DOM');

            // Attacher l'événement avec un délai pour s'assurer que l'élément est dans le DOM
            setTimeout(() => {
                const $btn = $('#btn-subscribe-notifications');
                console.log('🔘 Bouton trouvé:', $btn.length > 0 ? 'OUI' : 'NON');

                $btn.on('click', function() {
                    console.log('🖱️ Clic sur bouton abonnement détecté');
                    const email = $('#notify-email').val().trim();
                    console.log('📧 Email saisi:', email);

                    if (!email || !self.validateEmail(email)) {
                        alert('Veuillez entrer une adresse email valide.');
                        return;
                    }

                    $(this).prop('disabled', true).text('Envoi...');
                    console.log('📤 Envoi requête AJAX...');

                    $.ajax({
                        url: colis224Departures.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'colis224_subscribe_departure_notifications',
                            nonce: colis224Departures.nonce,
                            email: email
                        },
                        success: (response) => {
                            console.log('✅ Réponse reçue:', response);
                            if (response.success) {
                                alert('✅ Vous êtes maintenant abonné aux notifications de nouveaux départs!');
                                $('#notify-email').val('');
                            } else {
                                alert('❌ Erreur: ' + (response.data.message || 'Une erreur est survenue'));
                            }
                            $('#btn-subscribe-notifications').prop('disabled', false).text('S\'abonner');
                        },
                        error: (xhr, status, error) => {
                            console.error('❌ Erreur AJAX:', status, error);
                            alert('❌ Erreur lors de l\'abonnement. Veuillez réessayer.');
                            $('#btn-subscribe-notifications').prop('disabled', false).text('S\'abonner');
                        }
                    });
                });
            }, 100);
        }

        validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        updateActiveFilters() {
            const $activeFilters = $('#active-filters');
            const $filterTags = $activeFilters.find('.filter-tags');
            $filterTags.empty();

            let hasActiveFilters = false;

            // Pays de départ
            const departureCountry = $('#filter-departure-country').val();
            if (departureCountry) {
                hasActiveFilters = true;
                const countryName = $('#filter-departure-country option:selected').text();
                $filterTags.append(this.createFilterTag('Départ: ' + countryName, 'departure-country'));
            }

            // Pays d'arrivée
            const arrivalCountry = $('#filter-arrival-country').val();
            if (arrivalCountry) {
                hasActiveFilters = true;
                const countryName = $('#filter-arrival-country option:selected').text();
                $filterTags.append(this.createFilterTag('Arrivée: ' + countryName, 'arrival-country'));
            }

            // Type de transport
            const transportType = $('#filter-transport-type').val();
            if (transportType) {
                hasActiveFilters = true;
                const transportName = $('#filter-transport-type option:selected').text();
                $filterTags.append(this.createFilterTag(transportName, 'transport-type'));
            }

            // Mois
            const month = $('#filter-month').val();
            if (month) {
                hasActiveFilters = true;
                const monthName = $('#filter-month option:selected').text();
                $filterTags.append(this.createFilterTag('Mois: ' + monthName, 'month'));
            }

            // Recherche par ville
            const citySearch = $('#filter-city-search').val();
            if (citySearch) {
                hasActiveFilters = true;
                $filterTags.append(this.createFilterTag('Ville: ' + citySearch, 'city-search'));
            }

            // Afficher/masquer la zone des filtres actifs
            if (hasActiveFilters) {
                $activeFilters.show();
            } else {
                $activeFilters.hide();
            }
        }

        createFilterTag(text, filterType) {
            const self = this;
            const $tag = $('<span>', {
                class: 'filter-tag',
                html: text + ' <span class="filter-tag-remove">×</span>'
            });

            $tag.find('.filter-tag-remove').on('click', function() {
                // Retirer le filtre correspondant
                switch(filterType) {
                    case 'departure-country':
                        $('#filter-departure-country').val('');
                        break;
                    case 'arrival-country':
                        $('#filter-arrival-country').val('');
                        break;
                    case 'transport-type':
                        $('#filter-transport-type').val('');
                        break;
                    case 'month':
                        $('#filter-month').val('');
                        break;
                    case 'city-search':
                        $('#filter-city-search').val('');
                        break;
                }
                self.applyFilters();
                self.updateActiveFilters();
            });

            return $tag;
        }

        applyFilters() {
            const departureCountry = $('#filter-departure-country').val();
            const arrivalCountry = $('#filter-arrival-country').val();
            const transportType = $('#filter-transport-type').val();
            const month = $('#filter-month').val();

            // Afficher un loader
            this.showLoading();

            // Requête AJAX pour charger les départs filtrés
            $.ajax({
                url: colis224Departures.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'colis224_load_departures',
                    nonce: colis224Departures.nonce,
                    departure_country: departureCountry,
                    arrival_country: arrivalCountry,
                    transport_type: transportType,
                    month: month
                },
                success: (response) => {
                    if (response.success) {
                        // Remplacer le contenu
                        this.slider.html(response.data.html);
                        this.cards = this.slider.find('.departure-card');

                        // Réinitialiser le slider
                        if (this.cards.length > 0 && this.currentView === 'slider') {
                            this.setupInfiniteScroll();
                            this.startAutoScroll();
                        }

                        // Vérifier s'il n'y a aucun résultat
                        this.checkNoResults();
                    }
                    this.hideLoading();
                },
                error: () => {
                    this.hideLoading();
                    alert('❌ Erreur lors du chargement des départs. Veuillez réessayer.');
                }
            });
        }

        checkNoResults() {
            const visibleCards = this.cards.filter(':visible').length;
            const $noResults = this.slider.find('.no-departures');

            if (visibleCards === 0 && $noResults.length === 0) {
                // Afficher le message "Aucun résultat"
                this.slider.prepend(`
                    <div class="no-departures">
                        <div class="no-results-icon">😔</div>
                        <h3>Aucun départ trouvé</h3>
                        <p>Désolé, aucun départ ne correspond à vos critères de recherche.</p>
                        <p class="suggestion">💡 <strong>Suggestions:</strong> Essayez de modifier vos filtres ou contactez notre service client pour plus d'informations.</p>

                        <div class="contact-buttons">
                            <a href="https://wa.me/224626526735?text=${encodeURIComponent('Bonjour, je cherche des informations sur les départs disponibles depuis la Guinée.')}" target="_blank" class="btn-contact btn-guinea">
                                <span class="btn-icon">📞</span>
                                <div class="btn-content">
                                    <strong>Agence Guinée</strong>
                                    <span>+224 626 526 735</span>
                                </div>
                            </a>

                            <a href="https://wa.me/224620178930?text=${encodeURIComponent('Bonjour, j\'aimerais avoir des informations sur vos services de départs internationaux.')}" target="_blank" class="btn-contact btn-international">
                                <span class="btn-icon">🌍</span>
                                <div class="btn-content">
                                    <strong>Service International</strong>
                                    <span>+224 620 178 930</span>
                                </div>
                            </a>
                        </div>

                        <div class="no-results-footer">
                            <p>📧 Email: <a href="mailto:contact@colis224.com">contact@colis224.com</a></p>
                            <p>🌐 Site web: <a href="https://www.colis224.com" target="_blank">www.colis224.com</a></p>
                        </div>
                    </div>
                `);
            } else if (visibleCards > 0) {
                // Retirer le message si des résultats sont visibles
                $noResults.remove();
            }
        }

        showLoading() {
            this.slider.html(`
                <div class="departures-loading">
                    <div class="loading-spinner"></div>
                    <p>Chargement des départs...</p>
                </div>
            `);
        }

        hideLoading() {
            // Le loading est remplacé par le contenu
        }
    }

    // Initialiser au chargement de la page
    $(document).ready(function() {
        $('.colis224-departures-container').each(function() {
            new DeparturesSlider(this);
        });
    });

})(jQuery);
