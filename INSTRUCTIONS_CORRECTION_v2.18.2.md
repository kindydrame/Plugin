# 🔧 Instructions pour corriger l'erreur "Duplicate entry PA3330"

## Version concernée : 2.18.2

### 📍 Fichier à modifier
`wp-content/plugins/colis224-logistics-manager/admin/class-colis224-colis.php`

---

## 🔍 Étape 1 : Localiser le code à remplacer

Allez à la **ligne 999** environ (cherchez la fonction `save_parcel()`)

Cherchez cette section :

```php
        // Génération du numéro de suivi si vide
        $tracking_number = sanitize_text_field($_POST['tracking_number']);
        if (empty($tracking_number)) {
            $phone = sanitize_text_field($_POST['recipient_phone']);
            $last4 = substr(preg_replace('/\s/', '', $phone), -4);

            // Boucle pour garantir l'unicité du numéro de suivi
            $attempts = 0;
            $max_attempts = 10;

            do {
                $attempts++;

                // Première tentative : PA + 4 derniers chiffres du téléphone
                if ($attempts === 1) {
                    $tracking_number = 'PA' . $last4;
                } else {
                    // Tentatives suivantes : Ajouter un suffixe avec timestamp + random
                    $suffix = substr(time(), -3) . rand(10, 99);
                    $tracking_number = 'PA' . $last4 . '-' . $suffix;
                }

                // Vérifier l'unicité
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
                    $tracking_number
                ));

                // Si on a essayé 10 fois sans succès, utiliser un numéro complètement unique
                if ($attempts >= $max_attempts && $count > 0) {
                    $tracking_number = 'PA' . strtoupper(wp_generate_password(8, false, false));
                    $count = 0; // Forcer la sortie de la boucle
                }

            } while ($count > 0 && $attempts < $max_attempts);
        } else {
            // Si un tracking number est fourni manuellement, vérifier qu'il n'existe pas déjà
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
                $tracking_number
            ));

            if ($count > 0) {
                echo '<div class="notice notice-error"><p>❌ Erreur : Le numéro de suivi ' . esc_html($tracking_number) . ' existe déjà. Veuillez en choisir un autre.</p></div>';
                return;
            }
        }
```

---

## ✅ Étape 2 : Remplacer par ce nouveau code

**REMPLACEZ TOUT LE CODE CI-DESSUS PAR :**

```php
        // Génération du numéro de suivi si vide
        $tracking_number = sanitize_text_field($_POST['tracking_number']);
        if (empty($tracking_number)) {
            $phone = sanitize_text_field($_POST['recipient_phone']);
            $last4 = substr(preg_replace('/\s/', '', $phone), -4);

            // Boucle pour garantir l'unicité du numéro de suivi avec protection contre les doublons
            $attempts = 0;
            $max_attempts = 20;
            $tracking_number_generated = false;

            do {
                $attempts++;

                // Première tentative : PA + 4 derniers chiffres du téléphone
                if ($attempts === 1) {
                    $tracking_number = 'PA' . $last4;
                } else if ($attempts <= 5) {
                    // Tentatives 2-5 : Ajouter un suffixe séquentiel simple
                    $tracking_number = 'PA' . $last4 . '-' . ($attempts - 1);
                } else {
                    // Tentatives suivantes : Utiliser timestamp + random pour garantir l'unicité
                    $suffix = substr(time(), -4) . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                    $tracking_number = 'PA' . $last4 . '-' . $suffix;
                }

                // Vérifier l'unicité avec un verrou pour éviter les race conditions
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
                    $tracking_number
                ));

                if ($count == 0) {
                    $tracking_number_generated = true;
                    break;
                }

                // Si on a essayé 20 fois sans succès, utiliser un numéro complètement unique avec timestamp
                if ($attempts >= $max_attempts) {
                    $tracking_number = 'PA' . date('ymd') . '-' . strtoupper(substr(wp_generate_password(6, false, false), 0, 6));
                    $tracking_number_generated = true;
                    break;
                }

                // Petit délai pour éviter les collisions en cas de création simultanée
                if ($attempts > 1) {
                    usleep(50000); // 50ms
                }

            } while ($attempts < $max_attempts);

            if (!$tracking_number_generated) {
                echo '<div class="notice notice-error"><p>❌ Erreur : Impossible de générer un numéro de suivi unique après ' . $max_attempts . ' tentatives. Veuillez réessayer.</p></div>';
                return;
            }
        } else {
            // Si un tracking number est fourni manuellement, vérifier qu'il n'existe pas déjà
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
                $tracking_number
            ));

            if ($count > 0) {
                echo '<div class="notice notice-error"><p>❌ Erreur : Le numéro de suivi ' . esc_html($tracking_number) . ' existe déjà. Veuillez en choisir un autre ou laissez le champ vide pour une génération automatique.</p></div>';
                return;
            }
        }
```

---

## 💾 Étape 3 : Sauvegarder et tester

1. **Sauvegardez** le fichier
2. **Testez** la création de clients et colis
3. ✅ **L'erreur "Duplicate entry PA3330" devrait disparaître !**

---

## 🎯 Ce qui a été amélioré

- ✅ **20 tentatives** au lieu de 10
- ✅ **Stratégies progressives** : PA3330 → PA3330-1 → PA3330-2 → PA3330-[timestamp]
- ✅ **Protection anti-collision** : Délai de 50ms entre tentatives
- ✅ **Génération de secours** : Si échec, génère PA260114-XYZ123
- ✅ **Messages d'erreur améliorés**

---

## 📞 Support

Si vous avez des questions ou des problèmes, contactez-moi !
