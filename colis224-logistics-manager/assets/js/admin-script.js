/**
 * Colis224 Logistics Manager - Scripts Admin
 */

(function($) {
    'use strict';

    // === FONCTION DE NAVIGATION DOUCE (REMPLACE location.reload()) ===
    window.colis224Navigate = function(page, params = {}) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        
        Object.keys(params).forEach(key => {
            url.searchParams.set(key, params[key]);
        });
        
        // Transition douce
        $('body').css('opacity', '0.8');
        setTimeout(() => {
            window.location.href = url.toString();
        }, 200);
    };

    // Fonction de rechargement doux avec préservation du scroll
    window.colis224SoftReload = function() {
        const scrollPos = window.scrollY;
        sessionStorage.setItem('colis224_scroll_pos', scrollPos);
        window.location.reload();
    };

    // Restaurer la position de scroll après rechargement
    const savedScrollPos = sessionStorage.getItem('colis224_scroll_pos');
    if (savedScrollPos) {
        window.scrollTo(0, parseInt(savedScrollPos));
        sessionStorage.removeItem('colis224_scroll_pos');
    }

    $(document).ready(function() {

        // === CALCUL AUTOMATIQUE DU TOTAL (COLIS) ===
        function calculateParcelTotal() {
            var unitPrice = parseFloat($('#unit_price').val()) || 0;
            var weight = parseFloat($('#weight').val()) || 0;
            var discountType = $('#discount_type').val();
            var discountValue = parseFloat($('#discount_value').val()) || 0;

            // v2.18.29: Calcul correct = Prix unitaire × Poids
            var subtotal = unitPrice * weight;
            var total = subtotal;

            if (discountType === 'percentage') {
                total = subtotal - (subtotal * discountValue / 100);
            } else {
                total = subtotal - discountValue;
            }

            total = Math.max(0, total);
            $('#total_amount').val(total.toFixed(2));
        }

        // Déclencher le calcul lors de la modification des champs (incluant le poids)
        $('#unit_price, #weight, #discount_type, #discount_value').on('input change', calculateParcelTotal);

        // === GÉNÉRATION AUTOMATIQUE DU NUMÉRO DE SUIVI ===
        $('#recipient_phone').on('blur', function() {
            if ($('#tracking_number').val() === '' && $('#tracking_number').attr('readonly') !== 'readonly') {
                var phone = $(this).val().replace(/\s/g, '');
                var last4 = phone.slice(-4);
                if (last4.length === 4) {
                    var prefix = $('#parcel_prefix').val() || 'PA';
                    $('#tracking_number').val(prefix + last4);
                }
            }
        });

        // === CALCUL DU MONTANT RESTANT (PAIEMENT PARTIEL) ===
        function calculateRemainingAmount() {
            var total = parseFloat($('#total_amount').val()) || 0;
            var paid = parseFloat($('#paid_amount').val()) || 0;
            var remaining = total - paid;

            // Mettre à jour le statut de paiement automatiquement
            if (paid >= total && total > 0) {
                $('#payment_status').val('Payé');
            } else if (paid > 0 && paid < total) {
                $('#payment_status').val('Partiel');
            } else {
                $('#payment_status').val('Non payé');
            }
        }

        $('#total_amount, #paid_amount').on('input', calculateRemainingAmount);

        // === CONFIRMATION DE SUPPRESSION ===
        $('.button-link-delete').on('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                e.preventDefault();
                return false;
            }
        });

        // === FORMATAGE DES NOMBRES (SÉPARATEURS DE MILLIERS) ===
        $('input[type="number"]').on('blur', function() {
            var value = parseFloat($(this).val());
            if (!isNaN(value) && $(this).attr('step') === undefined) {
                // $(this).val(value.toLocaleString('fr-FR'));
            }
        });

        // === VALIDATION DES FORMULAIRES ===
        $('form').on('submit', function(e) {
            var hasErrors = false;
            var errorMessages = [];

            // Vérifier les champs requis
            $(this).find('[required]').each(function() {
                if ($(this).val() === '' || $(this).val() === null) {
                    hasErrors = true;
                    var fieldName = $(this).closest('.colis224-form-group').find('label').text();
                    errorMessages.push('Le champ "' + fieldName + '" est obligatoire.');
                    $(this).css('border-color', '#d63638');
                } else {
                    $(this).css('border-color', '');
                }
            });

            // Vérifier les emails
            $(this).find('input[type="email"]').each(function() {
                if ($(this).val() !== '') {
                    var email = $(this).val();
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        hasErrors = true;
                        errorMessages.push('L\'adresse email "' + email + '" n\'est pas valide.');
                        $(this).css('border-color', '#d63638');
                    }
                }
            });

            // Vérifier les montants négatifs
            $(this).find('input[type="number"]').each(function() {
                if ($(this).attr('min') === '0' && parseFloat($(this).val()) < 0) {
                    hasErrors = true;
                    var fieldName = $(this).closest('.colis224-form-group').find('label').text();
                    errorMessages.push('Le champ "' + fieldName + '" ne peut pas être négatif.');
                    $(this).css('border-color', '#d63638');
                }
            });

            if (hasErrors) {
                e.preventDefault();
                alert('Erreurs de validation:\n\n' + errorMessages.join('\n'));
                return false;
            }
        });

        // === AUTO-CALCUL POUR SERVICE D'ACHAT ===
        function calculatePurchaseTotal() {
            var estimated = parseFloat($('#estimated_amount').val()) || 0;
            var fee = parseFloat($('#service_fee').val()) || 0;
            var total = estimated + fee;
            $('#total_amount').val(total.toFixed(2));
        }

        $('#estimated_amount, #service_fee').on('input', calculatePurchaseTotal);

        // === FILTRAGE DYNAMIQUE DES TABLEAUX ===
        $('.colis224-search-input').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            var table = $(this).closest('.colis224-card, .wrap').find('.colis224-table tbody');

            table.find('tr').each(function() {
                var rowText = $(this).text().toLowerCase();
                if (rowText.indexOf(searchTerm) === -1) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });

        // === EXPORT DE DONNÉES ===
        $('.export-csv').on('click', function(e) {
            // Fonctionnalité d'export CSV côté client (optionnel)
        });

        // === IMPRESSION ===
        $('.print-page').on('click', function(e) {
            e.preventDefault();
            window.print();
        });

        // === TOOLTIPS ET AIDE ===
        $('[data-tooltip]').hover(
            function() {
                var tooltipText = $(this).data('tooltip');
                $('<div class="colis224-tooltip">' + tooltipText + '</div>')
                    .appendTo('body')
                    .fadeIn('fast');
            },
            function() {
                $('.colis224-tooltip').remove();
            }
        );

        // === SÉLECTION AUTOMATIQUE DU CLIENT ===
        $('#client_id').on('change', function() {
            var clientId = $(this).val();
            if (clientId) {
                // Vous pouvez récupérer les informations du client via AJAX
                // et pré-remplir les champs si nécessaire
            }
        });

        // === CALCUL DE LA DATE DE LIVRAISON ESTIMÉE ===
        $('#shipping_date, #transport_mode_id').on('change', function() {
            var shippingDate = $('#shipping_date').val();
            var transportMode = $('#transport_mode_id option:selected').text();

            if (shippingDate) {
                var estimatedDays = 0;

                // Estimation basée sur le mode de transport
                if (transportMode.includes('Express')) {
                    estimatedDays = 3;
                } else if (transportMode.includes('Standard')) {
                    estimatedDays = 7;
                } else if (transportMode.includes('Bateau')) {
                    estimatedDays = 30;
                } else {
                    estimatedDays = 7; // Par défaut
                }

                // Calculer la date estimée
                var date = new Date(shippingDate);
                date.setDate(date.getDate() + estimatedDays);

                var year = date.getFullYear();
                var month = String(date.getMonth() + 1).padStart(2, '0');
                var day = String(date.getDate()).padStart(2, '0');

                $('#estimated_delivery_date').val(year + '-' + month + '-' + day);
            }
        });

        // === NOTIFICATIONS ===
        // Auto-fermeture des notifications après 5 secondes
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut('slow');
        }, 5000);

        // === CHARGEMENT AJAX (POUR FUTURES FONCTIONNALITÉS) ===
        function loadDataViaAjax(action, data, callback) {
            $.ajax({
                url: colis224Ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: colis224Ajax.nonce,
                    data: data
                },
                success: function(response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', error);
                }
            });
        }

        // === INITIALISATION ===
        console.log('Colis224 Logistics Manager - Admin Scripts chargés');

    });

})(jQuery);
