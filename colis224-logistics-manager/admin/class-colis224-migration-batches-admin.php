<?php
/**
 * Page d'administration pour la migration des lots internationaux
 *
 * @package Colis224_Logistics_Manager
 * @subpackage Admin
 * @since 2.14.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Migration_Batches_Admin {

    /**
     * Afficher la page de migration
     */
    public static function display_page() {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
        }

        // Traiter l'exécution de la migration
        $migration_result = null;
        if (isset($_POST['run_migration']) && check_admin_referer('colis224_migration_batches_nonce')) {
            $migration_result = Colis224_Migration_Batches::run_migration();
        }

        // Vérifier le statut de la migration
        $migration_status = Colis224_Migration_Batches::check_migration_status();

        ?>
        <div class="wrap">
            <h1>🚢 Migration : Gestion des Lots Internationaux</h1>

            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>Version 2.14.0 - Sprint 4</h2>

                <p><strong>Cette migration ajoute la fonctionnalité de gestion des lots internationaux (conteneurs et vols).</strong></p>

                <h3>🎯 Objectif</h3>
                <p>Permettre le regroupement des colis en lots internationaux pour faciliter le suivi des envois groupés par conteneur maritime ou vol aérien.</p>

                <h3>📋 Modifications de la Base de Données</h3>
                <ul>
                    <li>✅ <strong>Nouvelle table :</strong> <code>colis224_international_batches</code> - Stockage des lots (conteneurs/vols)</li>
                    <li>✅ <strong>Nouvelle table :</strong> <code>colis224_batch_parcels</code> - Association colis ↔ lots</li>
                    <li>✅ <strong>Colonne ajoutée :</strong> <code>batch_id</code> dans <code>colis224_parcels</code> - Référence au lot</li>
                </ul>

                <h3>🔍 Inspection Technique</h3>
                <div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">
                    <h4>Structure : Nouvelle Table <code>colis224_international_batches</code></h4>
                    <pre style="background: white; padding: 10px; overflow-x: auto;">
CREATE TABLE {prefix}_colis224_international_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) NOT NULL UNIQUE,      -- CNT-YYMMDD-XXXX ou FLT-YYMMDD-XXXX
    batch_type VARCHAR(20) NOT NULL,               -- 'container' ou 'flight'
    status VARCHAR(20) NOT NULL DEFAULT 'preparing', -- preparing, in_transit, arrived, customs, cleared, closed

    -- Informations Conteneur
    container_number VARCHAR(50),
    shipping_company VARCHAR(100),
    vessel_name VARCHAR(100),

    -- Informations Vol
    flight_number VARCHAR(50),
    airline VARCHAR(100),

    -- Dates
    departure_date DATE,
    arrival_date DATE,

    -- Statistiques
    total_parcels INT DEFAULT 0,
    total_weight DECIMAL(10,2) DEFAULT 0,
    total_value DECIMAL(10,2) DEFAULT 0,

    -- Documents
    customs_document_url VARCHAR(255),

    -- Notes
    notes TEXT,

    -- Métadonnées
    created_by BIGINT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_batch_number (batch_number),
    INDEX idx_batch_type (batch_type),
    INDEX idx_status (status),
    INDEX idx_departure_date (departure_date),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;</pre>

                    <h4>Structure : Nouvelle Table <code>colis224_batch_parcels</code></h4>
                    <pre style="background: white; padding: 10px; overflow-x: auto;">
CREATE TABLE {prefix}_colis224_batch_parcels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    parcel_id BIGINT UNSIGNED NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT UNSIGNED,

    UNIQUE KEY unique_batch_parcel (batch_id, parcel_id),
    INDEX idx_batch_id (batch_id),
    INDEX idx_parcel_id (parcel_id),
    FOREIGN KEY (batch_id) REFERENCES {prefix}_colis224_international_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;</pre>

                    <h4>Modification : Table <code>colis224_parcels</code></h4>
                    <pre style="background: white; padding: 10px; overflow-x: auto;">
ALTER TABLE {prefix}_colis224_parcels
ADD COLUMN batch_id BIGINT UNSIGNED AFTER status,
ADD INDEX idx_batch_id (batch_id);</pre>
                </div>

                <h3>✨ Fonctionnalités Ajoutées</h3>
                <ul>
                    <li><strong>Gestion des Lots :</strong> Création de lots (conteneurs maritimes ou vols aériens)</li>
                    <li><strong>Numérotation Auto :</strong> CNT-YYMMDD-XXXX pour conteneurs, FLT-YYMMDD-XXXX pour vols</li>
                    <li><strong>Affectation Colis :</strong> Association de colis à un lot avec mise à jour auto des totaux</li>
                    <li><strong>Suivi Statut :</strong> preparing → in_transit → arrived → customs → cleared → closed</li>
                    <li><strong>Calculs Automatiques :</strong> Nombre de colis, poids total, valeur totale</li>
                    <li><strong>Documents Douane :</strong> Upload de documents de dédouanement</li>
                    <li><strong>Historique :</strong> Traçabilité complète des opérations</li>
                </ul>

                <h3>🔒 Sécurité</h3>
                <ul>
                    <li>✅ Transactions SQL avec ROLLBACK automatique en cas d'erreur</li>
                    <li>✅ Contraintes de clés étrangères pour l'intégrité des données</li>
                    <li>✅ Index optimisés pour les performances</li>
                    <li>✅ Validation des données avant insertion</li>
                </ul>

                <h3>📊 Statut de la Migration</h3>
                <div style="background: <?php echo $migration_status['status'] === 'completed' ? '#d4edda' : '#fff3cd'; ?>;
                            padding: 15px; border-radius: 4px; margin: 15px 0;">
                    <?php if ($migration_status['status'] === 'completed'): ?>
                        <p style="color: #155724; margin: 0; font-size: 16px;">
                            ✅ <strong>Migration déjà effectuée</strong><br>
                            Version installée : <code><?php echo esc_html($migration_status['version']); ?></code><br>
                            Date d'installation : <code><?php echo esc_html($migration_status['installed_at']); ?></code>
                        </p>
                    <?php else: ?>
                        <p style="color: #856404; margin: 0; font-size: 16px;">
                            ⚠️ <strong>Migration non effectuée</strong><br>
                            Les tables n'existent pas encore dans la base de données.
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($migration_result !== null): ?>
                    <div style="background: <?php echo $migration_result['success'] ? '#d4edda' : '#f8d7da'; ?>;
                                padding: 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid <?php echo $migration_result['success'] ? '#28a745' : '#dc3545'; ?>;">
                        <h3 style="margin-top: 0; color: <?php echo $migration_result['success'] ? '#155724' : '#721c24'; ?>;">
                            <?php echo $migration_result['success'] ? '✅ Migration Réussie !' : '❌ Erreur de Migration'; ?>
                        </h3>
                        <p style="margin: 0; color: <?php echo $migration_result['success'] ? '#155724' : '#721c24'; ?>;">
                            <?php echo esc_html($migration_result['message']); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('colis224_migration_batches_nonce'); ?>

                    <?php if ($migration_status['status'] !== 'completed'): ?>
                        <button type="submit" name="run_migration" class="button button-primary button-hero">
                            🚀 Exécuter la Migration
                        </button>
                        <p class="description">Cette opération créera les nouvelles tables et colonnes nécessaires.</p>
                    <?php else: ?>
                        <button type="submit" name="run_migration" class="button button-secondary"
                                onclick="return confirm('La migration a déjà été effectuée. Voulez-vous la ré-exécuter ? (Cela ne supprimera pas les données existantes)');">
                            🔄 Ré-exécuter la Migration
                        </button>
                        <p class="description">La migration a déjà été effectuée. Vous pouvez la ré-exécuter si nécessaire.</p>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h3>📚 Documentation Technique</h3>

                <h4>Types de Lots</h4>
                <ul>
                    <li><strong>Conteneur Maritime (container) :</strong> Grands volumes, transit plus long</li>
                    <li><strong>Vol Aérien (flight) :</strong> Petits volumes, transit rapide</li>
                </ul>

                <h4>Cycle de Vie d'un Lot</h4>
                <ol>
                    <li><strong>preparing</strong> - En préparation, ajout de colis</li>
                    <li><strong>in_transit</strong> - En transit vers la destination</li>
                    <li><strong>arrived</strong> - Arrivé à destination</li>
                    <li><strong>customs</strong> - En dédouanement</li>
                    <li><strong>cleared</strong> - Dédouané, prêt pour distribution</li>
                    <li><strong>closed</strong> - Lot fermé, tous les colis distribués</li>
                </ol>

                <h4>Usage de la Fonctionnalité</h4>
                <p>Accédez au menu <strong>🚢 Lots Internationaux</strong> pour :</p>
                <ul>
                    <li>Créer un nouveau lot (conteneur ou vol)</li>
                    <li>Assigner des colis à un lot</li>
                    <li>Suivre le statut du lot</li>
                    <li>Uploader des documents de douane</li>
                    <li>Consulter les statistiques (nombre de colis, poids, valeur)</li>
                </ul>
            </div>
        </div>
        <?php
    }
}
