<?php
/**
 * Détection et fusion des clients dupliqués
 *
 * @package Colis224_Logistics
 * @version 2.13.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Duplicate_Detector {

    // Seuil de similarité pour considérer comme doublon (0-100%)
    const SIMILARITY_THRESHOLD = 80;

    /**
     * Détecter les clients dupliqués
     *
     * @param int $limit Nombre maximum de groupes à retourner
     * @return array Groupes de clients dupliqués
     */
    public static function detect_duplicates($limit = 50) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Récupérer tous les clients actifs
        $clients = $wpdb->get_results("
            SELECT id, name, phone, email, address
            FROM $table_clients
            WHERE is_active = 1
            ORDER BY id ASC
        ");

        if (empty($clients)) {
            return array();
        }

        $duplicate_groups = array();
        $processed_ids = array();

        foreach ($clients as $client1) {
            // Si déjà traité, passer
            if (in_array($client1->id, $processed_ids)) {
                continue;
            }

            $group = array($client1);
            $group_ids = array($client1->id);

            // Comparer avec les autres clients
            foreach ($clients as $client2) {
                if ($client1->id === $client2->id) {
                    continue;
                }

                if (in_array($client2->id, $processed_ids)) {
                    continue;
                }

                // Calculer la similarité
                $similarity = self::calculate_similarity($client1, $client2);

                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    $group[] = $client2;
                    $group_ids[] = $client2->id;
                }
            }

            // Si au moins 2 clients similaires
            if (count($group) >= 2) {
                $duplicate_groups[] = array(
                    'clients' => $group,
                    'similarity' => self::calculate_group_similarity($group),
                    'count' => count($group)
                );

                // Marquer comme traités
                $processed_ids = array_merge($processed_ids, $group_ids);
            }

            // Limiter le nombre de groupes
            if (count($duplicate_groups) >= $limit) {
                break;
            }
        }

        // Trier par nombre de doublons (descendant)
        usort($duplicate_groups, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return $duplicate_groups;
    }

    /**
     * Calculer la similarité entre deux clients
     *
     * @param object $client1
     * @param object $client2
     * @return float Score de similarité (0-100)
     */
    private static function calculate_similarity($client1, $client2) {
        $scores = array();

        // Similarité du nom (poids: 50%)
        $name_similarity = self::string_similarity(
            self::normalize_string($client1->name),
            self::normalize_string($client2->name)
        );
        $scores['name'] = $name_similarity * 0.5;

        // Similarité du téléphone (poids: 40%)
        $phone_similarity = self::phone_similarity($client1->phone, $client2->phone);
        $scores['phone'] = $phone_similarity * 0.4;

        // Similarité de l'email (poids: 10%)
        if (!empty($client1->email) && !empty($client2->email)) {
            $email_similarity = self::string_similarity(
                strtolower($client1->email),
                strtolower($client2->email)
            );
            $scores['email'] = $email_similarity * 0.1;
        } else {
            $scores['email'] = 0;
        }

        return array_sum($scores);
    }

    /**
     * Calculer la similarité moyenne d'un groupe
     *
     * @param array $group
     * @return float
     */
    private static function calculate_group_similarity($group) {
        if (count($group) < 2) {
            return 0;
        }

        $total = 0;
        $count = 0;

        for ($i = 0; $i < count($group); $i++) {
            for ($j = $i + 1; $j < count($group); $j++) {
                $total += self::calculate_similarity($group[$i], $group[$j]);
                $count++;
            }
        }

        return $count > 0 ? $total / $count : 0;
    }

    /**
     * Normaliser une chaîne de caractères
     *
     * @param string $str
     * @return string
     */
    private static function normalize_string($str) {
        // Convertir en minuscules
        $str = mb_strtolower($str, 'UTF-8');

        // Supprimer les accents
        $str = self::remove_accents($str);

        // Supprimer les caractères spéciaux
        $str = preg_replace('/[^a-z0-9\s]/', '', $str);

        // Supprimer les espaces multiples
        $str = preg_replace('/\s+/', ' ', $str);

        return trim($str);
    }

    /**
     * Supprimer les accents
     *
     * @param string $str
     * @return string
     */
    private static function remove_accents($str) {
        $accents = array(
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n'
        );

        return strtr($str, $accents);
    }

    /**
     * Calculer la similarité entre deux chaînes (Levenshtein)
     *
     * @param string $str1
     * @param string $str2
     * @return float Score (0-100)
     */
    private static function string_similarity($str1, $str2) {
        if (empty($str1) || empty($str2)) {
            return 0;
        }

        if ($str1 === $str2) {
            return 100;
        }

        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        $max_len = max($len1, $len2);

        if ($max_len === 0) {
            return 100;
        }

        $distance = levenshtein($str1, $str2);
        $similarity = (1 - ($distance / $max_len)) * 100;

        return max(0, $similarity);
    }

    /**
     * Calculer la similarité entre deux numéros de téléphone
     *
     * @param string $phone1
     * @param string $phone2
     * @return float Score (0-100)
     */
    private static function phone_similarity($phone1, $phone2) {
        // Normaliser les téléphones
        $phone1_clean = self::normalize_phone($phone1);
        $phone2_clean = self::normalize_phone($phone2);

        if (empty($phone1_clean) || empty($phone2_clean)) {
            return 0;
        }

        // Correspondance exacte
        if ($phone1_clean === $phone2_clean) {
            return 100;
        }

        // Vérifier si l'un contient l'autre (numéros internationaux vs locaux)
        if (strpos($phone1_clean, $phone2_clean) !== false || strpos($phone2_clean, $phone1_clean) !== false) {
            return 95;
        }

        // Vérifier les 8 derniers chiffres (numéros mobiles guinéens)
        $suffix1 = substr($phone1_clean, -8);
        $suffix2 = substr($phone2_clean, -8);

        if ($suffix1 === $suffix2 && strlen($suffix1) === 8) {
            return 90;
        }

        // Similarité de chaîne classique
        return self::string_similarity($phone1_clean, $phone2_clean);
    }

    /**
     * Normaliser un numéro de téléphone
     *
     * @param string $phone
     * @return string
     */
    private static function normalize_phone($phone) {
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Supprimer les zéros initiaux et les préfixes pays
        $phone = ltrim($phone, '0');
        $phone = preg_replace('/^224/', '', $phone); // Guinée
        $phone = preg_replace('/^33/', '', $phone);  // France
        $phone = preg_replace('/^212/', '', $phone); // Maroc
        $phone = preg_replace('/^221/', '', $phone); // Sénégal
        $phone = preg_replace('/^225/', '', $phone); // Côte d'Ivoire
        $phone = preg_replace('/^86/', '', $phone);  // Chine

        return $phone;
    }

    /**
     * Fusionner plusieurs clients en un seul
     *
     * @param int $master_id ID du client à conserver
     * @param array $duplicate_ids IDs des clients à fusionner
     * @param array $field_choices Choix des champs à conserver
     * @return array Résultat
     */
    public static function merge_clients($master_id, $duplicate_ids, $field_choices = array()) {
        global $wpdb;

        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_loyalty = $wpdb->prefix . 'colis224_loyalty_points';

        // Vérifier que le master existe
        $master = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_clients WHERE id = %d", $master_id));
        if (!$master) {
            return array('success' => false, 'message' => 'Client principal introuvable.');
        }

        // Démarrer une transaction
        $wpdb->query('START TRANSACTION');

        try {
            $current_user = wp_get_current_user();
            $merged_data = array();

            foreach ($duplicate_ids as $dup_id) {
                if ($dup_id == $master_id) {
                    continue;
                }

                // Récupérer le doublon
                $duplicate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_clients WHERE id = %d", $dup_id));
                if (!$duplicate) {
                    continue;
                }

                // Transférer tous les colis vers le client principal
                $wpdb->update(
                    $table_parcels,
                    array('client_id' => $master_id),
                    array('client_id' => $dup_id),
                    array('%d'),
                    array('%d')
                );

                // Transférer les points de fidélité
                $wpdb->update(
                    $table_loyalty,
                    array('client_id' => $master_id),
                    array('client_id' => $dup_id),
                    array('%d'),
                    array('%d')
                );

                // Enregistrer dans l'historique de fusion
                self::log_merge($master_id, $dup_id, $duplicate, $current_user->ID);

                // Marquer le doublon comme inactif (soft delete)
                $wpdb->update(
                    $table_clients,
                    array('is_active' => 0),
                    array('id' => $dup_id),
                    array('%d'),
                    array('%d')
                );

                $merged_data[] = $duplicate;
            }

            // Mettre à jour les champs choisis du client principal
            if (!empty($field_choices)) {
                $update_data = array();

                foreach ($field_choices as $field => $source_id) {
                    if ($source_id == $master_id) {
                        continue;
                    }

                    // Trouver le client source
                    $source = null;
                    foreach ($merged_data as $dup) {
                        if ($dup->id == $source_id) {
                            $source = $dup;
                            break;
                        }
                    }

                    if ($source && isset($source->$field)) {
                        $update_data[$field] = $source->$field;
                    }
                }

                if (!empty($update_data)) {
                    $wpdb->update($table_clients, $update_data, array('id' => $master_id));
                }
            }

            $wpdb->query('COMMIT');

            return array(
                'success' => true,
                'message' => sprintf('✅ %d client(s) fusionné(s) avec succès.', count($merged_data)),
                'merged_count' => count($merged_data)
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur lors de la fusion : ' . $e->getMessage()
            );
        }
    }

    /**
     * Enregistrer la fusion dans l'historique
     *
     * @param int $master_id
     * @param int $duplicate_id
     * @param object $duplicate_data
     * @param int $user_id
     */
    private static function log_merge($master_id, $duplicate_id, $duplicate_data, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_client_merges';

        $wpdb->insert($table, array(
            'master_client_id' => $master_id,
            'merged_client_id' => $duplicate_id,
            'merged_client_data' => json_encode($duplicate_data),
            'merged_by' => $user_id,
            'merged_at' => current_time('mysql')
        ));
    }

    /**
     * Vérifier si un nouveau client est un doublon potentiel
     *
     * @param string $name
     * @param string $phone
     * @return array|null Client similaire ou null
     */
    public static function check_potential_duplicate($name, $phone) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $clients = $wpdb->get_results("
            SELECT id, name, phone, email
            FROM $table_clients
            WHERE is_active = 1
        ");

        $new_client = (object) array(
            'name' => $name,
            'phone' => $phone,
            'email' => ''
        );

        foreach ($clients as $existing) {
            $similarity = self::calculate_similarity($new_client, $existing);

            if ($similarity >= self::SIMILARITY_THRESHOLD) {
                return array(
                    'client' => $existing,
                    'similarity' => round($similarity, 1)
                );
            }
        }

        return null;
    }
}
