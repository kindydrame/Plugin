/**
 * Système de Tooltips d'Aide
 * Version 2.16.0 - Sprint 6
 *
 * Usage HTML:
 * <span class="colis224-help-icon" data-tooltip="Texte d'aide">?</span>
 * <span class="colis224-help-icon" data-tooltip-title="Titre" data-tooltip="Message">?</span>
 * <span class="colis224-help-icon" data-tooltip="Aide" data-tooltip-position="top">?</span>
 *
 * Usage JavaScript:
 * Colis224Tooltip.show(element, 'Message d'aide', 'top');
 * Colis224Tooltip.hide();
 */

(function($) {
    'use strict';

    // Élément tooltip actif
    let activeTooltip = null;
    let hideTimeout = null;

    // Créer un tooltip
    function createTooltip(text, title, type, position) {
        const tooltip = document.createElement('div');
        tooltip.className = `colis224-tooltip ${type || ''} ${position || 'top'}`;

        if (title) {
            tooltip.innerHTML = `
                <div class="colis224-tooltip-title">${escapeHtml(title)}</div>
                <div>${escapeHtml(text)}</div>
            `;
        } else {
            tooltip.textContent = text;
        }

        return tooltip;
    }

    // Positionner le tooltip
    function positionTooltip(tooltip, target, position) {
        const rect = target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        let top, left;

        switch (position) {
            case 'top':
                top = rect.top + scrollTop - tooltipRect.height - 10;
                left = rect.left + scrollLeft + (rect.width / 2) - (tooltipRect.width / 2);
                break;

            case 'bottom':
                top = rect.bottom + scrollTop + 10;
                left = rect.left + scrollLeft + (rect.width / 2) - (tooltipRect.width / 2);
                break;

            case 'left':
                top = rect.top + scrollTop + (rect.height / 2) - (tooltipRect.height / 2);
                left = rect.left + scrollLeft - tooltipRect.width - 10;
                break;

            case 'right':
                top = rect.top + scrollTop + (rect.height / 2) - (tooltipRect.height / 2);
                left = rect.right + scrollLeft + 10;
                break;

            default:
                // Par défaut: top
                top = rect.top + scrollTop - tooltipRect.height - 10;
                left = rect.left + scrollLeft + (rect.width / 2) - (tooltipRect.width / 2);
        }

        // S'assurer que le tooltip reste dans la fenêtre
        const padding = 10;
        if (left < padding) {
            left = padding;
        } else if (left + tooltipRect.width > window.innerWidth - padding) {
            left = window.innerWidth - tooltipRect.width - padding;
        }

        if (top < padding) {
            // Si pas de place en haut, afficher en bas
            top = rect.bottom + scrollTop + 10;
            tooltip.classList.remove('top');
            tooltip.classList.add('bottom');
        }

        tooltip.style.top = top + 'px';
        tooltip.style.left = left + 'px';
    }

    // Afficher un tooltip
    function show(target, text, options) {
        // Options par défaut
        const opts = typeof options === 'string' ? { position: options } : (options || {});
        const position = opts.position || 'top';
        const title = opts.title || '';
        const type = opts.type || '';
        const delay = opts.delay !== undefined ? opts.delay : 200;

        // Annuler le timeout de masquage si existant
        if (hideTimeout) {
            clearTimeout(hideTimeout);
            hideTimeout = null;
        }

        // Masquer tooltip actif
        if (activeTooltip) {
            hide();
        }

        // Créer le nouveau tooltip après un délai
        setTimeout(() => {
            if (!text) return;

            const tooltip = createTooltip(text, title, type, position);
            document.body.appendChild(tooltip);
            activeTooltip = tooltip;

            // Positionner
            positionTooltip(tooltip, target, position);

            // Afficher avec animation
            setTimeout(() => {
                tooltip.classList.add('show');
            }, 10);
        }, delay);
    }

    // Masquer le tooltip
    function hide(immediate) {
        if (!activeTooltip) return;

        if (immediate) {
            if (hideTimeout) {
                clearTimeout(hideTimeout);
                hideTimeout = null;
            }
            doHide();
        } else {
            // Délai avant masquage pour permettre le survol du tooltip
            hideTimeout = setTimeout(doHide, 100);
        }
    }

    // Masquer réellement
    function doHide() {
        if (!activeTooltip) return;

        activeTooltip.classList.remove('show');
        setTimeout(() => {
            if (activeTooltip && activeTooltip.parentNode) {
                activeTooltip.parentNode.removeChild(activeTooltip);
            }
            activeTooltip = null;
        }, 200);
    }

    // Échapper HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // API publique
    window.Colis224Tooltip = {
        show: show,
        hide: hide
    };

    // Initialisation automatique
    $(document).ready(function() {
        // Gérer les éléments avec data-tooltip
        $(document).on('mouseenter', '[data-tooltip]', function() {
            const $el = $(this);
            const text = $el.data('tooltip');
            const title = $el.data('tooltip-title');
            const position = $el.data('tooltip-position') || 'top';
            const type = $el.data('tooltip-type') || '';

            show(this, text, {
                title: title,
                position: position,
                type: type
            });
        });

        $(document).on('mouseleave', '[data-tooltip]', function() {
            hide();
        });

        // Masquer lors du scroll
        $(window).on('scroll', function() {
            if (activeTooltip) {
                hide(true);
            }
        });

        // Repositionner lors du resize
        let resizeTimeout;
        $(window).on('resize', function() {
            if (activeTooltip) {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    hide(true);
                }, 100);
            }
        });
    });

    // Helper pour ajouter facilement des icônes d'aide
    $.fn.addHelpIcon = function(tooltipText, options) {
        return this.each(function() {
            const $el = $(this);
            const opts = options || {};

            // Ne pas ajouter si déjà présent
            if ($el.find('.colis224-help-icon').length > 0) return;

            const icon = $('<span>', {
                class: 'colis224-help-icon' + (opts.pulse ? ' pulse' : ''),
                'data-tooltip': tooltipText,
                'data-tooltip-position': opts.position || 'top',
                'data-tooltip-type': opts.type || '',
                text: '?'
            });

            if (opts.title) {
                icon.attr('data-tooltip-title', opts.title);
            }

            if ($el.is('label') || $el.is('th') || $el.is('h2') || $el.is('h3')) {
                $el.append(' ').append(icon);
            } else {
                $el.after(icon);
            }
        });
    };

})(jQuery);

// Utilisation avec jQuery pour ajouter automatiquement des icônes d'aide
jQuery(document).ready(function($) {
    // Exemples d'ajout automatique (à personnaliser selon vos besoins)

    // Ajouter aide sur champs importants
    /*
    $('label[for="tracking_number"]').addHelpIcon(
        'Format: COL-001, COL-002, etc.',
        { position: 'right' }
    );
    */
});
