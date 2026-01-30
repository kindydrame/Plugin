/**
 * Colis224 Frontend Calculator JavaScript
 * Gestion du formulaire multi-étapes et génération automatique des informations
 *
 * @package Colis224_Logistics_Manager
 * @since 2.20.0
 */

(function($) {
    'use strict';

    // État global de l'application
    const AppState = {
        currentStep: 1,
        formData: {},
        serviceType: null, // 'cas1' or 'cas2'
        origin: null, // 'chine' or 'france'
        mode: null, // 'avion' or 'bateau'
        destination: null, // 'paris', 'marseille', etc.
    };

    /**
     * Initialisation au chargement du DOM
     */
    $(document).ready(function() {
        initEventListeners();
        updateProgressBar();
    });

    /**
     * Initialiser tous les event listeners
     */
    function initEventListeners() {
        // Navigation buttons
        $(document).on('click', '.btn-next', handleNextStep);
        $(document).on('click', '.btn-prev', handlePrevStep);

        // Service selection
        $(document).on('click', '.btn-select-service', handleServiceSelection);

        // Origin selection
        $(document).on('click', '.btn-select-origin', handleOriginSelection);

        // Agency selection (CAS 4)
        $(document).on('click', '.btn-select-agency', handleAgencySelection);

        // Mode selection - UTILISER DÉLÉGATION D'ÉVÉNEMENT
        $(document).on('click', '.btn-select-mode', handleModeSelection);

        // France cities selection
        $(document).on('click', '.btn-select-france-cities', handleFranceCitiesSelection);

        // Back to origin
        $(document).on('click', '.btn-back-origin', handleBackToOrigin);

        // Copy buttons
        $(document).on('click', '.btn-copy, .btn-copy-full', handleCopy);

        // Form submission
        $(document).on('submit', '#colis224-calculator-form', handleFormSubmit);

        // Restart button
        $(document).on('click', '.btn-restart', handleRestart);

        // Input validation
        $(document).on('blur', '#client_telephone', validatePhone);
        $(document).on('blur', '#client_nom, #client_prenom', function() {
            validateRequired($(this));
        });
    }

    /**
     * Navigation: Étape suivante
     */
    function handleNextStep(e) {
        e.preventDefault();
        const $button = $(this);
        const nextStep = parseInt($button.data('next'));

        // Validate current step
        if (!validateCurrentStep()) {
            return;
        }

        // Save form data
        saveFormData();

        // Navigate to next step
        goToStep(nextStep);
    }

    /**
     * Navigation: Étape précédente
     */
    function handlePrevStep(e) {
        e.preventDefault();
        const $button = $(this);
        const prevStep = parseInt($button.data('prev'));

        goToStep(prevStep);
    }

    /**
     * Sélection du type de service (CAS 1, 2, 3 ou 4)
     */
    function handleServiceSelection(e) {
        e.preventDefault();
        const $button = $(this);
        const service = $button.data('service');

        AppState.serviceType = service;

        // Visual feedback
        $('.service-card').removeClass('selected');
        $button.closest('.service-card').addClass('selected');

        // Determine next step
        setTimeout(() => {
            $('[data-step="2"]').removeClass('active').hide();

            if (service === 'cas1') {
                // CAS 1: Go to order details
                $('[data-step="3"][data-service="cas1"]').show().addClass('active');
                AppState.currentStep = 3;
            } else if (service === 'cas2') {
                // CAS 2: Go to origin selection
                $('[data-step="3"][data-service="cas2"]').show().addClass('active');
                AppState.currentStep = 3;
            } else if (service === 'cas3') {
                // CAS 3: Go to validation panier
                $('[data-step="3"][data-service="cas3"]').show().addClass('active');
                AppState.currentStep = 3;
            } else if (service === 'cas4') {
                // CAS 4: Go to agency selection
                $('[data-step="3"][data-service="cas4"]').show().addClass('active');
                AppState.currentStep = 3;
            }

            updateProgressBar();
        }, 300);
    }

    /**
     * Sélection de l'origine (Chine ou France)
     */
    function handleOriginSelection(e) {
        e.preventDefault();
        const $button = $(this);
        const origin = $button.data('origin');

        AppState.origin = origin;

        // Visual feedback
        $('.origin-card').removeClass('selected');
        $button.closest('.origin-card').addClass('selected');

        // Show mode selection based on origin
        setTimeout(() => {
            $('[data-step="3"][data-service="cas2"]').removeClass('active').hide();

            if (origin === 'chine') {
                $('[data-step="3-chine"]').show().addClass('active');
            } else if (origin === 'france') {
                $('[data-step="3-france"]').show().addClass('active');
            }
        }, 300);
    }

    /**
     * Sélection du mode de livraison
     */
    function handleModeSelection(e) {
        e.preventDefault();
        const $button = $(this);
        const mode = $button.data('mode');
        const origin = $button.data('origin');

        AppState.mode = mode;
        AppState.destination = origin; // 'chine', 'france-paris', 'france-marseille'

        // Visual feedback
        $('.mode-card, .city-card').removeClass('selected');
        $button.closest('.mode-card, .city-card').addClass('selected');

        // Generate shipping mark and address
        setTimeout(() => {
            generateShippingMarkAndAddress();

            // Show result step
            $('[data-step^="3"]').removeClass('active').hide();
            $('[data-step="4"][data-result="cas2"]').show().addClass('active');
            AppState.currentStep = 4;
            updateProgressBar();
        }, 300);
    }

    /**
     * Sélection des villes françaises
     */
    function handleFranceCitiesSelection(e) {
        e.preventDefault();

        setTimeout(() => {
            $('[data-step="3-france"]').removeClass('active').hide();
            $('[data-step="3-france-cities"]').show().addClass('active');
        }, 300);
    }

    /**
     * Retour au choix d'origine
     */
    function handleBackToOrigin(e) {
        e.preventDefault();

        AppState.origin = null;
        AppState.mode = null;

        $('[data-step^="3-"]').removeClass('active').hide();
        $('[data-step="3"][data-service="cas2"]').show().addClass('active');
    }

    /**
     * Sélection d'une agence (CAS 4)
     */
    function handleAgencySelection(e) {
        e.preventDefault();
        const $button = $(this);
        const agency = $button.data('agency');

        // Visual feedback
        $('.agency-card').removeClass('selected');
        $button.closest('.agency-card').addClass('selected');

        AppState.formData.agency = agency;

        // Show success message immediately for CAS 4
        setTimeout(() => {
            showAgencyConfirmation(agency);
        }, 300);
    }

    /**
     * Générer le Shipping Mark et l'adresse
     */
    function generateShippingMarkAndAddress() {
        const nom = $('#client_nom').val().trim().toUpperCase();
        const prenom = $('#client_prenom').val().trim().toUpperCase();
        const telephone = $('#client_telephone').val().trim();

        // Generate PA code (last 4 digits of phone)
        const paCode = generatePACode(telephone);

        // Detect country code
        const phoneInfo = detectCountryCode(telephone);
        const formattedPhone = formatPhoneWithIndicatif(telephone, phoneInfo);

        // Generate pricing, shipping mark, and address
        let pricingInfo = '';
        let shippingMark = '';
        let deliveryAddress = '';
        let visualImages = '';
        let addressImage = ''; // Image d'adresse correspondante au mode

        if (AppState.origin === 'chine') {
            // PRICING INFO - Show rates before shipping mark
            if (AppState.mode === 'avion') {
                pricingInfo = `
<div class="pricing-box">
    <h4>💰 CALCULATEUR TARIF AÉRIEN (Chine → Guinée)</h4>

    <div class="pricing-section">
        <h5>📦 Calculez votre tarif en temps réel</h5>

        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 16px 0;">
            <p style="margin: 0 0 16px; font-weight: 600;">Sélectionnez vos articles et quantités :</p>

            <!-- Tarifs au kilogramme -->
            <div style="margin-bottom: 20px;">
                <h6 style="font-size: 15px; font-weight: 700; margin: 0 0 12px; color: #1e293b;">📦 Articles au poids</h6>

                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 2px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; font-size: 14px;">Essentiels du quotidien</label>
                        <span style="color: #667eea; font-weight: 700;">200,000 GNF/kg</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="number" class="plane-calc-input" data-category="essentiels" data-unit="kg" data-price="200000" step="0.1" min="0" placeholder="Poids (kg)"
                               style="width: 100%; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <div style="padding: 8px; background: #f1f5f9; border-radius: 6px; text-align: center; font-weight: 600; color: #64748b;">
                            <span class="item-subtotal" data-category="essentiels">0 GNF</span>
                        </div>
                    </div>
                </div>

                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 2px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; font-size: 14px;">🔋 Batteries / Liquides</label>
                        <span style="color: #667eea; font-weight: 700;">250,000 GNF/kg</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="number" class="plane-calc-input" data-category="batteries" data-unit="kg" data-price="250000" step="0.1" min="0" placeholder="Poids (kg)"
                               style="width: 100%; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <div style="padding: 8px; background: #f1f5f9; border-radius: 6px; text-align: center; font-weight: 600; color: #64748b;">
                            <span class="item-subtotal" data-category="batteries">0 GNF</span>
                        </div>
                    </div>
                </div>

                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 2px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; font-size: 14px;">⚡ Express (4-7 jours)</label>
                        <span style="color: #667eea; font-weight: 700;">300,000 GNF/kg</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="number" class="plane-calc-input" data-category="express" data-unit="kg" data-price="300000" step="0.1" min="0" placeholder="Poids (kg)"
                               style="width: 100%; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <div style="padding: 8px; background: #f1f5f9; border-radius: 6px; text-align: center; font-weight: 600; color: #64748b;">
                            <span class="item-subtotal" data-category="express">0 GNF</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarifs électronique à la pièce -->
            <div style="margin-bottom: 20px;">
                <h6 style="font-size: 15px; font-weight: 700; margin: 0 0 12px; color: #1e293b;">📱💻 Électronique (à la pièce)</h6>

                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 2px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; font-size: 14px;">💻 MacBook</label>
                        <span style="color: #667eea; font-weight: 700;">1,000,000 GNF/pièce</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="number" class="plane-calc-input" data-category="macbook" data-unit="piece" data-price="1000000" step="1" min="0" placeholder="Nombre"
                               style="width: 100%; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <div style="padding: 8px; background: #f1f5f9; border-radius: 6px; text-align: center; font-weight: 600; color: #64748b;">
                            <span class="item-subtotal" data-category="macbook">0 GNF</span>
                        </div>
                    </div>
                </div>

                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 2px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; font-size: 14px;">💻 Autres ordinateurs</label>
                        <span style="color: #667eea; font-weight: 700;">800,000 GNF/pièce</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="number" class="plane-calc-input" data-category="laptop" data-unit="piece" data-price="800000" step="1" min="0" placeholder="Nombre"
                               style="width: 100%; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <div style="padding: 8px; background: #f1f5f9; border-radius: 6px; text-align: center; font-weight: 600; color: #64748b;">
                            <span class="item-subtotal" data-category="laptop">0 GNF</span>
                        </div>
                    </div>
                </div>

                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 2px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; font-size: 14px;">📱 iPhone</label>
                        <span style="color: #667eea; font-weight: 700;">1,000,000 GNF/pièce</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="number" class="plane-calc-input" data-category="iphone" data-unit="piece" data-price="1000000" step="1" min="0" placeholder="Nombre"
                               style="width: 100%; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <div style="padding: 8px; background: #f1f5f9; border-radius: 6px; text-align: center; font-weight: 600; color: #64748b;">
                            <span class="item-subtotal" data-category="iphone">0 GNF</span>
                        </div>
                    </div>
                </div>

                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 2px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; font-size: 14px;">📱 Samsung</label>
                        <span style="color: #667eea; font-weight: 700;">700,000 GNF/pièce</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="number" class="plane-calc-input" data-category="samsung" data-unit="piece" data-price="700000" step="1" min="0" placeholder="Nombre"
                               style="width: 100%; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <div style="padding: 8px; background: #f1f5f9; border-radius: 6px; text-align: center; font-weight: 600; color: #64748b;">
                            <span class="item-subtotal" data-category="samsung">0 GNF</span>
                        </div>
                    </div>
                </div>

                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 2px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; font-size: 14px;">📱 Autres téléphones</label>
                        <span style="color: #667eea; font-weight: 700;">550,000 GNF/pièce</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="number" class="plane-calc-input" data-category="otherphones" data-unit="piece" data-price="550000" step="1" min="0" placeholder="Nombre"
                               style="width: 100%; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <div style="padding: 8px; background: #f1f5f9; border-radius: 6px; text-align: center; font-weight: 600; color: #64748b;">
                            <span class="item-subtotal" data-category="otherphones">0 GNF</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; text-align: center; color: white;">
                <p style="margin: 0 0 8px; font-size: 15px; opacity: 0.9;">TOTAL ESTIMÉ</p>
                <p style="margin: 0; font-size: 32px; font-weight: 700;" id="plane-total-price">0 GNF</p>
            </div>
        </div>

        <p style="margin: 12px 0 0; font-size: 13px; color: #64748b; font-style: italic;">
            ⏱️ Délai de livraison : 7-15 jours (sauf Express : 4-7 jours)
        </p>
    </div>

    <p class="pricing-note">💡 <strong>Besoin d'un devis personnalisé?</strong> <a href="https://wa.me/224620178930" target="_blank" rel="noopener noreferrer" style="color: #667eea; text-decoration: underline; font-weight: 600;">Contactez-nous via WhatsApp</a> après avoir enregistré vos informations.</p>
</div>

<div class="wechat-payment-box" style="background: linear-gradient(135deg, #09b83e 0%, #07a33a 100%); border-radius: 12px; padding: 24px; margin-top: 20px; color: white; text-align: center;">
    <h4 style="margin: 0 0 16px; font-size: 20px; font-weight: 700;">💳 Paiement WeChat disponible</h4>
    <p style="margin: 0 0 16px; opacity: 0.9; font-size: 14px;">Pour payer vos frais de transport en Chine, scannez notre QR Code WeChat</p>
    <div style="background: white; padding: 16px; border-radius: 8px; display: inline-block;">
        <img src="${colis224Frontend.wechat_qr}" alt="QR Code WeChat COLIS224" style="max-width: 250px; width: 100%; height: auto; border-radius: 8px;">
    </div>
    <p style="margin: 16px 0 0; font-size: 13px; opacity: 0.85;">🇨🇳 Idéal pour les paiements en Chine</p>
</div>`;
            } else if (AppState.mode === 'bateau') {
                pricingInfo = `
<div class="pricing-box">
    <h4>💰 TARIF TRANSPORT MARITIME (Chine → Guinée)</h4>

    <div class="pricing-section">
        <h5>🚢 Calculateur de tarif CBM</h5>

        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 16px 0;">
            <p style="margin: 0 0 16px; font-weight: 600;">📦 Entrez les dimensions de votre colis :</p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500;">Longueur (m)</label>
                    <input type="number" id="cbm-length" step="0.01" min="0" placeholder="Ex: 1.2"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 15px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500;">Largeur (m)</label>
                    <input type="number" id="cbm-width" step="0.01" min="0" placeholder="Ex: 0.8"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 15px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500;">Hauteur (m)</label>
                    <input type="number" id="cbm-height" step="0.01" min="0" placeholder="Ex: 0.6"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 15px;">
                </div>
            </div>

            <button type="button" id="btn-calculate-cbm"
                    style="width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.4)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                🧮 Calculer le tarif
            </button>

            <div id="cbm-result" style="margin-top: 16px; padding: 16px; background: white; border-radius: 8px; border-left: 4px solid #667eea; display: none;">
                <p style="margin: 0 0 8px; font-size: 14px; color: #64748b;">Volume (CBM)</p>
                <p style="margin: 0 0 12px; font-size: 24px; font-weight: 700; color: #1e293b;" id="cbm-volume">-</p>
                <p style="margin: 0 0 8px; font-size: 14px; color: #64748b;">Tarif total estimé</p>
                <p style="margin: 0; font-size: 28px; font-weight: 700; color: #667eea;" id="cbm-price">-</p>
            </div>
        </div>

        <p style="margin: 12px 0 0; font-size: 13px; color: #64748b; font-style: italic;">
            📐 CBM = Longueur (m) × Largeur (m) × Hauteur (m)<br>
            💰 Tarif : 4,700,000 GNF par CBM
        </p>
    </div>

    <div class="pricing-section">
        <h5>⏱️ Délai de livraison</h5>
        <p>🚢 <strong>45-60 jours</strong> (port à port)</p>
    </div>

    <p class="pricing-note">💡 <strong>Pour un devis précis:</strong> <a href="https://wa.me/224620178930" target="_blank" rel="noopener noreferrer" style="color: #667eea; text-decoration: underline; font-weight: 600;">Contactez-nous via WhatsApp</a> après avoir enregistré vos informations.</p>
</div>

<div class="wechat-payment-box" style="background: linear-gradient(135deg, #09b83e 0%, #07a33a 100%); border-radius: 12px; padding: 24px; margin-top: 20px; color: white; text-align: center;">
    <h4 style="margin: 0 0 16px; font-size: 20px; font-weight: 700;">💳 Paiement WeChat disponible</h4>
    <p style="margin: 0 0 16px; opacity: 0.9; font-size: 14px;">Pour payer vos frais de transport en Chine, scannez notre QR Code WeChat</p>
    <div style="background: white; padding: 16px; border-radius: 8px; display: inline-block;">
        <img src="${colis224Frontend.wechat_qr}" alt="QR Code WeChat COLIS224" style="max-width: 250px; width: 100%; height: auto; border-radius: 8px;">
    </div>
    <p style="margin: 16px 0 0; font-size: 13px; opacity: 0.85;">🇨🇳 Idéal pour les paiements en Chine</p>
</div>`;
            }
            // Shipping mark for China (OBLIGATOIRE - 6 lignes)
            const warehousePhoneChina = (typeof colis224Frontend !== 'undefined' && colis224Frontend.warehouse_phone_china) ? colis224Frontend.warehouse_phone_china : '+8618719472926';
            shippingMark = `SHIPPING MARK OBLIGATOIRE - Écrire sur le carton :

1 - COLIS224
2 - ${prenom} ${nom}
3 - ${warehousePhoneChina}
4 - ${paCode}
5 - ${formattedPhone}
6 - ${AppState.mode === 'avion' ? 'AVION ✈️' : 'BATEAU 🚢'}

请务必在每一个纸箱上清楚标注货运名称：COLIS224
未标注 COLIS224 的货物将不被接收。`;

            // Address for China
            if (AppState.mode === 'avion') {
                deliveryAddress = `📍 ADRESSE AVION (Guangzhou - 广州)

🇨🇳 COLIS224
广东省广州市越秀区流花街环市西路202号美博运动城 10楼1020室
${warehousePhoneChina} KINDY

📦 INFORMATIONS DE LIVRAISON :
Nom du destinataire : KINDY DRAME
Téléphone : ${warehousePhoneChina}
Adresse complète : 10楼1020室 (10ème étage, salle 1020)

⚠️ INSTRUCTIONS OBLIGATOIRES (供应商指示) :
✅ 1. 必须始终按以下顺序首先列出：
   • COLIS224 货运
   • 客户全名 (${prenom} ${nom})
   • ${warehousePhoneChina} (货运号码)
   • ${paCode} (客户代码)
   • ${formattedPhone} (客户电话)

✅ 2. Écrivez "COLIS224 ${paCode}" en GROS sur chaque carton
✅ 3. 严禁修改或删除货运名称或货运号码
✅ 4. 请务必在每一个纸箱上清楚标注货运名称：COLIS224
✅ 5. 未标注 COLIS224 的货物将不被接收

🚨 不接受危险、易燃或易爆货物
🚨 必须通过微信联系我们的仓库号码提前预约送货，否则包裹将被拒收

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📖 MINI-GUIDE CLIENT / FOR CUSTOMER / 客户指南

🇫🇷 FR: Donnez cette adresse ET le shipping mark à votre fournisseur. Vérifiez que "COLIS224 ${paCode}" est bien écrit sur CHAQUE carton avant l'expédition.

🇬🇧 EN: Give this address AND shipping mark to your supplier. Make sure "COLIS224 ${paCode}" is written on EVERY box before shipping.

🇨🇳 CN: 将此地址和运输标记提供给您的供应商。确保在发货前每个箱子上都写有"COLIS224 ${paCode}"。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📖 GUIDE FOURNISSEUR / FOR SUPPLIER / 供应商指南

🇬🇧 EN: MANDATORY - Write on EACH box:
   1. COLIS224
   2. Customer name: ${prenom} ${nom}
   3. Warehouse phone: ${warehousePhoneChina}
   4. Customer code: ${paCode}
   5. Customer phone: ${formattedPhone}
   6. Mode: PLANE ✈️

   ⚠️ Packages without "COLIS224" marking will be REFUSED.

🇨🇳 CN: 强制要求 - 每个箱子必须写：
   1. COLIS224
   2. 客户姓名：${prenom} ${nom}
   3. 仓库电话：${warehousePhoneChina}
   4. 客户代码：${paCode}
   5. 客户电话：${formattedPhone}
   6. 方式：飞机 ✈️

   ⚠️ 未标注 "COLIS224" 的包裹将被拒收。`;

                // Check if images are available - AVION
                if (typeof colis224Frontend !== 'undefined' && colis224Frontend.images) {
                    // Image d'adresse avion (affichée avec l'adresse)
                    addressImage = `<img src="${colis224Frontend.images.air_plane}" alt="Adresse Avion Chine - Guangzhou" style="max-width:100%; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">`;
                    // Image des instructions shipping mark avion
                    visualImages = `<img src="${colis224Frontend.images.air_cargo_mark}" alt="Instructions Shipping Mark Avion" style="max-width:100%; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">`;
                } else {
                    addressImage = '';
                    visualImages = `<p style="color: #f59e0b; padding: 20px; background: #fef3c7; border-radius: 8px;">
📸 <strong>Images des instructions à venir</strong><br>
Les images d'exemple pour l'adresse et le shipping mark seront bientôt disponibles.
</p>`;
                }
            } else {
                deliveryAddress = `📍 ADRESSE BATEAU (Foshan - 佛山)

🇨🇳 COLIS224
佛山市南海区里水镇上沙路29号
${warehousePhoneChina} KINDY

📦 INFORMATIONS DE LIVRAISON :
Nom du destinataire : KINDY DRAME
Téléphone : ${warehousePhoneChina}
Ville : Foshan (佛山市)

⚠️ INSTRUCTIONS OBLIGATOIRES (供应商指示) :
✅ 1. 必须始终按以下顺序首先列出：
   • COLIS224 货运
   • 客户全名 (${prenom} ${nom})
   • ${warehousePhoneChina} (货运号码)
   • ${paCode} (客户代码)
   • ${formattedPhone} (客户电话)

✅ 2. Écrivez "COLIS224 ${paCode}" en GROS sur chaque carton
✅ 3. 严禁修改或删除货运名称或货运号码
✅ 4. 请务必在每一个纸箱上清楚标注货运名称：COLIS224
✅ 5. 未标注 COLIS224 的货物将不被接收

🚢 Transport maritime : 30-45 jours
🚨 不接受危险、易燃或易爆货物
🚨 必须通过微信联系我们的仓库号码提前预约送货，否则包裹将被拒收

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📖 MINI-GUIDE CLIENT / FOR CUSTOMER / 客户指南

🇫🇷 FR: Donnez cette adresse ET le shipping mark à votre fournisseur. Vérifiez que "COLIS224 ${paCode}" est bien écrit sur CHAQUE carton avant l'expédition.

🇬🇧 EN: Give this address AND shipping mark to your supplier. Make sure "COLIS224 ${paCode}" is written on EVERY box before shipping.

🇨🇳 CN: 将此地址和运输标记提供给您的供应商。确保在发货前每个箱子上都写有"COLIS224 ${paCode}"。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📖 GUIDE FOURNISSEUR / FOR SUPPLIER / 供应商指南

🇬🇧 EN: MANDATORY - Write on EACH box:
   1. COLIS224
   2. Customer name: ${prenom} ${nom}
   3. Warehouse phone: ${warehousePhoneChina}
   4. Customer code: ${paCode}
   5. Customer phone: ${formattedPhone}
   6. Mode: BOAT 🚢

   ⚠️ Packages without "COLIS224" marking will be REFUSED.

🇨🇳 CN: 强制要求 - 每个箱子必须写：
   1. COLIS224
   2. 客户姓名：${prenom} ${nom}
   3. 仓库电话：${warehousePhoneChina}
   4. 客户代码：${paCode}
   5. 客户电话：${formattedPhone}
   6. 方式：船运 🚢

   ⚠️ 未标注 "COLIS224" 的包裹将被拒收。`;

                // Check if images are available - BATEAU
                if (typeof colis224Frontend !== 'undefined' && colis224Frontend.images) {
                    // Image d'adresse bateau (affichée avec l'adresse)
                    addressImage = `<img src="${colis224Frontend.images.sea_cargo}" alt="Adresse Bateau Chine - Foshan" style="max-width:100%; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">`;
                    // Image des instructions shipping mark bateau
                    visualImages = `<img src="${colis224Frontend.images.sea_cargo_mark}" alt="Instructions Shipping Mark Bateau" style="max-width:100%; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">`;
                } else {
                    addressImage = '';
                    visualImages = `<p style="color: #f59e0b; padding: 20px; background: #fef3c7; border-radius: 8px;">
📸 <strong>Images des instructions à venir</strong><br>
Les images d'exemple pour l'adresse et le shipping mark seront bientôt disponibles.
</p>`;
                }
            }
        } else if (AppState.destination === 'france-paris') {
            if (AppState.mode === 'bateau') {
                // Bateau Paris → Conakry
                const paCodeFrance = `COLIS224 GP GUINEE ${paCode}`;

                shippingMark = `COLIS224 GP GUINEE ${paCode}
${prenom} ${nom}
${formattedPhone}`;

                deliveryAddress = `📍 ADRESSE BATEAU PARIS → CONAKRY

Nom : AARON TRAVEL / CGL
Adresse : 15 Rue des Écoles
Ville : Le Thillay
Code postal : 95500
Téléphone : +33 6 98 48 57 52

⚠️ MARQUAGE OBLIGATOIRE:
Écrivez sur le carton : ${paCodeFrance}

Transport maritime par conteneur
Délai : 30-45 jours`;
            } else {
                // Avion Paris (Amazon, Shein, etc.)
                const paCodeFrance = `COLIS224 GP GUINEE ${paCode}`;

                deliveryAddress = `📍 ADRESSE DE LIVRAISON PARIS

Pour vos commandes Amazon, Shein, Temu, Aliexpress, etc.

Prénom : KINDY
Nom : DRAME
Téléphone : +33 6 98 48 57 52
Adresse (ligne 1) : 37 Rue Stephenson
Ville : Paris
Code postal : 75018
Pays : France

⚠️ TRÈS IMPORTANT - Adresse ligne 2:
${paCodeFrance}

📦 Utilisez exactement ce format lors de votre commande !`;

                shippingMark = `Code client : ${paCodeFrance}
Nom : ${prenom} ${nom}
Téléphone : ${formattedPhone}`;
            }
        } else if (AppState.destination === 'france-marseille') {
            // Avion Marseille (Amazon, Shein, etc.)
            const paCodeFrance = `COLIS224 GP GUINEE ${paCode}`;

            deliveryAddress = `📍 ADRESSE DE LIVRAISON MARSEILLE

Pour vos commandes Amazon, Shein, Temu, Aliexpress, etc.

Prénom : KINDY DRAME
Nom : ${nom}
Téléphone : +33 6 98 48 57 52
Adresse (ligne 1) : 68 rue Longue des Capucins
Ville : Marseille
Code postal : 13001
Pays : France

⚠️ TRÈS IMPORTANT - Adresse ligne 2:
${paCodeFrance}

📦 Utilisez exactement ce format lors de votre commande !`;

            shippingMark = `Code client : ${paCodeFrance}
Nom : ${prenom} ${nom}
Téléphone : ${formattedPhone}`;
        }

        // Display pricing, shipping mark and address
        $('#pricing-display').html(pricingInfo);
        $('#shipping-mark-display').text(shippingMark);
        $('#address-display').text(deliveryAddress);
        // Afficher l'image d'adresse correspondante au mode (avion/bateau)
        $('#address-image').html(addressImage);
        // Afficher l'image des instructions shipping mark
        $('#visual-instructions-images').html(visualImages);

        // Initialize CBM calculator if bateau mode
        if (AppState.mode === 'bateau' && AppState.origin === 'chine') {
            setTimeout(() => {
                initCBMCalculator();
            }, 100);
        }

        // Initialize plane pricing calculator if avion mode
        if (AppState.mode === 'avion' && AppState.origin === 'chine') {
            setTimeout(() => {
                initPlanePricingCalculator();
            }, 100);
        }

        // Build WhatsApp contact buttons for quote page
        let whatsappButtons = '';
        if (colis224Frontend.agencies && colis224Frontend.agencies.length > 0) {
            // Take first 3 agencies for the quote page
            const mainAgencies = colis224Frontend.agencies.slice(0, 3);
            mainAgencies.forEach(agency => {
                const phoneClean = agency.phone.replace(/[^0-9]/g, '');
                whatsappButtons += `
                    <a href="https://wa.me/${phoneClean}"
                       target="_blank"
                       rel="noopener noreferrer"
                       style="display: inline-block; margin: 4px; padding: 12px 24px; background: white; color: #25d366; text-decoration: none; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: transform 0.2s;"
                       onmouseover="this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.transform='translateY(0)'">
                        💬 ${agency.name}
                    </a>`;
            });
        }
        $('#whatsapp-quote-buttons').html(whatsappButtons);

        // Save to state
        AppState.formData.shipping_mark = shippingMark;
        AppState.formData.pa_code = paCode;
    }

    /**
     * Initialiser le calculateur CBM pour le bateau
     */
    function initCBMCalculator() {
        $(document).on('click', '#btn-calculate-cbm', function() {
            const length = parseFloat($('#cbm-length').val()) || 0;
            const width = parseFloat($('#cbm-width').val()) || 0;
            const height = parseFloat($('#cbm-height').val()) || 0;

            if (length <= 0 || width <= 0 || height <= 0) {
                alert('Veuillez entrer toutes les dimensions (valeurs positives)');
                return;
            }

            // Calculate CBM
            const cbm = length * width * height;
            const pricePerCBM = 4700000; // 4,700,000 GNF
            const totalPrice = cbm * pricePerCBM;

            // Display result
            $('#cbm-volume').text(cbm.toFixed(3) + ' m³');
            $('#cbm-price').text(totalPrice.toLocaleString('fr-GN') + ' GNF');
            $('#cbm-result').fadeIn();
        });
    }

    /**
     * Initialiser le calculateur de tarifs avion
     */
    function initPlanePricingCalculator() {
        // Real-time calculation on input change
        $(document).on('input', '.plane-calc-input', function() {
            const $input = $(this);
            const category = $input.data('category');
            const price = parseFloat($input.data('price'));
            const quantity = parseFloat($input.val()) || 0;

            // Calculate subtotal for this category
            const subtotal = quantity * price;

            // Update subtotal display
            $(`.item-subtotal[data-category="${category}"]`).text(
                subtotal > 0 ? subtotal.toLocaleString('fr-GN') + ' GNF' : '0 GNF'
            );

            // Calculate and update total
            calculatePlaneTotal();
        });
    }

    /**
     * Calculer le total pour les tarifs avion
     */
    function calculatePlaneTotal() {
        let total = 0;

        $('.plane-calc-input').each(function() {
            const $input = $(this);
            const price = parseFloat($input.data('price'));
            const quantity = parseFloat($input.val()) || 0;
            total += quantity * price;
        });

        // Update total display
        $('#plane-total-price').text(
            total > 0 ? total.toLocaleString('fr-GN') + ' GNF' : '0 GNF'
        );
    }

    /**
     * Générer le code PA à partir du numéro de téléphone
     */
    function generatePACode(phone) {
        const digits = phone.replace(/\D/g, '');
        const last4 = digits.slice(-4);
        return 'PA' + last4;
    }

    /**
     * Détecter le code pays à partir du numéro
     */
    function detectCountryCode(phone) {
        const cleanPhone = phone.replace(/\s/g, '');

        const countries = {
            '+224': { code: 'GN', name: 'Guinée' },
            '+33': { code: 'FR', name: 'France' },
            '+1': { code: 'US', name: 'USA' },
            '+86': { code: 'CN', name: 'Chine' },
            '+221': { code: 'SN', name: 'Sénégal' },
            '+225': { code: 'CI', name: 'Côte d\'Ivoire' },
            '+212': { code: 'MA', name: 'Maroc' },
        };

        for (const [indicatif, info] of Object.entries(countries)) {
            if (cleanPhone.startsWith(indicatif)) {
                return { indicatif, ...info };
            }
        }

        // Default to Guinea
        return { indicatif: '+224', code: 'GN', name: 'Guinée' };
    }

    /**
     * Formater le téléphone avec l'indicatif
     */
    function formatPhoneWithIndicatif(phone, phoneInfo) {
        const cleanPhone = phone.replace(/\D/g, '');

        // If already has indicatif, return as is
        if (phone.startsWith('+')) {
            return phone;
        }

        // Add indicatif
        return phoneInfo.indicatif + cleanPhone;
    }

    /**
     * Copier le contenu dans le presse-papiers
     */
    function handleCopy(e) {
        e.preventDefault();
        const $button = $(this);
        const targetId = $button.data('copy');
        const $target = $('#' + targetId);

        let textToCopy = '';

        if ($target.is('code')) {
            textToCopy = $target.text();
        } else {
            textToCopy = $target.text();
        }

        // Copy to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                showCopyFeedback($button);
            }).catch(err => {
                console.error('Erreur de copie:', err);
                fallbackCopyTextToClipboard(textToCopy, $button);
            });
        } else {
            fallbackCopyTextToClipboard(textToCopy, $button);
        }
    }

    /**
     * Fallback pour copier dans le presse-papiers
     */
    function fallbackCopyTextToClipboard(text, $button) {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();

        try {
            document.execCommand('copy');
            showCopyFeedback($button);
        } catch (err) {
            console.error('Erreur de copie:', err);
            alert('Impossible de copier. Veuillez copier manuellement.');
        }

        $temp.remove();
    }

    /**
     * Afficher le feedback de copie
     */
    function showCopyFeedback($button) {
        const originalText = $button.html();
        $button.html('✅ Copié !');
        $button.prop('disabled', true);

        setTimeout(() => {
            $button.html(originalText);
            $button.prop('disabled', false);
        }, 2000);
    }

    /**
     * Valider l'étape actuelle
     */
    function validateCurrentStep() {
        if (AppState.currentStep === 1) {
            // Validate contact info
            const nom = $('#client_nom').val().trim();
            const prenom = $('#client_prenom').val().trim();
            const telephone = $('#client_telephone').val().trim();

            if (!nom || !prenom || !telephone) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return false;
            }

            if (!isValidPhone(telephone)) {
                alert('Veuillez entrer un numéro de téléphone valide avec l\'indicatif (ex: +224 620 17 89 30)');
                return false;
            }

            return true;
        }

        if (AppState.currentStep === 3 && AppState.serviceType === 'cas1') {
            // Validate CAS 1 order details
            const quantites = $('#cas1_quantites').val().trim();
            const modeles = $('#cas1_modeles').val().trim();

            if (!quantites || !modeles) {
                alert('Veuillez remplir les quantités et modèles souhaités.');
                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * Valider un numéro de téléphone
     */
    function isValidPhone(phone) {
        const cleanPhone = phone.replace(/\s/g, '');
        // Must start with + and have at least 10 digits
        return /^\+\d{10,15}$/.test(cleanPhone);
    }

    /**
     * Validation téléphone
     */
    function validatePhone() {
        const $input = $(this);
        const phone = $input.val().trim();

        if (phone && !isValidPhone(phone)) {
            $input.addClass('error');
            if (!$input.next('.error-message').length) {
                $input.after('<span class="error-message">Format invalide. Utilisez l\'indicatif (ex: +224 620178930)</span>');
            }
        } else {
            $input.removeClass('error');
            $input.next('.error-message').remove();
        }
    }

    /**
     * Valider un champ requis
     */
    function validateRequired($input) {
        const value = $input.val().trim();

        if (!value) {
            $input.addClass('error');
            if (!$input.next('.error-message').length) {
                $input.after('<span class="error-message">Ce champ est obligatoire</span>');
            }
        } else {
            $input.removeClass('error');
            $input.next('.error-message').remove();
        }
    }

    /**
     * Sauvegarder les données du formulaire
     */
    function saveFormData() {
        // v2.20.12: Préserver shipping_mark, pa_code et agency qui sont définis ailleurs
        const existingShippingMark = AppState.formData.shipping_mark || '';
        const existingPaCode = AppState.formData.pa_code || '';
        const existingAgency = AppState.formData.agency || '';

        AppState.formData = {
            nom: $('#client_nom').val().trim(),
            prenom: $('#client_prenom').val().trim(),
            telephone: $('#client_telephone').val().trim(),
            type_service: AppState.serviceType,
            origine: AppState.origin || '',
            mode_livraison: AppState.mode || '',
            destination: AppState.destination || '',
            quantites: $('#cas1_quantites').val().trim(),
            modeles: $('#cas1_modeles').val().trim(),
            delai: $('#cas1_delai').val().trim(),
            panier_details: $('#cas3_panier').val().trim(),
            site_achat: $('#cas3_site').val().trim(),
            shipping_mark: existingShippingMark,
            pa_code: existingPaCode,
            agency: existingAgency
        };

        // Update summaries
        if (AppState.serviceType === 'cas1') {
            updateCAS1Summary();
        } else if (AppState.serviceType === 'cas3') {
            updateCAS3Summary();
        }
    }

    /**
     * Mettre à jour le résumé CAS 3
     */
    function updateCAS3Summary() {
        const html = `
            <p><strong>Nom:</strong> ${AppState.formData.prenom} ${AppState.formData.nom}</p>
            <p><strong>Téléphone:</strong> ${AppState.formData.telephone}</p>
            <p><strong>Panier:</strong> ${AppState.formData.panier_details}</p>
            ${AppState.formData.site_achat ? `<p><strong>Site:</strong> ${AppState.formData.site_achat}</p>` : ''}
        `;

        $('#cas3-summary').html(html);

        // Build WhatsApp contact buttons for CAS 3
        let whatsappButtons = '';
        if (colis224Frontend.agencies && colis224Frontend.agencies.length > 0) {
            const mainAgencies = colis224Frontend.agencies.slice(0, 3);
            mainAgencies.forEach(agency => {
                const phoneClean = agency.phone.replace(/[^0-9]/g, '');
                whatsappButtons += `
                    <a href="https://wa.me/${phoneClean}"
                       target="_blank"
                       rel="noopener noreferrer"
                       style="display: inline-block; margin: 4px; padding: 12px 24px; background: white; color: #25d366; text-decoration: none; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: transform 0.2s;"
                       onmouseover="this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.transform='translateY(0)'">
                        💬 ${agency.name}
                    </a>`;
            });
        }
        $('#whatsapp-cas3-buttons').html(whatsappButtons);
    }

    /**
     * Mettre à jour le résumé CAS 1
     */
    function updateCAS1Summary() {
        const html = `
            <p><strong>Nom:</strong> ${AppState.formData.prenom} ${AppState.formData.nom}</p>
            <p><strong>Téléphone:</strong> ${AppState.formData.telephone}</p>
            <p><strong>Quantités:</strong> ${AppState.formData.quantites}</p>
            <p><strong>Modèles:</strong> ${AppState.formData.modeles}</p>
            ${AppState.formData.delai ? `<p><strong>Délai:</strong> ${AppState.formData.delai}</p>` : ''}
        `;

        $('#cas1-summary').html(html);

        // Build WhatsApp contact buttons for CAS 1
        let whatsappButtons = '';
        if (colis224Frontend.agencies && colis224Frontend.agencies.length > 0) {
            const mainAgencies = colis224Frontend.agencies.slice(0, 3);
            mainAgencies.forEach(agency => {
                const phoneClean = agency.phone.replace(/[^0-9]/g, '');
                whatsappButtons += `
                    <a href="https://wa.me/${phoneClean}"
                       target="_blank"
                       rel="noopener noreferrer"
                       style="display: inline-block; margin: 4px; padding: 12px 24px; background: white; color: #25d366; text-decoration: none; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: transform 0.2s;"
                       onmouseover="this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.transform='translateY(0)'">
                        💬 ${agency.name}
                    </a>`;
            });
        }
        $('#whatsapp-cas1-buttons').html(whatsappButtons);
    }

    /**
     * Naviguer vers une étape
     */
    function goToStep(stepNumber) {
        // Hide all steps
        $('.form-step').removeClass('active').hide();

        // Show target step
        const $targetStep = $(`.form-step[data-step="${stepNumber}"]`).first();
        $targetStep.show().addClass('active');

        AppState.currentStep = stepNumber;
        updateProgressBar();

        // Scroll to top
        $('.colis224-frontend-calculator').get(0).scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * Mettre à jour la barre de progression
     */
    function updateProgressBar() {
        $('.progress-step').each(function() {
            const $step = $(this);
            const stepNum = parseInt($step.data('step'));

            $step.removeClass('active completed');

            if (stepNum === AppState.currentStep) {
                $step.addClass('active');
            } else if (stepNum < AppState.currentStep) {
                $step.addClass('completed');
            }
        });
    }

    /**
     * Soumettre le formulaire
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        console.log('Form submit triggered');

        const $form = $(this);
        const $submitBtn = $form.find('.btn-submit');

        // Save form data first
        saveFormData();
        console.log('Form data saved:', AppState.formData);

        // Validate required fields
        const nom = AppState.formData.nom;
        const prenom = AppState.formData.prenom;
        const telephone = AppState.formData.telephone;

        if (!nom || !prenom || !telephone) {
            alert('Erreur: Les informations de contact (nom, prénom, téléphone) sont obligatoires.');
            return false;
        }

        if (!isValidPhone(telephone)) {
            alert('Erreur: Le numéro de téléphone n\'est pas valide. Utilisez le format international (ex: +224 620 17 89 30)');
            return false;
        }

        // Disable submit button and show loading state
        $submitBtn.prop('disabled', true).addClass('loading');
        const originalText = $submitBtn.html();
        $submitBtn.html('⏳ Enregistrement en cours...');

        // Prepare AJAX data
        const ajaxData = {
            action: 'colis224_submit_request',
            nonce: colis224Frontend.nonce,
            ...AppState.formData
        };

        console.log('Sending AJAX request:', ajaxData);

        // Send AJAX request
        $.ajax({
            url: colis224Frontend.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    showSuccessMessage();
                } else {
                    alert('Erreur: ' + (response.data && response.data.message ? response.data.message : 'Une erreur est survenue'));
                    $submitBtn.prop('disabled', false).removeClass('loading').html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr, status, error);
                alert('Erreur de connexion. Veuillez réessayer. Détails: ' + error);
                $submitBtn.prop('disabled', false).removeClass('loading').html(originalText);
            }
        });

        return false;
    }

    /**
     * Afficher le message de succès
     */
    function showSuccessMessage() {
        // Hide form
        $('.calculator-form').hide();

        // Update success message
        let message = '';

        if (AppState.serviceType === 'cas1') {
            message = 'Votre demande a été enregistrée avec succès ! Notre équipe vous contactera dans l\'heure pour commencer la recherche auprès de nos fournisseurs.';
        } else if (AppState.serviceType === 'cas3') {
            message = 'Votre panier a été enregistré ! Notre équipe va le valider et vous contactera rapidement.';
        } else {
            message = 'Vos informations ont été enregistrées ! Vous pouvez maintenant utiliser le shipping mark et l\'adresse ci-dessus pour vos commandes.';
        }

        $('#success-message-text').text(message);

        // Build WhatsApp contacts
        let contactsHTML = '';
        colis224Frontend.agencies.forEach(agency => {
            contactsHTML += `
                <a href="https://wa.me/${agency.phone.replace(/[^0-9]/g, '')}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="whatsapp-contact">
                    <span class="whatsapp-icon">💬</span>
                    <div>
                        <div>${agency.name}</div>
                        <small>${agency.phone}</small>
                    </div>
                </a>
            `;
        });

        $('#whatsapp-contacts').html(contactsHTML);

        // Show success message
        $('.success-message').fadeIn();

        // Scroll to top
        $('.colis224-frontend-calculator').get(0).scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * Afficher confirmation agence (CAS 4)
     */
    function showAgencyConfirmation(agency) {
        // Hide form
        $('.calculator-form').hide();

        const agencyInfo = {
            'conakry': {
                name: 'Agence Conakry',
                address: 'Conakry, Guinée',
                phones: ['+224 620 17 89 30', '+224 626 526 737', '+224 626 526 735'],
                flag: '🇬🇳'
            },
            'paris': {
                name: 'Bureau Paris',
                address: '37 Rue Stephenson, 75018 Paris, France',
                phones: ['+33 6 98 48 57 52'],
                flag: '🇫🇷'
            },
            'marseille': {
                name: 'Bureau Marseille',
                address: '68 rue Longue des Capucins, 13001 Marseille, France',
                phones: ['+33 6 98 48 57 52'],
                flag: '🇫🇷'
            },
            'nice': {
                name: 'Bureau Nice',
                address: '42 Rue Gounod, 06000 Nice, France',
                phones: ['+33 6 98 48 57 52'],
                flag: '🇫🇷'
            }
        };

        const info = agencyInfo[agency];

        let message = `Parfait ! Vous avez choisi notre ${info.name}.`;

        let phonesHTML = '';
        info.phones.forEach(phone => {
            phonesHTML += `
                <a href="https://wa.me/${phone.replace(/[^0-9]/g, '')}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="whatsapp-contact">
                    <span class="whatsapp-icon">💬</span>
                    <div>
                        <div>${info.name}</div>
                        <small>${phone}</small>
                    </div>
                </a>
            `;
        });

        $('#success-message-text').html(`
            ${message}<br><br>
            <strong>${info.flag} ${info.name}</strong><br>
            📍 ${info.address}<br><br>
            Nos horaires d'ouverture :<br>
            Lundi - Samedi : 9h00 - 18h00<br>
            Dimanche : Fermé<br><br>
            <strong>Contactez-nous sur WhatsApp pour plus d'informations :</strong>
        `);

        $('#whatsapp-contacts').html(phonesHTML);

        // Show success message
        $('.success-message').fadeIn();

        // Scroll to top
        $('.colis224-frontend-calculator').get(0).scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * Redémarrer le calculateur
     */
    function handleRestart(e) {
        e.preventDefault();

        // Reset state
        AppState.currentStep = 1;
        AppState.formData = {};
        AppState.serviceType = null;
        AppState.origin = null;
        AppState.mode = null;
        AppState.destination = null;

        // Reset form
        $('#colis224-calculator-form')[0].reset();
        $('.service-card, .origin-card, .mode-card, .city-card').removeClass('selected');
        $('.error').removeClass('error');
        $('.error-message').remove();

        // Hide success message
        $('.success-message').hide();

        // Show form
        $('.calculator-form').show();

        // Go to step 1
        goToStep(1);

        // Scroll to top
        $('.colis224-frontend-calculator').get(0).scrollIntoView({ behavior: 'smooth' });
    }

})(jQuery);
