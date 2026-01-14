<?php
/**
 * Classe de désactivation du plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Deactivator {

    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Note: On ne supprime pas les données lors de la désactivation
        // Pour supprimer complètement les données, utilisez la désinstallation
    }
}
