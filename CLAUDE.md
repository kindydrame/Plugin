# CLAUDE.md - Colis224 Logistics Manager

## Project Overview

**Colis224 Logistics Manager** is a professional WordPress plugin providing a complete logistics management system for international delivery companies operating between China, France, Morocco, Senegal, Ivory Coast, and Guinea.

- **Type**: WordPress Plugin
- **Version**: 2.16.6
- **Language**: PHP 8.0+
- **WordPress**: 6.0+
- **MySQL**: 5.7+
- **License**: GPL-2.0+
- **Text Domain**: `colis224-logistics`

## Directory Structure

```
colis224-logistics-manager/
├── colis224-logistics-manager.php    # Main plugin file (entry point)
├── README.md                         # User documentation
├── BUGFIXES.md                       # Bug fixes changelog
├── CHANGELOG.md                      # Version history
│
├── includes/                         # Core functionality classes
│   ├── class-colis224-activator.php         # Plugin activation
│   ├── class-colis224-deactivator.php       # Plugin deactivation
│   ├── class-colis224-database.php          # Database schema (25+ tables)
│   ├── class-colis224-admin.php             # Admin initialization
│   ├── class-colis224-sanitizer.php         # Input sanitization
│   ├── class-colis224-permissions.php       # Role-based access control
│   ├── class-colis224-client-auth.php       # Client authentication
│   ├── class-colis224-client-portal-enhanced.php  # Client portal
│   ├── class-colis224-frontend-portal.php   # Frontend interface
│   ├── class-colis224-rest-api.php          # REST API endpoints
│   ├── class-colis224-departures.php        # Shipment/departure tracking
│   ├── class-colis224-batches.php           # Batch processing
│   ├── class-colis224-live-chat.php         # Real-time chat
│   ├── class-colis224-notifications.php     # Email/SMS notifications
│   ├── class-colis224-loyalty.php           # Loyalty program
│   ├── class-colis224-warehouses.php        # Warehouse management
│   ├── class-colis224-invoice.php           # Invoice generation
│   ├── class-colis224-qrcode.php            # QR code generation
│   └── ... (50+ total classes)
│
├── admin/                            # Admin interface classes
│   ├── class-colis224-dashboard.php         # Admin dashboard
│   ├── class-colis224-colis.php             # Parcel management UI
│   ├── class-colis224-clients.php           # Client management
│   ├── class-colis224-partenaires.php       # Partner management
│   ├── class-colis224-equipe.php            # Team management
│   ├── class-colis224-comptabilite.php      # Accounting UI
│   ├── class-colis224-rapports.php          # Reports
│   ├── class-colis224-parametres.php        # Settings
│   └── ... (26+ total classes)
│
└── assets/
    ├── css/                          # Stylesheets
    │   ├── admin-style.css           # Admin interface
    │   ├── frontend-style.css        # Client portal
    │   ├── departures-slider.css     # Slider component
    │   ├── toast-notifications.css   # Notifications
    │   ├── confirm-modal.css         # Modal dialogs
    │   └── tooltips.css              # Tooltips
    │
    └── js/                           # JavaScript
        ├── admin-script.js           # Admin functionality
        ├── frontend-script.js        # Frontend interactions
        ├── departures-slider.js      # Slider component
        ├── confirm-modal.js          # Modal dialogs
        └── toast-notifications.js    # Toast notifications
```

## Build & Development

### No Build System Required
This is a standard WordPress plugin with no external build tools (no npm, webpack, or grunt). Development is straightforward PHP editing.

### Installation
```bash
# Extract the plugin
cd wp-content/plugins/
unzip colis224-logistics-manager.zip

# Activate via WordPress Admin or WP-CLI
wp plugin activate colis224-logistics-manager
```

### Database
Tables are automatically created/updated on plugin activation via `Colis224_Database::create_tables()`. Version checks ensure schema migrations occur when updating.

## Key Architecture Patterns

### Singleton Pattern (Main Plugin)
```php
class Colis224_Logistics_Manager {
    protected static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### Class Naming Convention
- All classes use `Colis224_` prefix
- Format: `Colis224_Feature_Name` (e.g., `Colis224_Live_Chat`)
- File naming: `class-colis224-feature-name.php`

### Security Guard
All PHP files start with:
```php
if (!defined('ABSPATH')) {
    exit;
}
```

## Coding Conventions

### PHP Standards
- **PHP Version**: 8.0+ required
- **WordPress Coding Standards**: Follow WordPress PHP coding standards
- **Class-based organization**: Each feature in its own class
- **Static methods**: Commonly used for display methods and utilities
- **Hooks-based architecture**: Use WordPress action/filter hooks

### Input Sanitization
Always use the `Colis224_Sanitizer` class for data cleaning:
```php
// For text input
$name = Colis224_Sanitizer::clean_name($_POST['nom']);
$email = Colis224_Sanitizer::clean_email($_POST['email']);
$phone = Colis224_Sanitizer::clean_phone($_POST['telephone']);
$amount = Colis224_Sanitizer::clean_amount($_POST['montant']);

// Bulk cleaning
$data = Colis224_Sanitizer::clean_post_data(array(
    'nom' => 'name',
    'email' => 'email',
    'telephone' => 'phone',
    'montant' => 'amount'
));
```

### Database Operations
Always use WordPress `$wpdb` with prepared statements:
```php
global $wpdb;
$table = $wpdb->prefix . 'colis224_clients';

// Insert
$wpdb->insert($table, array('nom' => $nom, 'email' => $email), array('%s', '%s'));

// Select with prepare
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table WHERE id = %d",
    $id
));
```

### Nonce Verification
All forms must include nonce verification:
```php
// In form
wp_nonce_field('colis224_action_name', 'colis224_nonce');

// In handler
if (!wp_verify_nonce($_POST['colis224_nonce'], 'colis224_action_name')) {
    wp_die('Security check failed');
}
```

### Permission Checks
Use the `Colis224_Permissions` class for access control:
```php
// Check capability
if (!current_user_can('colis224_manage_all')) {
    wp_die('Access denied');
}

// Get user role
$user_role = Colis224_Permissions::get_user_colis224_role();
```

### User Roles
| Role | Capabilities |
|------|-------------|
| `colis224_manager` | Full access to all features |
| `colis224_agent` | Create/view parcels, manage clients |
| `colis224_driver` | View assigned parcels, update delivery status |
| `colis224_accountant` | View reports, manage accounting |

## JavaScript Conventions

### Navigation Functions
Use the custom navigation functions for smooth transitions:
```javascript
// Navigate to a page
colis224Navigate('colis224-dashboard', { updated: 'true' });

// Soft reload preserving scroll position
colis224SoftReload();
```

### AJAX Calls
Use WordPress AJAX with nonce:
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'colis224_action_name',
        nonce: colis224_data.nonce,
        // ... other data
    },
    success: function(response) {
        if (response.success) {
            // Handle success
        }
    }
});
```

## Database Tables (25+)

### Core Tables
- `wp_colis224_clients` - Customer profiles
- `wp_colis224_parcels` - Parcel/shipment records
- `wp_colis224_countries` - Destination countries
- `wp_colis224_transport_modes` - Shipping methods

### Team & Operations
- `wp_colis224_team_members` - Office staff
- `wp_colis224_drivers` - Delivery drivers

### Partner & Financial
- `wp_colis224_partners` - Partner agencies
- `wp_colis224_revenues` - Income tracking
- `wp_colis224_expenses` - Expense tracking
- `wp_colis224_payments` - Payment records

### Customer Engagement
- `wp_colis224_loyalty_members` - Loyalty program
- `wp_colis224_chat_conversations` - Live chat
- `wp_colis224_notifications` - Notifications

## Constants

```php
COLIS224_VERSION          // Current plugin version (e.g., '2.16.6')
COLIS224_PLUGIN_DIR       // Absolute path to plugin directory
COLIS224_PLUGIN_URL       // URL to plugin directory
COLIS224_PLUGIN_BASENAME  // Plugin basename for hooks
```

## Key Entry Points

### Admin Menu Pages
- `colis224-dashboard` - Main dashboard
- `colis224-parcels` - Parcel management
- `colis224-clients` - Client management
- `colis224-partners` - Partner management
- `colis224-team` - Team management
- `colis224-accounting` - Accounting
- `colis224-reports` - Reports
- `colis224-settings` - Settings

### Shortcodes
- `[colis224_tracking]` - Public tracking form
- `[colis224_client_portal]` - Client portal
- `[colis224_departures_slider]` - Departures slider widget

### AJAX Actions
All AJAX actions are prefixed with `colis224_`:
- `colis224_create_parcel`
- `colis224_update_status`
- `colis224_search_client`
- `colis224_send_notification`
- etc.

## Currency Support

The plugin supports multi-currency:
- **GNF** (Franc Guineen) - Default currency
- **EUR** (Euro)
- **USD** (US Dollar)

Exchange rates are configurable in Settings.

## Common Tasks

### Adding a New Admin Page
1. Create class file in `admin/class-colis224-yourfeature.php`
2. Add to menu in `Colis224_Admin::add_plugin_admin_menu()`
3. Register hooks in main plugin file

### Adding a New Feature Class
1. Create class file in `includes/class-colis224-yourfeature.php`
2. Add `require_once` in main plugin file's `load_dependencies()`
3. Initialize in `init_frontend()` if needed

### Creating Database Migrations
1. Add table creation to `Colis224_Database::create_tables()`
2. Use version checks for conditional migrations:
```php
if (version_compare($installed_version, '2.x.x', '<')) {
    // Run migration
}
```

## Testing

No automated test suite is present. Test manually in a WordPress environment:
1. Install on a test WordPress site
2. Activate plugin
3. Verify database tables created
4. Test CRUD operations for each module

## Important Notes for AI Assistants

1. **Always sanitize input** using `Colis224_Sanitizer` class
2. **Always verify nonces** for form submissions
3. **Check permissions** before sensitive operations
4. **Use prepared statements** for all database queries
5. **Follow WordPress coding standards** for PHP
6. **Maintain bilingual support** (French is primary language in UI)
7. **Test database changes** carefully as many tables are interconnected
8. **Preserve backward compatibility** when modifying database schema

## Files to Never Modify Without Caution

- `colis224-logistics-manager.php` - Main entry point
- `includes/class-colis224-database.php` - Database schema
- `includes/class-colis224-permissions.php` - Access control

## Documentation Files

- `README.md` - User documentation
- `BUGFIXES.md` - Bug fixes log
- `CHANGELOG.md` - Detailed version history
