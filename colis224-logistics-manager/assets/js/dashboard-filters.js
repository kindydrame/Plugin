/**
 * Colis224 Dashboard Filters JavaScript
 *
 * Gère les interactions avec le panneau de filtres et l'affichage des statistiques
 * Version: 2.18.22
 */

jQuery(document).ready(function($) {
    'use strict';

    // Variables globales
    var currentFilters = {
        time_period: '1_month'
    };

    /**
     * Toggle du panneau de filtres
     */
    $('#toggle-filters').on('click', function() {
        var $content = $('#filters-content');
        var $icon = $(this).find('.dashicons');

        if ($content.is(':visible')) {
            $content.slideUp(300);
            $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        } else {
            $content.slideDown(300);
            $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        }
    });

    /**
     * Sélection de la période
     */
    $('.period-btn').on('click', function() {
        // Retirer l'état actif des autres boutons
        $('.period-btn').removeAttr('data-active');

        // Activer ce bouton
        $(this).attr('data-active', 'true');

        // Mettre à jour le filtre
        var period = $(this).data('period');
        currentFilters.time_period = period;

        // Afficher/masquer les dates personnalisées
        if (period === 'custom') {
            $('#custom-dates').slideDown(200);
        } else {
            $('#custom-dates').slideUp(200);
        }
    });

    /**
     * Soumission du formulaire de filtres
     */
    $('#dashboard-filters-form').on('submit', function(e) {
        e.preventDefault();

        // Collecter tous les filtres
        var filters = {
            time_period: currentFilters.time_period
        };

        // Dates personnalisées
        if (currentFilters.time_period === 'custom') {
            filters.date_start = $('#date-start').val();
            filters.date_end = $('#date-end').val();
        }

        // Agent
        var agentId = $('#filter-agent').val();
        if (agentId) {
            filters.agent_id = agentId;
        }

        // Statut de paiement
        var paymentStatus = $('#filter-payment').val();
        if (paymentStatus && paymentStatus !== 'all') {
            filters.payment_status = paymentStatus;
        }

        // Pays d'origine
        var originCountry = $('#filter-origin').val();
        if (originCountry) {
            filters.origin_country = originCountry;
        }

        // Pays de destination
        var destinationCountry = $('#filter-destination').val();
        if (destinationCountry) {
            filters.destination_country = destinationCountry;
        }

        // Livreur
        var driverId = $('#filter-driver').val();
        if (driverId) {
            filters.driver_id = driverId;
        }

        // Partenaire
        var partnerId = $('#filter-partner').val();
        if (partnerId) {
            filters.partner_id = partnerId;
        }

        // Moyen de transport (v2.18.25)
        var transportId = $('#filter-transport').val();
        if (transportId) {
            filters.transport_mode_id = transportId;
        }

        // Pays du client (v2.18.25)
        var clientCountry = $('#filter-client-country').val();
        if (clientCountry) {
            filters.client_country = clientCountry;
        }

        // Charger les statistiques
        loadFilteredStats(filters);
    });

    /**
     * Réinitialiser les filtres
     */
    $('#reset-filters').on('click', function() {
        // Réinitialiser le formulaire
        $('#dashboard-filters-form')[0].reset();

        // Réinitialiser les boutons de période
        $('.period-btn').removeAttr('data-active');
        $('.period-btn[data-period="1_month"]').attr('data-active', 'true');

        // Cacher les dates personnalisées
        $('#custom-dates').slideUp(200);

        // Réinitialiser les filtres
        currentFilters = {
            time_period: '1_month'
        };

        // Cacher les statistiques filtrées
        $('#filtered-stats-container').slideUp(300);
        $('#filtered-stats-content').empty();
    });

    /**
     * Exporter les statistiques en CSV
     */
    $('#export-stats').on('click', function() {
        // Vérifier si des statistiques sont affichées
        if ($('#filtered-stats-content').is(':empty')) {
            alert('⚠️ Veuillez d\'abord appliquer des filtres pour générer des statistiques.');
            return;
        }

        // Collecter les filtres actuels
        var filters = collectCurrentFilters();

        // Créer un formulaire temporaire pour l'export
        var $form = $('<form>', {
            method: 'POST',
            action: colis224_ajax.ajax_url
        });

        $form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'colis224_export_stats_csv'
        }));

        $form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: colis224_ajax.dashboard_nonce
        }));

        $form.append($('<input>', {
            type: 'hidden',
            name: 'filters',
            value: JSON.stringify(filters)
        }));

        // Soumettre le formulaire
        $form.appendTo('body').submit().remove();
    });

    /**
     * Charger les statistiques filtrées via AJAX
     */
    function loadFilteredStats(filters) {
        // v2.18.28: Récupérer le nonce (de colis224_ajax OU du input hidden comme fallback)
        var ajaxUrl = '/wp-admin/admin-ajax.php';
        var nonce = null;

        // Essayer d'abord colis224_ajax
        if (typeof colis224_ajax !== 'undefined') {
            if (colis224_ajax.ajax_url) ajaxUrl = colis224_ajax.ajax_url;
            if (colis224_ajax.dashboard_nonce) nonce = colis224_ajax.dashboard_nonce;
        }

        // Si pas de nonce dans colis224_ajax, essayer le input hidden
        if (!nonce && $('#dashboard_nonce_field').length) {
            nonce = $('#dashboard_nonce_field').val();
            console.log('Nonce récupéré depuis input hidden');
        }

        // Si toujours pas de nonce, erreur
        if (!nonce) {
            $('#filtered-stats-content').html('<div class="error-message">❌ Erreur: Nonce manquant. Veuillez rafraîchir la page complètement (Ctrl+F5).</div>');
            console.error('Nonce non disponible!', {
                'colis224_ajax existe': typeof colis224_ajax !== 'undefined',
                'colis224_ajax': typeof colis224_ajax !== 'undefined' ? colis224_ajax : null,
                'Input hidden existe': $('#dashboard_nonce_field').length > 0
            });
            return;
        }

        console.log('✓ Nonce disponible, envoi requête AJAX...');

        // Afficher le conteneur et l'indicateur de chargement
        $('#filtered-stats-container').slideDown(300);
        $('#stats-loading').show();
        $('#filtered-stats-content').empty();

        // Requête AJAX
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'colis224_get_filtered_stats',
                nonce: nonce,
                filters: filters
            },
            success: function(response) {
                $('#stats-loading').hide();

                if (response.success) {
                    // Afficher les statistiques
                    $('#filtered-stats-content').html(response.data.html);

                    // Scroll vers les résultats
                    $('html, body').animate({
                        scrollTop: $('#filtered-stats-container').offset().top - 100
                    }, 500);

                    // Stocker les filtres actuels pour l'export
                    currentFilters = filters;

                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Erreur inconnue';
                    $('#filtered-stats-content').html('<div class="error-message">❌ ' + errorMsg + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#stats-loading').hide();
                console.error('AJAX Error:', status, error, xhr.responseText);
                $('#filtered-stats-content').html('<div class="error-message">❌ Erreur de connexion. Veuillez réessayer.</div>');
            }
        });
    }

    /**
     * Collecter les filtres actuels du formulaire
     */
    function collectCurrentFilters() {
        var filters = {
            time_period: currentFilters.time_period
        };

        // Dates personnalisées
        if (currentFilters.time_period === 'custom') {
            filters.date_start = $('#date-start').val();
            filters.date_end = $('#date-end').val();
        }

        // Agent
        var agentId = $('#filter-agent').val();
        if (agentId) {
            filters.agent_id = agentId;
        }

        // Statut de paiement
        var paymentStatus = $('#filter-payment').val();
        if (paymentStatus && paymentStatus !== 'all') {
            filters.payment_status = paymentStatus;
        }

        // Pays d'origine
        var originCountry = $('#filter-origin').val();
        if (originCountry) {
            filters.origin_country = originCountry;
        }

        // Pays de destination
        var destinationCountry = $('#filter-destination').val();
        if (destinationCountry) {
            filters.destination_country = destinationCountry;
        }

        // Livreur
        var driverId = $('#filter-driver').val();
        if (driverId) {
            filters.driver_id = driverId;
        }

        // Partenaire
        var partnerId = $('#filter-partner').val();
        if (partnerId) {
            filters.partner_id = partnerId;
        }

        // Moyen de transport (v2.18.25)
        var transportId = $('#filter-transport').val();
        if (transportId) {
            filters.transport_mode_id = transportId;
        }

        // Pays du client (v2.18.25)
        var clientCountry = $('#filter-client-country').val();
        if (clientCountry) {
            filters.client_country = clientCountry;
        }

        return filters;
    }

    /**
     * Auto-load des statistiques si un filtre est déjà appliqué
     * (utile après rafraîchissement de page)
     */
    function checkAutoLoad() {
        // Vérifier si des paramètres de filtres sont dans l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var autoLoad = urlParams.get('auto_load_stats');

        if (autoLoad === '1') {
            // Ouvrir le panneau de filtres
            $('#filters-content').show();
            $('#toggle-filters .dashicons')
                .removeClass('dashicons-arrow-down-alt2')
                .addClass('dashicons-arrow-up-alt2');

            // Charger les statistiques avec les filtres par défaut
            $('#dashboard-filters-form').submit();
        }
    }

    // Vérifier l'auto-chargement au chargement de la page
    checkAutoLoad();

    /**
     * Animation de rotation pour l'indicateur de chargement
     */
    function startLoadingAnimation() {
        var rotation = 0;
        var loadingInterval = setInterval(function() {
            rotation += 10;
            $('.rotating').css('transform', 'rotate(' + rotation + 'deg)');

            if (!$('#stats-loading').is(':visible')) {
                clearInterval(loadingInterval);
            }
        }, 50);
    }

    // Démarrer l'animation si le loading est visible
    if ($('#stats-loading').is(':visible')) {
        startLoadingAnimation();
    }

    /**
     * Accessibilité: Support du clavier pour les boutons de période
     */
    $('.period-btn').on('keypress', function(e) {
        if (e.which === 13 || e.which === 32) { // Enter ou Espace
            e.preventDefault();
            $(this).click();
        }
    });
});
