/**
 * Système de Modales de Confirmation
 * Version 2.16.0 - Sprint 6
 *
 * Usage:
 * Colis224Confirm.show({
 *     type: 'danger', // danger, warning, info, success
 *     title: 'Supprimer le lot ?',
 *     message: 'Cette action est irréversible.',
 *     details: {
 *         'Lot': 'CNT-250113-0001',
 *         'Colis': '25 colis',
 *         'Valeur': '5,000,000 GNF'
 *     },
 *     confirmText: 'Supprimer',
 *     cancelText: 'Annuler',
 *     onConfirm: function() {
 *         // Action à exécuter
 *         return true; // ou Promise
 *     }
 * });
 */

(function($) {
    'use strict';

    // Template HTML de la modale
    const modalTemplate = `
        <div class="colis224-confirm-overlay">
            <div class="colis224-confirm-modal">
                <div class="colis224-confirm-header">
                    <div class="colis224-confirm-icon"></div>
                    <div class="colis224-confirm-text">
                        <h3 class="colis224-confirm-title"></h3>
                        <p class="colis224-confirm-message"></p>
                    </div>
                </div>
                <div class="colis224-confirm-details"></div>
                <div class="colis224-confirm-checkbox" style="display:none;">
                    <label>
                        <input type="checkbox" class="colis224-confirm-remember">
                        <span>Ne plus me demander</span>
                    </label>
                </div>
                <div class="colis224-confirm-footer">
                    <button type="button" class="colis224-confirm-btn colis224-confirm-btn-cancel"></button>
                    <button type="button" class="colis224-confirm-btn colis224-confirm-btn-confirm"></button>
                </div>
            </div>
        </div>
    `;

    // Icônes pour chaque type
    const icons = {
        danger: '⚠️',
        warning: '⚠️',
        info: 'ℹ️',
        success: '✓'
    };

    // Options par défaut
    const defaults = {
        type: 'warning',
        title: 'Confirmation',
        message: 'Êtes-vous sûr de vouloir continuer ?',
        details: null,
        confirmText: 'Confirmer',
        cancelText: 'Annuler',
        showRemember: false,
        rememberKey: null,
        onConfirm: null,
        onCancel: null
    };

    // Variable pour stocker la modale active
    let activeModal = null;

    // Fonction principale pour afficher une confirmation
    function show(options) {
        // Fusionner avec les options par défaut
        const opts = $.extend({}, defaults, options);

        // Vérifier si l'utilisateur a coché "ne plus me demander"
        if (opts.rememberKey && localStorage.getItem('colis224_skip_' + opts.rememberKey) === 'true') {
            // Appeler directement la callback de confirmation
            if (typeof opts.onConfirm === 'function') {
                return opts.onConfirm();
            }
            return Promise.resolve();
        }

        // Créer ou réutiliser la modale
        if (!activeModal || !document.body.contains(activeModal)) {
            const div = document.createElement('div');
            div.innerHTML = modalTemplate;
            activeModal = div.firstElementChild;
            document.body.appendChild(activeModal);
        }

        // Remplir le contenu
        const modal = activeModal;
        const icon = modal.querySelector('.colis224-confirm-icon');
        const title = modal.querySelector('.colis224-confirm-title');
        const message = modal.querySelector('.colis224-confirm-message');
        const detailsContainer = modal.querySelector('.colis224-confirm-details');
        const checkboxContainer = modal.querySelector('.colis224-confirm-checkbox');
        const cancelBtn = modal.querySelector('.colis224-confirm-btn-cancel');
        const confirmBtn = modal.querySelector('.colis224-confirm-btn-confirm');

        // Définir le type
        icon.className = `colis224-confirm-icon ${opts.type}`;
        icon.textContent = icons[opts.type] || icons.warning;

        // Définir titre et message
        title.textContent = opts.title;
        message.textContent = opts.message;

        // Définir les détails si fournis
        if (opts.details && typeof opts.details === 'object') {
            let detailsHTML = '';
            for (const [key, value] of Object.entries(opts.details)) {
                detailsHTML += `
                    <div class="colis224-confirm-detail-item">
                        <span class="colis224-confirm-detail-label">${escapeHtml(key)}</span>
                        <span class="colis224-confirm-detail-value">${escapeHtml(value)}</span>
                    </div>
                `;
            }
            detailsContainer.innerHTML = detailsHTML;
            detailsContainer.style.display = 'block';
        } else {
            detailsContainer.innerHTML = '';
            detailsContainer.style.display = 'none';
        }

        // Afficher/masquer checkbox "ne plus me demander"
        if (opts.showRemember && opts.rememberKey) {
            checkboxContainer.style.display = 'block';
        } else {
            checkboxContainer.style.display = 'none';
        }

        // Définir les textes des boutons
        cancelBtn.textContent = opts.cancelText;
        confirmBtn.textContent = opts.confirmText;

        // Définir le style du bouton de confirmation
        confirmBtn.className = `colis224-confirm-btn colis224-confirm-btn-confirm ${opts.type}`;

        // Promise pour gérer la réponse
        return new Promise((resolve, reject) => {
            // Gérer l'annulation
            const handleCancel = () => {
                closeModal();
                if (typeof opts.onCancel === 'function') {
                    opts.onCancel();
                }
                resolve(false);
            };

            // Gérer la confirmation
            const handleConfirm = async () => {
                // Vérifier la checkbox "ne plus me demander"
                if (opts.rememberKey) {
                    const checkbox = modal.querySelector('.colis224-confirm-remember');
                    if (checkbox && checkbox.checked) {
                        localStorage.setItem('colis224_skip_' + opts.rememberKey, 'true');
                    }
                }

                // Afficher le chargement
                confirmBtn.classList.add('loading');
                confirmBtn.disabled = true;
                cancelBtn.disabled = true;

                try {
                    let result = true;
                    if (typeof opts.onConfirm === 'function') {
                        result = await opts.onConfirm();
                    }

                    closeModal();
                    resolve(result);
                } catch (error) {
                    console.error('Erreur lors de la confirmation:', error);
                    confirmBtn.classList.remove('loading');
                    confirmBtn.disabled = false;
                    cancelBtn.disabled = false;
                    reject(error);
                }
            };

            // Supprimer les anciens listeners
            const newCancelBtn = cancelBtn.cloneNode(true);
            const newConfirmBtn = confirmBtn.cloneNode(true);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

            // Ajouter les nouveaux listeners
            newCancelBtn.addEventListener('click', handleCancel);
            newConfirmBtn.addEventListener('click', handleConfirm);

            // Fermer avec Escape
            const escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    handleCancel();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);

            // Fermer en cliquant sur l'overlay
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    handleCancel();
                }
            });

            // Afficher la modale
            setTimeout(() => {
                modal.classList.add('active');
                newConfirmBtn.focus();
            }, 10);
        });
    }

    // Fermer la modale
    function closeModal() {
        if (activeModal) {
            activeModal.classList.remove('active');
            setTimeout(() => {
                if (activeModal && activeModal.parentNode) {
                    activeModal.parentNode.removeChild(activeModal);
                    activeModal = null;
                }
            }, 300);
        }
    }

    // Échapper HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Réinitialiser les préférences "ne plus me demander"
    function resetRemember(key) {
        if (key) {
            localStorage.removeItem('colis224_skip_' + key);
        } else {
            // Réinitialiser toutes les préférences
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('colis224_skip_')) {
                    localStorage.removeItem(key);
                }
            }
        }
    }

    // API publique
    window.Colis224Confirm = {
        show: show,
        close: closeModal,
        resetRemember: resetRemember,

        // Raccourcis pour types communs
        danger: function(title, message, onConfirm) {
            return show({
                type: 'danger',
                title: title,
                message: message,
                onConfirm: onConfirm
            });
        },

        warning: function(title, message, onConfirm) {
            return show({
                type: 'warning',
                title: title,
                message: message,
                onConfirm: onConfirm
            });
        },

        info: function(title, message, onConfirm) {
            return show({
                type: 'info',
                title: title,
                message: message,
                onConfirm: onConfirm
            });
        }
    };

    // Ajouter automatiquement les confirmations aux éléments avec data-confirm
    $(document).on('click', '[data-confirm]', function(e) {
        e.preventDefault();
        const $el = $(this);
        const confirmMessage = $el.data('confirm');
        const confirmTitle = $el.data('confirm-title') || 'Confirmation';
        const confirmType = $el.data('confirm-type') || 'warning';

        Colis224Confirm.show({
            type: confirmType,
            title: confirmTitle,
            message: confirmMessage,
            onConfirm: function() {
                // Si c'est un lien, suivre le lien
                if ($el.is('a')) {
                    window.location.href = $el.attr('href');
                }
                // Si c'est un bouton de formulaire, soumettre le formulaire
                else if ($el.is('button') || $el.is('input[type="submit"]')) {
                    $el.closest('form').submit();
                }
            }
        });
    });

})(jQuery);
