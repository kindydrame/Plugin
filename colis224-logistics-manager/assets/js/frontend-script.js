/**
 * Colis224 Logistics Manager - Scripts Frontend
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // === SUIVI DE COLIS ===
        $('#colis224-tracking-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $result = $('#colis224-tracking-result');
            var trackingNumber = $('#tracking_number').val().trim();

            if (trackingNumber === '') {
                alert('Veuillez entrer un numéro de suivi');
                return;
            }

            // Désactiver le bouton et afficher le loader
            $button.prop('disabled', true);
            var originalText = $button.html();
            $button.html('<span class="colis224-loader"></span> Recherche...');

            // Vider le résultat précédent
            $result.html('');

            // Requête AJAX
            $.ajax({
                url: colis224Frontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'colis224_track_parcel',
                    nonce: colis224Frontend.nonce,
                    tracking_number: trackingNumber
                },
                success: function(response) {
                    if (response.success) {
                        $result.html(response.data.html);
                        // Scroll vers le résultat
                        $('html, body').animate({
                            scrollTop: $result.offset().top - 100
                        }, 500);
                    } else {
                        $result.html(
                            '<div class="colis224-message colis224-message-error">' +
                            '<strong>Erreur:</strong> ' + response.data.message +
                            '</div>'
                        );
                    }
                },
                error: function() {
                    $result.html(
                        '<div class="colis224-message colis224-message-error">' +
                        '<strong>Erreur:</strong> Une erreur est survenue. Veuillez réessayer.' +
                        '</div>'
                    );
                },
                complete: function() {
                    // Réactiver le bouton
                    $button.prop('disabled', false);
                    $button.html(originalText);
                }
            });
        });

        // === CONNEXION CLIENT ===
        $('#colis224-client-login-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $message = $('#colis224-login-message');
            var phone = $('#client_phone').val().trim();

            if (phone === '') {
                $message.html(
                    '<div class="colis224-message colis224-message-error">' +
                    'Veuillez entrer votre numéro de téléphone' +
                    '</div>'
                );
                return;
            }

            // Désactiver le bouton
            $button.prop('disabled', true);
            var originalText = $button.html();
            $button.html('<span class="colis224-loader"></span> Connexion...');

            // Vider les messages précédents
            $message.html('');

            // Requête AJAX
            $.ajax({
                url: colis224Frontend.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'colis224_client_login',
                    nonce: colis224Frontend.nonce,
                    redirect_url: window.location.href,
                    client_phone: phone,
                    client_password: $('#client_password').val()
                },
                success: function(response) {
                    console.log('Réponse serveur:', response);
                    if (response.success) {
                        // Afficher le message de succès
                        $message.html(
                            '<div class="colis224-message colis224-message-success">' +
                            response.data.message +
                            '</div>'
                        );

                        // Redirection immédiate avec reload forcé
                        setTimeout(function() {
                            // Forcer le rechargement complet de la page pour charger la session
                            window.location.reload(true);
                        }, 800);
                    } else {
                        // Afficher le message d'erreur
                        $message.html(
                            '<div class="colis224-message colis224-message-error">' +
                            '<strong>Erreur:</strong> ' + response.data.message +
                            '</div>'
                        );
                        // Réactiver le bouton en cas d'erreur
                        $button.prop('disabled', false);
                        $button.html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', status, error);
                    console.error('Réponse brute:', xhr.responseText);
                    $message.html(
                        '<div class="colis224-message colis224-message-error">' +
                        '<strong>Erreur:</strong> Une erreur est survenue. Veuillez réessayer.' +
                        '</div>'
                    );
                    $button.prop('disabled', false);
                    $button.html(originalText);
                }
            });
        });

        // === AUTO-COMPLÉTION DU CHAMP DE RECHERCHE ===
        $('#tracking_number').on('input', function() {
            var value = $(this).val().toUpperCase();
            $(this).val(value);
        });

        // === SMOOTH SCROLL POUR LES ANCRES ===
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 80
                }, 800);
            }
        });

    });

})(jQuery);

/**
 * Système de modales modernes
 */
var Colis224Modal = {
    /**
     * Créer une modale moderne
     */
    create: function(options) {
        var defaults = {
            title: 'Détails',
            content: '',
            closeButton: true,
            footer: '',
            onClose: null
        };

        var settings = $.extend({}, defaults, options);

        // Créer la structure HTML de la modale
        var modalHTML = '<div class="colis224-modal" id="colis224-modal-' + Date.now() + '">' +
            '<div class="colis224-modal-content">' +
                '<div class="colis224-modal-header">' +
                    '<h3>' + settings.title + '</h3>' +
                    (settings.closeButton ? '<button class="colis224-modal-close" aria-label="Fermer">&times;</button>' : '') +
                '</div>' +
                '<div class="colis224-modal-body">' +
                    settings.content +
                '</div>' +
                (settings.footer ? '<div class="colis224-modal-footer">' + settings.footer + '</div>' : '') +
            '</div>' +
        '</div>';

        // Ajouter au DOM
        $('body').append(modalHTML);

        var $modal = $('.colis224-modal').last();

        // Ouvrir la modale
        setTimeout(function() {
            $modal.addClass('active');
        }, 10);

        // Gérer la fermeture
        $modal.find('.colis224-modal-close').on('click', function() {
            Colis224Modal.close($modal, settings.onClose);
        });

        // Fermer en cliquant sur le fond
        $modal.on('click', function(e) {
            if ($(e.target).hasClass('colis224-modal')) {
                Colis224Modal.close($modal, settings.onClose);
            }
        });

        // Fermer avec la touche Escape
        $(document).on('keydown.colis224modal', function(e) {
            if (e.key === 'Escape') {
                Colis224Modal.close($modal, settings.onClose);
            }
        });

        return $modal;
    },

    /**
     * Fermer une modale
     */
    close: function($modal, callback) {
        $modal.removeClass('active');
        setTimeout(function() {
            $modal.remove();
            $(document).off('keydown.colis224modal');
            if (typeof callback === 'function') {
                callback();
            }
        }, 300);
    }
};

/**
 * Fonction globale pour voir les détails d'un colis
 */
function colis224ViewParcelDetails(parcelId) {
    // Afficher un loader
    var $modal = Colis224Modal.create({
        title: '<span class="dashicons dashicons-admin-page"></span> Détails du colis',
        content: '<div style="text-align: center; padding: 40px;"><span class="colis224-loader"></span><p style="margin-top: 15px;">Chargement des détails...</p></div>'
    });

    // Requête AJAX pour récupérer les détails
    $.ajax({
        url: colis224Frontend.ajaxurl,
        type: 'POST',
        data: {
            action: 'colis224_get_parcel_details',
            nonce: colis224Frontend.nonce,
            parcel_id: parcelId
        },
        success: function(response) {
            if (response.success) {
                // Créer un élément temporaire pour décoder les entités HTML
                var tempDiv = document.createElement('div');
                tempDiv.innerHTML = response.data.html;
                // Mettre à jour le contenu de la modale
                $modal.find('.colis224-modal-body').html(tempDiv.innerHTML);
            } else {
                var errorMsg = response.data.message || 'Une erreur est survenue';
                $modal.find('.colis224-modal-body').html(
                    '<div class="colis224-message colis224-message-error">' +
                    '<strong>Erreur:</strong> ' + $('<div/>').text(errorMsg).html() +
                    '</div>'
                );
            }
        },
        error: function() {
            $modal.find('.colis224-modal-body').html(
                '<div class="colis224-message colis224-message-error">' +
                    '<strong>Erreur:</strong> Impossible de charger les détails. Veuillez réessayer.' +
                '</div>'
            );
        }
    });
}

/**
 * Fonction pour afficher les détails d'un ticket
 */
function colis224ViewTicketDetails(ticketId) {
    var $modal = Colis224Modal.create({
        title: '<span class="dashicons dashicons-sos"></span> Détails du ticket #' + ticketId,
        content: '<div style="text-align: center; padding: 40px;"><span class="colis224-loader"></span><p style="margin-top: 15px;">Chargement des détails...</p></div>'
    });

    // Requête AJAX pour récupérer les détails du ticket
    $.ajax({
        url: colis224Frontend.ajaxurl,
        type: 'POST',
        data: {
            action: 'colis224_get_ticket_details',
            nonce: colis224Frontend.nonce,
            ticket_id: ticketId
        },
        success: function(response) {
            if (response.success) {
                // Créer un élément temporaire pour décoder les entités HTML
                var tempDiv = document.createElement('div');
                tempDiv.innerHTML = response.data.html;
                // Mettre à jour le contenu de la modale
                $modal.find('.colis224-modal-body').html(tempDiv.innerHTML);
            } else {
                var errorMsg = response.data.message || 'Une erreur est survenue';
                $modal.find('.colis224-modal-body').html(
                    '<div class="colis224-message colis224-message-error">' +
                    '<strong>Erreur:</strong> ' + $('<div/>').text(errorMsg).html() +
                    '</div>'
                );
            }
        },
        error: function() {
            $modal.find('.colis224-modal-body').html(
                '<div class="colis224-message colis224-message-error">' +
                    '<strong>Erreur:</strong> Impossible de charger les détails. Veuillez réessayer.' +
                '</div>'
            );
        }
    });
}
