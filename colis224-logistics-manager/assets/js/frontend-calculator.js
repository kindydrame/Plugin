/**
 * Colis224 Logistics Manager - Calculateur de Tarif Frontend
 *
 * @version 2.17.0
 */

(function($) {
    'use strict';

    // Variables globales
    var calculatedRate = null;
    var isCalculating = false;
    var isSaving = false;

    $(document).ready(function() {
        console.log('Colis224 Calculator: Initialisation...');

        // Initialiser le formulaire
        initCalculatorForm();

        // Charger les données sauvegardées si disponibles
        loadSavedFormData();

        console.log('Colis224 Calculator: Prêt!');
    });

    /**
     * Initialiser le formulaire de calcul
     */
    function initCalculatorForm() {
        var $form = $('#colis224-calculator-form');
        var $btnCalculate = $('#btn-calculate');
        var $btnSave = $('#btn-save-quote');

        if (!$form.length) {
            console.log('Colis224 Calculator: Formulaire non trouvé sur cette page');
            return;
        }

        // Bouton Calculer
        $btnCalculate.on('click', function(e) {
            e.preventDefault();
            calculateRate();
        });

        // Soumission du formulaire (Enregistrer)
        $form.on('submit', function(e) {
            e.preventDefault();
            saveQuote();
        });

        // Sauvegarde automatique des champs
        $form.find('input, select, textarea').on('change', function() {
            saveFormData();

            // Réinitialiser le calcul si les paramètres changent
            var fieldName = $(this).attr('name');
            var rateFields = ['weight', 'origin_country', 'destination_country', 'transport_mode', 'category'];

            if (rateFields.indexOf(fieldName) !== -1) {
                resetCalculation();
            }
        });

        // Validation en temps réel du poids
        $('#weight').on('input', function() {
            var weight = parseFloat($(this).val());
            if (weight < 0) {
                $(this).val(0);
            }
        });

        // Validation du téléphone
        $('#sender_phone, #recipient_phone').on('input', function() {
            var phone = $(this).val();
            // Garder seulement les chiffres et le +
            $(this).val(phone.replace(/[^\d+\s-]/g, ''));
        });
    }

    /**
     * Calculer le tarif
     */
    function calculateRate() {
        if (isCalculating) return;

        var $form = $('#colis224-calculator-form');
        var $btnCalculate = $('#btn-calculate');
        var $result = $('#colis224-calculator-result');
        var $message = $('#colis224-calculator-message');

        // Validation des champs requis pour le calcul
        var weight = parseFloat($('#weight').val()) || 0;
        var originCountry = $('#origin_country').val();
        var destinationCountry = $('#destination_country').val();
        var transportMode = $('#transport_mode').val();
        var category = $('#category').val();

        if (weight <= 0) {
            showMessage('error', colis224Calculator.messages.invalid_weight);
            $('#weight').focus();
            return;
        }

        if (!originCountry || !destinationCountry || !transportMode || !category) {
            showMessage('error', colis224Calculator.messages.required);
            return;
        }

        // Désactiver le bouton
        isCalculating = true;
        $btnCalculate.prop('disabled', true).text(colis224Calculator.messages.calculating);
        $message.hide();

        // Préparer les données
        var data = {
            action: 'colis224_calculate_rate',
            nonce: colis224Calculator.nonce,
            weight: weight,
            origin_country: originCountry,
            destination_country: destinationCountry,
            transport_mode: transportMode,
            category: category
        };

        console.log('Colis224 Calculator: Envoi requête calcul...', data);

        // Requête AJAX
        $.ajax({
            url: colis224Calculator.ajaxurl,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                console.log('Colis224 Calculator: Réponse reçue', response);

                if (response.success) {
                    calculatedRate = response.data;
                    displayResult(response.data);
                    $('#btn-save-quote').prop('disabled', false);
                    showMessage('success', 'Tarif calculé avec succès !');
                } else {
                    showMessage('error', response.data.message || colis224Calculator.messages.error);
                    $result.hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Colis224 Calculator: Erreur AJAX', status, error);
                console.error('Réponse brute:', xhr.responseText);
                showMessage('error', colis224Calculator.messages.error);
                $result.hide();
            },
            complete: function() {
                isCalculating = false;
                $btnCalculate.prop('disabled', false).text('Calculer le tarif');
            }
        });
    }

    /**
     * Afficher le résultat du calcul
     */
    function displayResult(data) {
        var $result = $('#colis224-calculator-result');

        $('#result-base-rate').text(data.formatted.base_rate);
        $('#result-extra-fees').text(data.formatted.extra_fees);
        $('#result-total').text(data.formatted.total);
        $('#result-delivery-time').text(data.formatted.delivery_time);

        $result.slideDown(300);

        // Scroll vers le résultat
        $('html, body').animate({
            scrollTop: $result.offset().top - 100
        }, 500);
    }

    /**
     * Enregistrer le devis
     */
    function saveQuote() {
        if (isSaving) return;

        if (!calculatedRate) {
            showMessage('error', 'Veuillez d\'abord calculer le tarif.');
            return;
        }

        var $form = $('#colis224-calculator-form');
        var $btnSave = $('#btn-save-quote');
        var $message = $('#colis224-calculator-message');

        // Validation des champs expéditeur
        var senderName = $('#sender_name').val().trim();
        var senderPhone = $('#sender_phone').val().trim();
        var recipientName = $('#recipient_name').val().trim();
        var recipientPhone = $('#recipient_phone').val().trim();

        if (!senderName || !senderPhone) {
            showMessage('error', 'Les informations de l\'expéditeur sont obligatoires.');
            $('#sender_name').focus();
            return;
        }

        if (!recipientName || !recipientPhone) {
            showMessage('error', 'Les informations du destinataire sont obligatoires.');
            $('#recipient_name').focus();
            return;
        }

        // Validation basique du téléphone
        if (senderPhone.replace(/[\s-]/g, '').length < 8) {
            showMessage('error', colis224Calculator.messages.invalid_phone);
            $('#sender_phone').focus();
            return;
        }

        // Désactiver le bouton
        isSaving = true;
        $btnSave.prop('disabled', true).text(colis224Calculator.messages.saving);

        // Préparer les données
        var data = {
            action: 'colis224_save_quote',
            nonce: colis224Calculator.nonce,
            sender_name: senderName,
            sender_phone: senderPhone,
            sender_email: $('#sender_email').val().trim(),
            sender_address: $('#sender_address').val().trim(),
            recipient_name: recipientName,
            recipient_phone: recipientPhone,
            recipient_address: $('#recipient_address').val().trim(),
            origin_country: $('#origin_country').val(),
            destination_country: $('#destination_country').val(),
            transport_mode: $('#transport_mode').val(),
            category: $('#category').val(),
            weight: parseFloat($('#weight').val()) || 0,
            description: $('#description').val().trim(),
            estimated_total: calculatedRate.total
        };

        console.log('Colis224 Calculator: Enregistrement du devis...', data);

        // Requête AJAX
        $.ajax({
            url: colis224Calculator.ajaxurl,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                console.log('Colis224 Calculator: Réponse enregistrement', response);

                if (response.success) {
                    showMessage('success', response.data.message + ' (Réf: ' + response.data.quote_number + ')');

                    // Effacer les données sauvegardées
                    clearSavedFormData();

                    // Optionnel: Réinitialiser le formulaire
                    // $form[0].reset();
                    // resetCalculation();
                } else {
                    showMessage('error', response.data.message || colis224Calculator.messages.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Colis224 Calculator: Erreur enregistrement', status, error);
                console.error('Réponse brute:', xhr.responseText);
                showMessage('error', colis224Calculator.messages.error);
            },
            complete: function() {
                isSaving = false;
                $btnSave.prop('disabled', false).text('Enregistrer le devis');
            }
        });
    }

    /**
     * Réinitialiser le calcul
     */
    function resetCalculation() {
        calculatedRate = null;
        $('#btn-save-quote').prop('disabled', true);
        $('#colis224-calculator-result').slideUp(200);
    }

    /**
     * Afficher un message
     */
    function showMessage(type, text) {
        var $message = $('#colis224-calculator-message');
        var iconClass = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';

        $message
            .removeClass('success error')
            .addClass(type)
            .html('<span class="dashicons ' + iconClass + '"></span> ' + text)
            .slideDown(200);

        // Auto-hide après 5 secondes pour les succès
        if (type === 'success') {
            setTimeout(function() {
                $message.slideUp(200);
            }, 5000);
        }
    }

    /**
     * Sauvegarder les données du formulaire dans localStorage
     */
    function saveFormData() {
        var $form = $('#colis224-calculator-form');
        var formData = {};

        $form.find('input, select, textarea').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            if (name && name !== 'calculator_nonce' && name !== '_wpnonce') {
                formData[name] = value;
            }
        });

        try {
            localStorage.setItem('colis224_calculator_form', JSON.stringify(formData));
            console.log('Colis224 Calculator: Données sauvegardées', formData);
        } catch (e) {
            console.warn('Colis224 Calculator: Impossible de sauvegarder dans localStorage', e);
        }
    }

    /**
     * Charger les données sauvegardées
     */
    function loadSavedFormData() {
        try {
            var savedData = localStorage.getItem('colis224_calculator_form');
            if (savedData) {
                var formData = JSON.parse(savedData);
                console.log('Colis224 Calculator: Restauration des données', formData);

                $.each(formData, function(name, value) {
                    var $field = $('#colis224-calculator-form').find('[name="' + name + '"]');
                    if ($field.length && value) {
                        $field.val(value);
                    }
                });
            }
        } catch (e) {
            console.warn('Colis224 Calculator: Impossible de charger depuis localStorage', e);
        }
    }

    /**
     * Effacer les données sauvegardées
     */
    function clearSavedFormData() {
        try {
            localStorage.removeItem('colis224_calculator_form');
            console.log('Colis224 Calculator: Données effacées');
        } catch (e) {
            console.warn('Colis224 Calculator: Impossible d\'effacer localStorage', e);
        }
    }

})(jQuery);
