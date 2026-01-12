<?php
/**
 * Script de vérification des permissions utilisateur
 * À exécuter depuis WordPress admin ou via WP-CLI
 */

// Simuler l'utilisateur connecté "lambanyi"
$user = get_user_by('login', 'lambanyi');

if (!$user) {
    echo "❌ Utilisateur 'lambanyi' introuvable\n";
    exit;
}

echo "✅ Utilisateur trouvé: {$user->display_name} (ID: {$user->ID})\n\n";

echo "📋 RÔLES:\n";
foreach ($user->roles as $role) {
    echo "  - $role\n";
}

echo "\n🔑 CAPABILITIES IMPORTANTES:\n";
$caps_to_check = [
    'colis224_manage_all',
    'administrator',
    'manage_options',
    'edit_posts',
    'edit_pages'
];

foreach ($caps_to_check as $cap) {
    $has_cap = user_can($user, $cap) ? '✅ OUI' : '❌ NON';
    echo "  - $cap: $has_cap\n";
}

echo "\n🎯 VERDICT:\n";
if (user_can($user, 'colis224_manage_all') || 
    user_can($user, 'administrator') || 
    user_can($user, 'manage_options')) {
    echo "  ✅ L'utilisateur DEVRAIT voir le dashboard agent\n";
} else {
    echo "  ❌ L'utilisateur N'A PAS les permissions nécessaires\n";
    echo "  💡 SOLUTION: Attribuer le rôle 'administrator' ou ajouter la capability 'colis224_manage_all'\n";
}
