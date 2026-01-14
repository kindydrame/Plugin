/**
 * Système de Notifications Toast Moderne
 * Version 2.16.0 - Sprint 6
 *
 * Usage:
 * Colis224Toast.success('Opération réussie !');
 * Colis224Toast.error('Une erreur est survenue');
 * Colis224Toast.warning('Attention : migration requise');
 * Colis224Toast.info('5 nouveaux colis');
 *
 * Avec titre et message:
 * Colis224Toast.success('Succès', 'Le lot a été créé avec succès');
 *
 * Avec durée personnalisée:
 * Colis224Toast.info('Information', 'Message', 8000);
 */

(function($) {
    'use strict';

    // Création du container si nécessaire
    function ensureContainer() {
        let container = document.querySelector('.colis224-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'colis224-toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    // Icônes pour chaque type
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    // Titres par défaut
    const defaultTitles = {
        success: 'Succès',
        error: 'Erreur',
        warning: 'Attention',
        info: 'Information'
    };

    // Fonction principale pour afficher un toast
    function showToast(type, titleOrMessage, message, duration) {
        // Si message n'est pas fourni, titleOrMessage est le message
        let title, msg;
        if (message === undefined) {
            title = defaultTitles[type];
            msg = titleOrMessage;
        } else {
            title = titleOrMessage;
            msg = message;
        }

        // Durée par défaut : 5 secondes
        const toastDuration = duration || 5000;

        // Créer le container
        const container = ensureContainer();

        // Créer l'élément toast
        const toast = document.createElement('div');
        toast.className = `colis224-toast ${type}`;
        toast.innerHTML = `
            <div class="colis224-toast-icon">${icons[type]}</div>
            <div class="colis224-toast-content">
                <div class="colis224-toast-title">${escapeHtml(title)}</div>
                ${msg ? `<div class="colis224-toast-message">${escapeHtml(msg)}</div>` : ''}
            </div>
            <button type="button" class="colis224-toast-close" aria-label="Fermer">×</button>
        `;

        // Ajouter au container
        container.appendChild(toast);

        // Animation d'entrée
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Gestion du bouton de fermeture
        const closeBtn = toast.querySelector('.colis224-toast-close');
        closeBtn.addEventListener('click', () => {
            removeToast(toast);
        });

        // Auto-suppression après la durée spécifiée
        setTimeout(() => {
            removeToast(toast);
        }, toastDuration);

        // Son (optionnel - commenté par défaut)
        // playNotificationSound(type);
    }

    // Supprimer un toast
    function removeToast(toast) {
        if (!toast || !toast.parentNode) return;

        toast.classList.remove('show');
        toast.classList.add('hide');

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 400);
    }

    // Échapper HTML pour sécurité
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Jouer un son (optionnel)
    function playNotificationSound(type) {
        // Implémentation future si besoin
        // const audio = new Audio('/path/to/sound.mp3');
        // audio.play();
    }

    // API publique
    window.Colis224Toast = {
        success: function(titleOrMessage, message, duration) {
            showToast('success', titleOrMessage, message, duration);
        },

        error: function(titleOrMessage, message, duration) {
            showToast('error', titleOrMessage, message, duration);
        },

        warning: function(titleOrMessage, message, duration) {
            showToast('warning', titleOrMessage, message, duration);
        },

        info: function(titleOrMessage, message, duration) {
            showToast('info', titleOrMessage, message, duration);
        },

        // Alias courts
        s: function(msg) { this.success(msg); },
        e: function(msg) { this.error(msg); },
        w: function(msg) { this.warning(msg); },
        i: function(msg) { this.info(msg); }
    };

    // Intégration avec les messages WordPress existants
    $(document).ready(function() {
        // Convertir les messages WordPress en toasts
        $('.notice.notice-success, .updated').each(function() {
            const message = $(this).find('p').text().trim();
            if (message) {
                Colis224Toast.success(message);
                $(this).hide();
            }
        });

        $('.notice.notice-error, .error').each(function() {
            const message = $(this).find('p').text().trim();
            if (message && !message.includes('Erreur de la base de données')) {
                Colis224Toast.error(message);
                $(this).hide();
            }
        });

        $('.notice.notice-warning').each(function() {
            const message = $(this).find('p').text().trim();
            if (message) {
                Colis224Toast.warning(message);
                $(this).hide();
            }
        });

        $('.notice.notice-info').each(function() {
            const message = $(this).find('p').text().trim();
            if (message) {
                Colis224Toast.info(message);
                $(this).hide();
            }
        });
    });

    // Raccourcis clavier (Ctrl+Shift+T pour tester)
    $(document).keydown(function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'T') {
            e.preventDefault();
            Colis224Toast.success('Système de notifications', 'Les toasts fonctionnent correctement !');
        }
    });

})(jQuery);
