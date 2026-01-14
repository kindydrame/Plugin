/**
 * JavaScript pour l'Espace Agent - Colis224
 * Version 2.18.2
 */

(function($) {
    'use strict';

    // Variables globales
    let uploadedParcelPhotos = [];
    let uploadedReceiptPhotos = [];
    let selectedClient = null;

    $(document).ready(function() {
        initAgentPortal();
    });

    function initAgentPortal() {
        // Gestion des onglets
        initTabs();

        // Autocomplete client
        initClientAutocomplete();

        // Boutons nouveau client
        initNewClientButtons();

        // Upload photos
        initPhotoUpload();

        // Preview et soumission
        initFormSubmission();

        // Gestion du changement de client
        initClientChange();
    }

    /**
     * Gestion des onglets
     */
    function initTabs() {
        $('.tab-btn').on('click', function() {
            const tabId = $(this).data('tab');

            // Activer l'onglet
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');

            // Afficher le contenu
            $('.tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
        });
    }

    /**
     * Autocomplete recherche client
     */
    function initClientAutocomplete() {
        let searchTimeout;

        $('#client_search').on('input', function() {
            const search = $(this).val().trim();

            // Réinitialiser
            $('#client_autocomplete_results').empty();
            selectedClient = null;
            $('#client_id').val('');

            if (search.length < 2) {
                return;
            }

            // Debounce
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchClients(search);
            }, 300);
        });

        // Fermer les résultats si clic ailleurs
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.autocomplete-wrapper').length) {
                $('#client_autocomplete_results').empty();
            }
        });
    }

    /**
     * Rechercher des clients via AJAX
     */
    function searchClients(search) {
        $.ajax({
            url: colis224_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'colis224_agent_search_clients',
                nonce: colis224_ajax.nonce,
                search: search
            },
            beforeSend: function() {
                $('#client_autocomplete_results').html('<div class="autocomplete-loading">Recherche...</div>');
            },
            success: function(response) {
                if (response.success && response.data.clients.length > 0) {
                    displayClientResults(response.data.clients);
                } else {
                    $('#client_autocomplete_results').html('<div class="autocomplete-empty">Aucun client trouvé</div>');
                }
            },
            error: function() {
                $('#client_autocomplete_results').html('<div class="autocomplete-error">Erreur de recherche</div>');
            }
        });
    }

    /**
     * Afficher les résultats de recherche client
     */
    function displayClientResults(clients) {
        const $results = $('#client_autocomplete_results');
        $results.empty();

        clients.forEach(function(client) {
            const $item = $('<div class="autocomplete-item"></div>');

            let html = '<strong>' + escapeHtml(client.name) + '</strong><br>';
            html += '<span class="client-phone">📞 ' + escapeHtml(client.phone) + '</span>';

            if (client.email) {
                html += '<span class="client-email">✉️ ' + escapeHtml(client.email) + '</span>';
            }

            if (client.client_code) {
                html += '<span class="client-code">🔖 ' + escapeHtml(client.client_code) + '</span>';
            }

            $item.html(html);
            $item.data('client', client);

            $item.on('click', function() {
                selectClient($(this).data('client'));
            });

            $results.append($item);
        });
    }

    /**
     * Sélectionner un client
     */
    function selectClient(client) {
        selectedClient = client;
        $('#client_id').val(client.id);
        $('#client_search').val(client.name);
        $('#client_autocomplete_results').empty();

        // Afficher les infos du client sélectionné
        let html = '<p><strong>' + escapeHtml(client.name) + '</strong></p>';
        html += '<p>📞 ' + escapeHtml(client.phone);

        if (client.email) {
            html += ' | ✉️ ' + escapeHtml(client.email);
        }

        if (client.client_code) {
            html += ' | 🔖 Code: ' + escapeHtml(client.client_code);
        }

        html += '</p>';

        if (client.address) {
            html += '<p>📍 ' + escapeHtml(client.address) + '</p>';
        }

        $('#selected-client-details').html(html);
        $('#selected-client-info').show();
        $('.autocomplete-wrapper').hide();
    }

    /**
     * Boutons nouveau client
     */
    function initNewClientButtons() {
        // Afficher le formulaire nouveau client
        $('#btn-new-client').on('click', function() {
            $('#new-client-form').slideDown();
            $('#new_client_name').focus();
        });

        // Annuler création client
        $('#btn-cancel-new-client').on('click', function() {
            $('#new-client-form').slideUp();
            $('#new-client-form input, #new-client-form textarea').val('');
        });

        // Sauvegarder nouveau client
        $('#btn-save-new-client').on('click', function() {
            saveNewClient();
        });
    }

    /**
     * Sauvegarder un nouveau client via AJAX
     */
    function saveNewClient() {
        const name = $('#new_client_name').val().trim();
        const phone = $('#new_client_phone').val().trim();
        const email = $('#new_client_email').val().trim();
        const address = $('#new_client_address').val().trim();
        const id_card = $('#new_client_id_card').val().trim();

        if (!name || !phone) {
            alert('❌ Nom et téléphone sont obligatoires');
            return;
        }

        const $btn = $('#btn-save-new-client');
        const originalText = $btn.html();

        $.ajax({
            url: colis224_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'colis224_agent_create_client',
                nonce: colis224_ajax.nonce,
                name: name,
                phone: phone,
                email: email,
                address: address,
                id_card: id_card
            },
            beforeSend: function() {
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Création...');
            },
            success: function(response) {
                if (response.success) {
                    // Sélectionner le client nouvellement créé
                    selectClient(response.data.client);

                    // Masquer le formulaire et réinitialiser
                    $('#new-client-form').slideUp();
                    $('#new-client-form input, #new-client-form textarea').val('');

                    showNotification('success', response.data.message);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', '❌ Erreur lors de la création du client');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Changer de client
     */
    function initClientChange() {
        $('#btn-change-client').on('click', function() {
            selectedClient = null;
            $('#client_id').val('');
            $('#client_search').val('');
            $('#selected-client-info').hide();
            $('.autocomplete-wrapper').show();
            $('#client_search').focus();
        });
    }

    /**
     * Upload et preview des photos
     */
    function initPhotoUpload() {
        // Photos du colis
        $('#parcel_photos').on('change', function(e) {
            handlePhotoUpload(e.target.files, 'parcel');
        });

        // Photos du reçu
        $('#receipt_photos').on('change', function(e) {
            handlePhotoUpload(e.target.files, 'receipt');
        });
    }

    /**
     * Gérer l'upload de photos
     */
    function handlePhotoUpload(files, type) {
        const previewContainer = type === 'parcel' ? '#parcel_photos_preview' : '#receipt_photos_preview';
        const photoArray = type === 'parcel' ? uploadedParcelPhotos : uploadedReceiptPhotos;

        // Limiter à 5 photos max par type
        if (photoArray.length + files.length > 5) {
            alert('❌ Maximum 5 photos par catégorie');
            return;
        }

        Array.from(files).forEach(function(file) {
            // Vérifier que c'est une image
            if (!file.type.match('image.*')) {
                showNotification('error', '❌ Seules les images sont acceptées');
                return;
            }

            // Vérifier la taille (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showNotification('error', '❌ Taille maximum: 5MB par photo');
                return;
            }

            // Upload via AJAX
            uploadPhoto(file, type, previewContainer, photoArray);
        });
    }

    /**
     * Upload une photo via AJAX
     */
    function uploadPhoto(file, type, previewContainer, photoArray) {
        const formData = new FormData();
        formData.append('action', 'colis224_agent_upload_photos');
        formData.append('nonce', colis224_ajax.nonce);
        formData.append('file', file);

        // Créer un élément de preview temporaire
        const photoId = 'photo-' + Date.now();
        const $preview = $('<div class="photo-preview-item" id="' + photoId + '"></div>');
        $preview.html('<div class="photo-loading"><span class="dashicons dashicons-update spin"></span></div>');
        $(previewContainer).append($preview);

        $.ajax({
            url: colis224_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    photoArray.push(response.data.url);

                    // Afficher la preview
                    $preview.html(
                        '<img src="' + response.data.url + '" alt="Photo">' +
                        '<button type="button" class="btn-remove-photo" data-url="' + response.data.url + '" data-type="' + type + '">' +
                        '<span class="dashicons dashicons-no"></span>' +
                        '</button>'
                    );

                    // Bouton supprimer
                    $preview.find('.btn-remove-photo').on('click', function() {
                        removePhoto($(this).data('url'), $(this).data('type'), $preview);
                    });
                } else {
                    $preview.remove();
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                $preview.remove();
                showNotification('error', '❌ Erreur lors de l\'upload');
            }
        });
    }

    /**
     * Supprimer une photo
     */
    function removePhoto(url, type, $element) {
        const photoArray = type === 'parcel' ? uploadedParcelPhotos : uploadedReceiptPhotos;
        const index = photoArray.indexOf(url);

        if (index > -1) {
            photoArray.splice(index, 1);
        }

        $element.fadeOut(300, function() {
            $(this).remove();
        });
    }

    /**
     * Initialiser la soumission du formulaire
     */
    function initFormSubmission() {
        // Bouton preview
        $('#btn-preview-parcel').on('click', function(e) {
            e.preventDefault();

            if (validateForm()) {
                showPreviewModal();
            }
        });

        // Bouton modifier dans le modal
        $('#btn-edit-parcel').on('click', function() {
            closePreviewModal();
        });

        // Fermer le modal
        $('.colis224-modal-close').on('click', function() {
            closePreviewModal();
        });

        // Clic en dehors du modal
        $('#modal-parcel-preview').on('click', function(e) {
            if ($(e.target).is('#modal-parcel-preview')) {
                closePreviewModal();
            }
        });

        // Bouton confirmer et soumettre
        $('#btn-confirm-submit-parcel').on('click', function() {
            submitParcel();
        });
    }

    /**
     * Valider le formulaire
     */
    function validateForm() {
        const errors = [];

        // Client
        if (!$('#client_id').val()) {
            errors.push('Veuillez sélectionner ou créer un client');
        }

        // Destinataire
        if (!$('#recipient_name').val().trim()) {
            errors.push('Nom du destinataire requis');
        }
        if (!$('#recipient_phone').val().trim()) {
            errors.push('Téléphone du destinataire requis');
        }
        if (!$('#recipient_address').val().trim()) {
            errors.push('Adresse du destinataire requise');
        }

        // Colis
        if (!$('#origin_country_id').val()) {
            errors.push('Pays de provenance requis');
        }
        if (!$('#destination_country_id').val()) {
            errors.push('Pays de destination requis');
        }
        if (!$('#parcel_nature').val().trim()) {
            errors.push('Nature du colis requise');
        }
        if (!$('#weight').val() || parseFloat($('#weight').val()) <= 0) {
            errors.push('Poids du colis requis');
        }

        // Prix
        const priceEur = parseFloat($('#price_eur').val()) || 0;
        const priceGnf = parseFloat($('#price_gnf').val()) || 0;

        if (priceEur <= 0 && priceGnf <= 0) {
            errors.push('Au moins un tarif (EUR ou GNF) est requis');
        }

        // Paiement
        if (!$('#payment_status').val()) {
            errors.push('État de paiement requis');
        }

        if (errors.length > 0) {
            alert('❌ Erreurs de validation:\n\n' + errors.join('\n'));
            return false;
        }

        return true;
    }

    /**
     * Afficher le modal de preview
     */
    function showPreviewModal() {
        const formData = collectFormData();
        const html = generatePreviewHTML(formData);

        $('#parcel-preview-content').html(html);
        $('#modal-parcel-preview').fadeIn(300);
        $('body').addClass('modal-open');
    }

    /**
     * Fermer le modal de preview
     */
    function closePreviewModal() {
        $('#modal-parcel-preview').fadeOut(300);
        $('body').removeClass('modal-open');
    }

    /**
     * Collecter les données du formulaire
     */
    function collectFormData() {
        return {
            // Client
            client: selectedClient,
            invoice_number: $('#invoice_number').val(),
            tracking_number: $('#tracking_number').val(),

            // Expéditeur
            sender_name: $('#sender_name').val(),
            sender_phone: $('#sender_phone').val(),
            sender_id_card: $('#sender_id_card').val(),
            sender_email: $('#sender_email').val(),

            // Destinataire
            recipient_name: $('#recipient_name').val(),
            recipient_phone: $('#recipient_phone').val(),
            recipient_email: $('#recipient_email').val(),
            recipient_address: $('#recipient_address').val(),

            // Colis
            origin_country: $('#origin_country_id option:selected').text(),
            destination_country: $('#destination_country_id option:selected').text(),
            parcel_nature: $('#parcel_nature').val(),
            weight: $('#weight').val(),
            price_eur: $('#price_eur').val(),
            price_gnf: $('#price_gnf').val(),
            payment_status: $('#payment_status').val(),
            estimated_delivery_date: $('#estimated_delivery_date').val(),
            transport_mode: $('#transport_mode_id option:selected').text(),

            // Photos
            parcel_photos: uploadedParcelPhotos,
            receipt_photos: uploadedReceiptPhotos,

            // Notes
            notes: $('#notes').val()
        };
    }

    /**
     * Générer le HTML du récapitulatif
     */
    function generatePreviewHTML(data) {
        let html = '<div class="preview-sections">';

        // Section Client
        html += '<div class="preview-section">';
        html += '<h4>👤 Client</h4>';
        html += '<table class="preview-table">';
        html += '<tr><td><strong>Nom:</strong></td><td>' + escapeHtml(data.client.name) + '</td></tr>';
        html += '<tr><td><strong>Téléphone:</strong></td><td>' + escapeHtml(data.client.phone) + '</td></tr>';
        if (data.client.email) {
            html += '<tr><td><strong>Email:</strong></td><td>' + escapeHtml(data.client.email) + '</td></tr>';
        }
        if (data.client.client_code) {
            html += '<tr><td><strong>Code PA:</strong></td><td>' + escapeHtml(data.client.client_code) + '</td></tr>';
        }
        html += '<tr><td><strong>N° Facture:</strong></td><td><span class="highlight">' + escapeHtml(data.invoice_number) + '</span></td></tr>';
        html += '<tr><td><strong>N° Suivi:</strong></td><td><span class="highlight">' + escapeHtml(data.tracking_number) + '</span></td></tr>';
        html += '</table>';
        html += '</div>';

        // Section Expéditeur (si rempli)
        if (data.sender_name || data.sender_phone) {
            html += '<div class="preview-section">';
            html += '<h4>📤 Expéditeur</h4>';
            html += '<table class="preview-table">';
            if (data.sender_name) html += '<tr><td><strong>Nom:</strong></td><td>' + escapeHtml(data.sender_name) + '</td></tr>';
            if (data.sender_phone) html += '<tr><td><strong>Téléphone:</strong></td><td>' + escapeHtml(data.sender_phone) + '</td></tr>';
            if (data.sender_id_card) html += '<tr><td><strong>N° Carte ID:</strong></td><td>' + escapeHtml(data.sender_id_card) + '</td></tr>';
            if (data.sender_email) html += '<tr><td><strong>Email:</strong></td><td>' + escapeHtml(data.sender_email) + '</td></tr>';
            html += '</table>';
            html += '</div>';
        }

        // Section Destinataire
        html += '<div class="preview-section">';
        html += '<h4>📥 Destinataire</h4>';
        html += '<table class="preview-table">';
        html += '<tr><td><strong>Nom:</strong></td><td>' + escapeHtml(data.recipient_name) + '</td></tr>';
        html += '<tr><td><strong>Téléphone:</strong></td><td>' + escapeHtml(data.recipient_phone) + '</td></tr>';
        if (data.recipient_email) html += '<tr><td><strong>Email:</strong></td><td>' + escapeHtml(data.recipient_email) + '</td></tr>';
        html += '<tr><td><strong>Adresse:</strong></td><td>' + escapeHtml(data.recipient_address) + '</td></tr>';
        html += '</table>';
        html += '</div>';

        // Section Colis
        html += '<div class="preview-section">';
        html += '<h4>📦 Détails du Colis</h4>';
        html += '<table class="preview-table">';
        html += '<tr><td><strong>Provenance:</strong></td><td>' + escapeHtml(data.origin_country) + '</td></tr>';
        html += '<tr><td><strong>Destination:</strong></td><td>' + escapeHtml(data.destination_country) + '</td></tr>';
        html += '<tr><td><strong>Nature:</strong></td><td>' + escapeHtml(data.parcel_nature) + '</td></tr>';
        html += '<tr><td><strong>Poids:</strong></td><td>' + escapeHtml(data.weight) + ' kg</td></tr>';

        if (data.price_eur) {
            html += '<tr><td><strong>Tarif EUR:</strong></td><td>' + escapeHtml(data.price_eur) + ' €</td></tr>';
        }
        if (data.price_gnf) {
            html += '<tr><td><strong>Tarif GNF:</strong></td><td>' + escapeHtml(data.price_gnf) + ' GNF</td></tr>';
        }

        html += '<tr><td><strong>Paiement:</strong></td><td><span class="badge badge-payment">' + escapeHtml(data.payment_status) + '</span></td></tr>';

        if (data.estimated_delivery_date) {
            html += '<tr><td><strong>Délai estimé:</strong></td><td>' + formatDate(data.estimated_delivery_date) + '</td></tr>';
        }
        if (data.transport_mode && data.transport_mode !== 'Sélectionner') {
            html += '<tr><td><strong>Transport:</strong></td><td>' + escapeHtml(data.transport_mode) + '</td></tr>';
        }
        html += '</table>';
        html += '</div>';

        // Section Photos
        if (data.parcel_photos.length > 0 || data.receipt_photos.length > 0) {
            html += '<div class="preview-section">';
            html += '<h4>📸 Photos</h4>';

            if (data.parcel_photos.length > 0) {
                html += '<p><strong>Photos du colis (' + data.parcel_photos.length + '):</strong></p>';
                html += '<div class="preview-photos">';
                data.parcel_photos.forEach(function(url) {
                    html += '<img src="' + url + '" alt="Photo colis" class="preview-photo-thumb">';
                });
                html += '</div>';
            }

            if (data.receipt_photos.length > 0) {
                html += '<p><strong>Photos du reçu (' + data.receipt_photos.length + '):</strong></p>';
                html += '<div class="preview-photos">';
                data.receipt_photos.forEach(function(url) {
                    html += '<img src="' + url + '" alt="Photo reçu" class="preview-photo-thumb">';
                });
                html += '</div>';
            }

            html += '</div>';
        }

        // Section Notes
        if (data.notes) {
            html += '<div class="preview-section">';
            html += '<h4>📝 Notes</h4>';
            html += '<p>' + escapeHtml(data.notes).replace(/\n/g, '<br>') + '</p>';
            html += '</div>';
        }

        html += '</div>';

        // Avertissement
        html += '<div class="preview-warning">';
        html += '<p><strong>⚠️ Attention:</strong> Une fois soumis, seul l\'administrateur pourra modifier ces informations.</p>';
        html += '</div>';

        return html;
    }

    /**
     * Soumettre le colis
     */
    function submitParcel() {
        const $btn = $('#btn-confirm-submit-parcel');
        const originalText = $btn.html();

        // Préparer les données
        const formData = {
            action: 'colis224_agent_create_parcel',
            nonce: colis224_ajax.nonce,

            // Client
            client_id: $('#client_id').val(),
            invoice_number: $('#invoice_number').val(),
            tracking_number: $('#tracking_number').val(),

            // Expéditeur
            sender_name: $('#sender_name').val(),
            sender_phone: $('#sender_phone').val(),
            sender_id_card: $('#sender_id_card').val(),
            sender_email: $('#sender_email').val(),

            // Destinataire
            recipient_name: $('#recipient_name').val(),
            recipient_phone: $('#recipient_phone').val(),
            recipient_email: $('#recipient_email').val(),
            recipient_address: $('#recipient_address').val(),

            // Colis
            origin_country_id: $('#origin_country_id').val(),
            destination_country_id: $('#destination_country_id').val(),
            transport_mode_id: $('#transport_mode_id').val(),
            parcel_nature: $('#parcel_nature').val(),
            weight: $('#weight').val(),
            price_eur: $('#price_eur').val(),
            price_gnf: $('#price_gnf').val(),
            payment_status: $('#payment_status').val(),
            estimated_delivery_date: $('#estimated_delivery_date').val(),

            // Photos (joindre les URLs en CSV)
            parcel_photos: uploadedParcelPhotos.join(','),
            receipt_photos: uploadedReceiptPhotos.join(','),

            // Notes
            notes: $('#notes').val()
        };

        $.ajax({
            url: colis224_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Soumission en cours...');
            },
            success: function(response) {
                if (response.success) {
                    closePreviewModal();

                    // Afficher le succès
                    showSuccessMessage(response.data);

                    // Réinitialiser le formulaire
                    resetForm();

                    // Rafraîchir les stats
                    refreshStats();
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', '❌ Erreur lors de la soumission');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Afficher un message de succès
     */
    function showSuccessMessage(data) {
        let html = '<div class="colis224-success-message">';
        html += '<div class="success-icon">✅</div>';
        html += '<h3>Colis Soumis avec Succès !</h3>';
        html += '<p>' + data.message + '</p>';
        html += '<div class="success-details">';
        html += '<p><strong>N° Facture:</strong> ' + escapeHtml(data.invoice_number) + '</p>';
        html += '<p><strong>N° Suivi:</strong> ' + escapeHtml(data.tracking_number) + '</p>';
        html += '</div>';
        html += '<p class="success-note">Le colis sera visible après validation par l\'administrateur.</p>';
        html += '</div>';

        // Insérer avant le formulaire
        $('.agent-form').prepend(html);

        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);

        // Supprimer après 10 secondes
        setTimeout(function() {
            $('.colis224-success-message').fadeOut(500, function() {
                $(this).remove();
            });
        }, 10000);
    }

    /**
     * Réinitialiser le formulaire
     */
    function resetForm() {
        // Réinitialiser les champs
        $('#form-agent-create-parcel')[0].reset();

        // Réinitialiser le client
        selectedClient = null;
        $('#client_id').val('');
        $('#client_search').val('');
        $('#selected-client-info').hide();
        $('.autocomplete-wrapper').show();

        // Réinitialiser les photos
        uploadedParcelPhotos = [];
        uploadedReceiptPhotos = [];
        $('#parcel_photos_preview').empty();
        $('#receipt_photos_preview').empty();

        // Générer nouveaux numéros
        // Note: Les numéros seront régénérés au prochain chargement de page
    }

    /**
     * Rafraîchir les statistiques
     */
    function refreshStats() {
        $.ajax({
            url: colis224_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'colis224_agent_get_pending',
                nonce: colis224_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour les stats
                    $('.stat-pending .stat-value').text(response.data.parcels);
                    $('.stat-departures .stat-value').text(response.data.departures);

                    // Mettre à jour le compteur de l'onglet
                    $('.tab-btn[data-tab="pending"]').html(
                        '<span class="dashicons dashicons-clock"></span> En Attente (' + response.data.total + ')'
                    );
                }
            }
        });
    }

    /**
     * Afficher une notification
     */
    function showNotification(type, message) {
        const $notification = $('<div class="colis224-notification colis224-notification-' + type + '"></div>');
        $notification.html(message);

        $('body').append($notification);

        setTimeout(function() {
            $notification.addClass('show');
        }, 100);

        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 5000);
    }

    /**
     * Échapper le HTML
     */
    function escapeHtml(text) {
        if (!text) return '';

        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Formater une date
     */
    function formatDate(dateString) {
        if (!dateString) return '';

        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };

        return date.toLocaleDateString('fr-FR', options);
    }

})(jQuery);
