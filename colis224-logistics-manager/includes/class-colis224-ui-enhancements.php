<?php
/**
 * Améliorations UI/UX - Colis224 v2.10.12
 * Ce fichier contient tous les styles et scripts pour les améliorations
 */

// Styles CSS pour les améliorations
function colis224_enhanced_styles() {
    ?>
    <style>
    /* ===== BOUTON DE FERMETURE UNIVERSEL ===== */
    .colis224-modal-close,
    .colis224-close-btn,
    .modal-close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 36px;
        height: 36px;
        background: #f3f4f6;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 1000;
        padding: 0;
    }
    
    .colis224-modal-close:hover,
    .colis224-close-btn:hover,
    .modal-close-btn:hover {
        background: #ef4444;
        color: white;
        transform: rotate(90deg);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .colis224-modal-close svg,
    .colis224-close-btn svg,
    .modal-close-btn svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        stroke-width: 2;
    }
    
    /* ===== MODAL COLIS DÉTAILS - AMÉLIORÉE ===== */
    .colis224-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9998;
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .colis224-modal-overlay.active {
        display: block;
    }
    
    .colis224-modal-content {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 20px;
        max-width: 800px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        z-index: 9999;
        padding: 30px;
        animation: slideUp 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translate(-50%, -45%);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }
    
    /* Masquer la note interne pour les clients */
    .colis224-internal-note {
        display: none !important;
    }
    
    .admin-only-field {
        display: none !important;
    }
    
    /* ===== CHAT CLIENT MODERNE ===== */
    .colis224-chat-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 0;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        overflow: hidden;
        max-width: 500px;
        margin: 30px auto;
    }
    
    .colis224-chat-modern .chat-header {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 20px;
        color: white;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .colis224-chat-modern .chat-header h3 {
        margin: 0 0 5px 0;
        font-size: 20px;
        font-weight: 700;
    }
    
    .colis224-chat-modern .chat-status {
        font-size: 13px;
        opacity: 0.9;
    }
    
    .colis224-chat-modern .chat-messages {
        background: #f8f9fa;
        padding: 20px;
        height: 400px;
        overflow-y: auto;
    }
    
    .colis224-chat-modern .chat-message {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
        animation: messageSlide 0.3s ease;
    }
    
    @keyframes messageSlide {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .colis224-chat-modern .chat-message.client {
        align-items: flex-end;
    }
    
    .colis224-chat-modern .chat-message.agent {
        align-items: flex-start;
    }
    
    .colis224-chat-modern .message-bubble {
        max-width: 75%;
        padding: 12px 18px;
        border-radius: 18px;
        word-wrap: break-word;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        position: relative;
    }
    
    .colis224-chat-modern .chat-message.client .message-bubble {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }
    
    .colis224-chat-modern .chat-message.agent .message-bubble {
        background: white;
        color: #1f2937;
        border: 1px solid #e5e7eb;
        border-bottom-left-radius: 4px;
    }
    
    .colis224-chat-modern .message-time {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 5px;
        font-weight: 500;
    }
    
    .colis224-chat-modern .chat-input {
        padding: 20px;
        background: white;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 12px;
        align-items: flex-end;
    }
    
    .colis224-chat-modern .chat-input textarea {
        flex: 1;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 16px;
        resize: none;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.3s ease;
        min-height: 44px;
        max-height: 120px;
    }
    
    .colis224-chat-modern .chat-input textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .colis224-chat-modern .colis224-btn-send {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 12px;
        padding: 12px 20px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        min-height: 44px;
    }
    
    .colis224-chat-modern .colis224-btn-send:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .colis224-chat-modern .colis224-btn-send:active {
        transform: translateY(0);
    }
    
    .colis224-chat-modern .btn-icon {
        width: 18px;
        height: 18px;
        transition: transform 0.3s ease;
    }
    
    .colis224-chat-modern .colis224-btn-send:hover .btn-icon {
        transform: translateX(3px);
    }
    
    /* Scrollbar personnalisée pour le chat */
    .colis224-chat-modern .chat-messages::-webkit-scrollbar {
        width: 6px;
    }
    
    .colis224-chat-modern .chat-messages::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .colis224-chat-modern .chat-messages::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 3px;
    }
    
    /* ===== TICKETS SUPPORT - MODAL AVEC FERMETURE ===== */
    .ticket-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9998;
        display: none;
        align-items: center;
        justify-content: center;
    }
    
    .ticket-modal.active {
        display: flex;
    }
    
    .ticket-modal-content {
        background: white;
        border-radius: 20px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 30px;
        position: relative;
    }
    
    /* ===== CHAMP TEXTE ENRICHI (SIMPLE) ===== */
    .colis224-rich-textarea {
        width: 100%;
        min-height: 120px;
        padding: 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        resize: vertical;
        transition: all 0.3s ease;
    }
    
    .colis224-rich-textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    /* Barre d'outils pour le texte enrichi */
    .colis224-toolbar {
        display: flex;
        gap: 5px;
        padding: 8px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-bottom: none;
        border-radius: 8px 8px 0 0;
        flex-wrap: wrap;
    }
    
    .colis224-toolbar button {
        padding: 6px 12px;
        border: 1px solid #d1d5db;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    
    .colis224-toolbar button:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .colis224-toolbar + .colis224-rich-textarea {
        border-radius: 0 0 8px 8px;
        border-top: none;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .colis224-modal-content,
        .ticket-modal-content {
            width: 95%;
            padding: 20px;
            border-radius: 15px;
        }
        
        .colis224-chat-modern {
            max-width: 100%;
            border-radius: 15px;
        }
        
        .colis224-modal-close,
        .colis224-close-btn,
        .modal-close-btn {
            width: 32px;
            height: 32px;
            top: 10px;
            right: 10px;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'colis224_enhanced_styles');
add_action('admin_head', 'colis224_enhanced_styles');

// JavaScript pour les fonctionnalités
function colis224_enhanced_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        
        // Fonction pour fermer les modales
        window.colis224CloseModal = function(modalId) {
            const modal = $('#' + modalId);
            if (modal.length) {
                modal.removeClass('active').fadeOut(300);
                $('body').css('overflow', '');
            }
        };
        
        // Fermer modal avec touche Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.colis224-modal-overlay.active, .ticket-modal.active').each(function() {
                    $(this).removeClass('active').fadeOut(300);
                });
                $('body').css('overflow', '');
            }
        });
        
        // Fermer modal en cliquant sur l'overlay
        $('.colis224-modal-overlay, .ticket-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('active').fadeOut(300);
                $('body').css('overflow', '');
            }
        });
        
        // Masquer les notes internes pour les clients
        if (!$('body').hasClass('wp-admin')) {
            $('.colis224-internal-note, .admin-only-field').remove();
        }
        
        // Améliorer les textarea avec auto-resize
        $('textarea').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'colis224_enhanced_scripts');
add_action('admin_footer', 'colis224_enhanced_scripts');
