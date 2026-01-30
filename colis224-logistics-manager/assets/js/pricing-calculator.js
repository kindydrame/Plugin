/**
 * Colis224 Pricing Calculator
 * Calculateurs de tarifs interactifs
 *
 * @since 2.19.0
 */

(function($) {
    'use strict';

    // === CALCULATEUR CHINE → GUINÉE ===
    window.calculateChinaGuinea = function() {
        const category = $('#china_category').val();
        const quantity = parseFloat($('#china_quantity').val()) || 0;
        const resultDiv = $('#china_result');

        // Validation
        if (!category) {
            showError(resultDiv, 'Veuillez sélectionner une catégorie');
            return;
        }

        if (quantity <= 0) {
            showError(resultDiv, 'Veuillez entrer une quantité valide (supérieure à 0)');
            return;
        }

        // Récupérer les données de la catégorie sélectionnée
        const selectedOption = $('#china_category option:selected');
        const priceType = selectedOption.data('type');
        const unitPrice = parseFloat(selectedOption.data('price'));
        const currency = selectedOption.data('currency');
        const unit = selectedOption.data('unit');
        const categoryLabel = selectedOption.text();

        // Calcul
        const total = unitPrice * quantity;

        // Afficher le résultat
        const html = `
            <div class="result-success">
                <div class="result-header">
                    <h3>💰 Estimation du Prix</h3>
                </div>
                <div class="result-body">
                    <div class="result-row">
                        <span class="result-label">Catégorie:</span>
                        <span class="result-value">${categoryLabel}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Prix unitaire:</span>
                        <span class="result-value">${formatPrice(unitPrice, currency)}/${unit}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">${priceType === 'per_kg' ? 'Poids:' : 'Quantité:'}</span>
                        <span class="result-value">${quantity} ${unit}${quantity > 1 ? 's' : ''}</span>
                    </div>
                    <div class="result-divider"></div>
                    <div class="result-total">
                        <span class="total-label">TOTAL:</span>
                        <span class="total-value">${formatPrice(total, currency)}</span>
                    </div>
                </div>
                <div class="result-actions">
                    <button type="button" class="btn btn-secondary" onclick="copyPrice(${total}, '${currency}')">
                        <span class="dashicons dashicons-clipboard"></span>
                        Copier le prix
                    </button>
                </div>
            </div>
        `;

        resultDiv.html(html).slideDown(300);
    };

    // === CALCULATEUR CBM ===
    window.calculateCBM = function() {
        let length = parseFloat($('#cbm_length').val()) || 0;
        let width = parseFloat($('#cbm_width').val()) || 0;
        let height = parseFloat($('#cbm_height').val()) || 0;
        const unit = $('#cbm_unit').val();
        const resultDiv = $('#cbm_result');

        // Validation
        if (length <= 0 || width <= 0 || height <= 0) {
            showError(resultDiv, 'Veuillez entrer toutes les dimensions (supérieures à 0)');
            return;
        }

        // Convertir en mètres si nécessaire
        if (unit === 'cm') {
            length = length / 100;
            width = width / 100;
            height = height / 100;
        }

        // Calcul CBM
        const cbm = length * width * height;
        const pricePerCBM = 4700000; // FG
        const total = cbm * pricePerCBM;

        // Afficher le résultat
        const html = `
            <div class="result-success">
                <div class="result-header">
                    <h3>📦 Résultat du Calcul CBM</h3>
                </div>
                <div class="result-body">
                    <div class="result-row">
                        <span class="result-label">Dimensions:</span>
                        <span class="result-value">${length.toFixed(2)}m × ${width.toFixed(2)}m × ${height.toFixed(2)}m</span>
                    </div>
                    <div class="result-row result-highlight">
                        <span class="result-label">Volume (CBM):</span>
                        <span class="result-value"><strong>${cbm.toFixed(3)} m³</strong></span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Tarif maritime:</span>
                        <span class="result-value">${formatPrice(pricePerCBM, 'GNF')}/CBM</span>
                    </div>
                    <div class="result-divider"></div>
                    <div class="result-total">
                        <span class="total-label">TOTAL:</span>
                        <span class="total-value">${formatPrice(total, 'GNF')}</span>
                    </div>
                    <div class="result-note">
                        <small>⚠️ Hors frais de douane</small>
                    </div>
                </div>
                <div class="result-actions">
                    <button type="button" class="btn btn-secondary" onclick="copyPrice(${total}, 'GNF')">
                        <span class="dashicons dashicons-clipboard"></span>
                        Copier le prix
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="copyCBM(${cbm})">
                        <span class="dashicons dashicons-clipboard"></span>
                        Copier le CBM
                    </button>
                </div>
            </div>
        `;

        resultDiv.html(html).slideDown(300);
    };

    // === CALCULATEUR GUINÉE ↔ MONDE ===
    window.calculateGuineaWorld = function() {
        const country = $('#world_country').val();
        const city = $('#world_city').val();
        const mode = $('#world_mode').val();
        const type = $('#world_type').val();
        const quantity = parseInt($('#world_quantity').val()) || 0;
        const resultDiv = $('#world_result');

        // Validation
        if (!country) {
            showError(resultDiv, 'Veuillez sélectionner un pays');
            return;
        }

        if (!mode) {
            showError(resultDiv, 'Veuillez sélectionner un mode de livraison');
            return;
        }

        if (!type) {
            showError(resultDiv, 'Veuillez sélectionner un type de colis');
            return;
        }

        if (quantity <= 0) {
            showError(resultDiv, 'Veuillez entrer une quantité valide');
            return;
        }

        // Maroc: vérifier la ville
        const hasCity = $('#world_country option:selected').data('has-cities');
        if (hasCity === 'true' && !city) {
            showError(resultDiv, 'Veuillez sélectionner une ville');
            return;
        }

        // Faire la requête AJAX
        $.ajax({
            url: colis224Ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'colis224_calculate_pricing',
                nonce: colis224Ajax.nonce,
                calculator: 'guinea_world',
                country: country,
                city: city,
                delivery_mode: mode,
                parcel_type: type,
                quantity: quantity
            },
            beforeSend: function() {
                resultDiv.html('<div class="loading"><span class="spinner is-active"></span> Calcul en cours...</div>').slideDown(300);
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const html = `
                        <div class="result-success">
                            <div class="result-header">
                                <h3>💰 Estimation du Prix</h3>
                            </div>
                            <div class="result-body">
                                <div class="result-row">
                                    <span class="result-label">Destination:</span>
                                    <span class="result-value">${data.country_flag} ${data.country}${data.city ? ' - ' + data.city : ''}</span>
                                </div>
                                <div class="result-row">
                                    <span class="result-label">Mode:</span>
                                    <span class="result-value">${data.delivery_mode}</span>
                                </div>
                                <div class="result-row">
                                    <span class="result-label">Type:</span>
                                    <span class="result-value">${data.parcel_type}</span>
                                </div>
                                <div class="result-row">
                                    <span class="result-label">Prix unitaire:</span>
                                    <span class="result-value">${data.formatted_unit_price} ${data.currency}</span>
                                </div>
                                <div class="result-row">
                                    <span class="result-label">Quantité:</span>
                                    <span class="result-value">${data.quantity} pièce${data.quantity > 1 ? 's' : ''}</span>
                                </div>
                                <div class="result-divider"></div>
                                <div class="result-total">
                                    <span class="total-label">TOTAL:</span>
                                    <span class="total-value">${data.formatted_total} ${data.currency}</span>
                                </div>
                            </div>
                            <div class="result-actions">
                                <button type="button" class="btn btn-secondary" onclick="copyPrice(${data.total}, '${data.currency}')">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    Copier le prix
                                </button>
                            </div>
                        </div>
                    `;
                    resultDiv.html(html);
                } else {
                    showError(resultDiv, response.data.message || 'Erreur lors du calcul');
                }
            },
            error: function() {
                showError(resultDiv, 'Erreur de connexion au serveur');
            }
        });
    };

    // === MISE À JOUR DES MODES DE LIVRAISON ===
    window.updateWorldModes = function() {
        const selectedOption = $('#world_country option:selected');
        const modes = selectedOption.data('modes');
        const hasCity = selectedOption.data('has-cities');
        const modeSelect = $('#world_mode');
        const cityGroup = $('#world_city_group');

        // Afficher/masquer le champ ville
        if (hasCity === 'true') {
            cityGroup.slideDown(200);
        } else {
            cityGroup.slideUp(200);
            $('#world_city').val('');
        }

        // Réinitialiser et peupler les modes
        modeSelect.html('<option value="">-- Sélectionnez un mode --</option>');

        if (modes && modes.length > 0) {
            const modeLabels = {
                'bureau': '🏢 Bureau',
                'relais': '📍 Point relais',
                'domicile': '🏠 À domicile'
            };

            modes.forEach(function(mode) {
                const label = modeLabels[mode] || mode;
                modeSelect.append(`<option value="${mode}">${label}</option>`);
            });

            modeSelect.prop('disabled', false);
        } else {
            modeSelect.prop('disabled', true);
        }

        // Réinitialiser le résultat
        $('#world_result').slideUp(200).html('');
    };

    // === SÉLECTIONNER UN PAYS (depuis la grille) ===
    window.selectCountry = function(countryCode) {
        $('#world_country').val(countryCode).trigger('change');
        updateWorldModes();

        // Scroll vers le calculateur
        $('html, body').animate({
            scrollTop: $('.colis224-calculator-card').offset().top - 50
        }, 500);
    };

    // === MISE À JOUR DU LABEL QUANTITÉ (Chine → Guinée) ===
    $(document).on('change', '#china_category', function() {
        const selectedOption = $(this).find('option:selected');
        const priceType = selectedOption.data('type');
        const unit = selectedOption.data('unit');

        if (priceType === 'per_kg') {
            $('#china_quantity_label').text('Poids (kg)');
            $('#china_quantity').attr('placeholder', 'Ex: 2.5').attr('step', '0.1');
            $('#china_quantity_hint').text('Entrez le poids en kilogrammes');
        } else {
            $('#china_quantity_label').text('Quantité (pièces)');
            $('#china_quantity').attr('placeholder', 'Ex: 3').attr('step', '1');
            $('#china_quantity_hint').text('Entrez le nombre de pièces');
        }

        // Réinitialiser
        $('#china_quantity').val('');
        $('#china_result').slideUp(200).html('');
    });

    // === CALCULATEUR CONTENEUR FRANCE → CONAKRY (v2.19.1) ===
    window.calculateContainer = function() {
        const itemKey = $('#container_item').val();
        const quantity = parseFloat($('#container_quantity').val()) || 0;
        const resultDiv = $('#container_result');

        // Validation
        if (!itemKey) {
            showError(resultDiv, 'Veuillez sélectionner un article');
            return;
        }

        if (quantity <= 0) {
            showError(resultDiv, 'Veuillez entrer une quantité valide (supérieure à 0)');
            return;
        }

        // Récupérer les données de l'article sélectionné
        const selectedOption = $('#container_item option:selected');
        const unitPrice = parseFloat(selectedOption.data('price'));
        const unit = selectedOption.data('unit');
        const note = selectedOption.data('note');
        const itemLabel = selectedOption.text();

        // Si prix à définir
        if (unitPrice === 0) {
            showError(resultDiv, 'Le prix de cet article est à définir selon dimensions. Contactez le service.');
            return;
        }

        // Calcul
        const total = unitPrice * quantity;

        // Afficher le résultat
        const html = `
            <div class="result-success">
                <div class="result-header">
                    <h3>💰 Estimation du Prix</h3>
                </div>
                <div class="result-body">
                    <div class="result-row">
                        <span class="result-label">Article:</span>
                        <span class="result-value">${itemLabel}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Prix unitaire:</span>
                        <span class="result-value">${formatPrice(unitPrice, 'EUR')}/${unit}</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Quantité:</span>
                        <span class="result-value">${quantity} ${unit}${quantity > 1 ? 's' : ''}</span>
                    </div>
                    ${note ? `<div class="result-row"><span class="result-label">Note:</span><span class="result-value" style="color:#f59e0b;">${note}</span></div>` : ''}
                    <div class="result-divider"></div>
                    <div class="result-total">
                        <span class="total-label">TOTAL:</span>
                        <span class="total-value">${formatPrice(total, 'EUR')}</span>
                    </div>
                    <div class="result-note">
                        <small>📍 Départ: Entrepôt Paris</small>
                    </div>
                </div>
                <div class="result-actions">
                    <button type="button" class="btn btn-secondary" onclick="copyPrice(${total}, 'EUR')">
                        <span class="dashicons dashicons-clipboard"></span>
                        Copier le prix
                    </button>
                </div>
            </div>
        `;

        resultDiv.html(html).slideDown(300);
    };

    // === MISE À JOUR DU LABEL QUANTITÉ (CONTENEUR) ===
    window.updateContainerPrice = function() {
        const selectedOption = $('#container_item option:selected');
        const unit = selectedOption.data('unit');

        if (unit) {
            $('#container_quantity_label').text('Quantité (' + unit + ')');
            $('#container_quantity').attr('placeholder', 'Ex: 2');
            $('#container_quantity_hint').text('Entrez la quantité en ' + unit);
        }

        // Réinitialiser
        $('#container_quantity').val('');
        $('#container_result').slideUp(200).html('');
    };

    // === MISE À JOUR DE L'UNITÉ (CBM) ===
    $(document).on('change', '#cbm_unit', function() {
        const unit = $(this).val();
        $('.dimension-unit').text(unit);
        $('#cbm_result').slideUp(200).html('');
    });

    // === COPIER LE PRIX ===
    window.copyPrice = function(amount, currency) {
        const formatted = formatPrice(amount, currency);
        copyToClipboard(formatted);
        alert('✅ Prix copié: ' + formatted);
    };

    // === COPIER LE CBM ===
    window.copyCBM = function(cbm) {
        const formatted = cbm.toFixed(3) + ' m³';
        copyToClipboard(formatted);
        alert('✅ CBM copié: ' + formatted);
    };

    // === FONCTIONS UTILITAIRES ===
    function formatPrice(amount, currency) {
        if (currency === 'GNF') {
            return number_format(amount, 0, ',', ' ') + ' FG';
        } else if (currency === 'EUR') {
            return number_format(amount, 2, ',', ' ') + ' €';
        } else if (currency === 'USD') {
            return number_format(amount, 2, ',', ' ') + ' $';
        }
        return number_format(amount, 2, ',', ' ') + ' ' + currency;
    }

    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(',', '').replace(' ', '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    function copyToClipboard(text) {
        const tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(text).select();
        document.execCommand('copy');
        tempInput.remove();
    }

    function showError(container, message) {
        const html = `
            <div class="result-error">
                <span class="dashicons dashicons-warning"></span>
                <span>${message}</span>
            </div>
        `;
        container.html(html).slideDown(300);
    }

    // === INITIALISATION ===
    $(document).ready(function() {
        console.log('Colis224 Pricing Calculator chargé');
    });

})(jQuery);
