# CLAUDE.md

Instructions for a new developer or AI coding agent working on the Enquiry Manager plugin.

## Project Purpose

A self-contained WordPress plugin that provides:
1. A frontend enquiry submission form (shortcode)
2. WordPress REST API endpoint for form submission
3. Admin dashboard for enquiry management (CRUD, search, filter, pagination)
4. Email notifications for new enquiries
5. Settings page for notification email configuration

## Plugin Architecture

The plugin follows a modular OOP architecture with a singleton bootstrap pattern. Each concern is separated into its own class. No Composer, no external dependencies.

**Prefix**: All identifiers use the `em_` prefix (`EM_`, `em-`).

## File Structure

```
enquiry-manager/
├── enquiry-manager.php        # Plugin header, constants, loads EM_Plugin
├── uninstall.php              # Runs on plugin deletion; drops table, removes options
├── includes/
│   ├── class-em-plugin.php    # Singleton bootstrap, loads all classes, registers hooks
│   ├── class-em-database.php  # Table creation (dbDelta), CRUD operations
│   ├── class-em-rest-api.php  # REST route registration, submission handling
│   ├── class-em-admin.php     # Admin menu, listing, detail, actions
│   ├── class-em-settings.php  # Settings API registration, notification email field
│   └── class-em-frontend.php  # Shortcode [enquiry_form], asset enqueueing
├── admin/
│   ├── css/admin.css          # Admin table, detail view, status badge styles
│   └── js/admin.js            # Delete confirmation dialog
└── public/
    ├── css/frontend.css       # Form, input, button, error, notice styles
    └── js/frontend.js         # Form validation and Fetch API AJAX submission
```

## Responsibility of Each Class

### EM_Plugin (class-em-plugin.php)
- Singleton instance
- Loads all dependency classes
- Hooks: `plugins_loaded` (init), `rest_api_init`, `init` (shortcode), `wp_enqueue_scripts`, `admin_menu`, `admin_enqueue_scripts`, `admin_init`
- Registers activation hook on bootstrap file

### EM_Database (class-em-database.php)
- `activate()`: Creates table via `dbDelta()`, stores `em_db_version` option
- `table_name()`: Returns `$wpdb->prefix . 'enquiries'` (static)
- `insert(array $data)`: Inserts enquiry, returns insert ID or 0
- `get_by_id(int $id)`: Returns single row object or null
- `update_status(int $id, string $status)`: Updates status against whitelist
- `delete(int $id)`: Deletes by ID
- `get_all(array $args)`: Returns enquiry rows with pagination, search, filtering
- `count_all(array $args)`: Returns total count for pagination

### EM_Rest_API (class-em-rest-api.php)
- `register_routes()`: Registers `POST /wp-json/enquiry-manager/v1/enquiries`
- `handle_submission()`: Validates, sanitizes, captures IP, inserts, sends notification
- `get_client_ip()`: Reads `$_SERVER['REMOTE_ADDR']` only
- `send_notification()`: Uses `wp_mail()` to configured email

### EM_Admin (class-em-admin.php)
- `add_menu_pages()`: Menu: Enquiries (listing), hidden Detail page, Settings
- `render_list_page()`: Full admin listing with search, filter, pagination, action buttons
- `render_detail_page()`: Single enquiry detail view
- `handle_admin_actions()`: Processes status changes and deletes, validates nonces/caps
- Helper function `em_wp_kses_swp()`: Custom kses filter for pagination links

### EM_Settings (class-em-settings.php)
- `register_settings()`: Registers `em_notification_email` setting and field
- `sanitize_notification_email()`: Validates and sanitizes email

### EM_Frontend (class-em-frontend.php)
- `register_shortcode()`: Registers `[enquiry_form]`
- `enqueue_assets()`: Conditionally loads CSS/JS only when shortcode is present
- `render_form()`: Outputs the HTML form
- Localizes `EM_Frontend` JS object with REST URL, nonce, and translatable strings

## Plugin Bootstrap Flow

1. `enquiry-manager.php` defines constants, loads `EM_Plugin`, registers activation hook directly via `register_activation_hook( __FILE__, ... )`
2. `plugins_loaded` hook fires `em_init()` → `EM_Plugin::instance()`
3. Constructor loads all dependency files, instantiates all classes
4. Hooks registered for textdomain, REST API, shortcode, assets, admin menu, settings

## Activation Flow

1. `EM_Database::activate_on_hook()` (static) fires on activation — registered from main plugin file via `register_activation_hook( __FILE__, array( 'EM_Database', 'activate_on_hook' ) )`
2. Creates a new `EM_Database` instance, calls `create_table()`
3. Loads `wp-admin/includes/upgrade.php`
4. Runs `dbDelta()` with CREATE TABLE SQL (note: PRIMARY KEY requires 2 spaces before `(` per dbDelta spec)
5. Stores `em_db_version` option

## Uninstall Flow

1. `uninstall.php` fires on full deletion
2. Drops `{$wpdb->prefix}enquiries` table
3. Deletes options: `em_db_version`, `em_notification_email`, `em_items_per_page`

## Database Schema

**Table**: `{$wpdb->prefix}enquiries`

| Column | Type | Notes |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT | PRIMARY KEY |
| name | VARCHAR(255) NOT NULL | |
| email | VARCHAR(255) NOT NULL | INDEXED |
| phone | VARCHAR(50) NOT NULL | Default empty string |
| subject | VARCHAR(255) NOT NULL | |
| message | LONGTEXT NOT NULL | |
| status | VARCHAR(20) NOT NULL DEFAULT 'new' | INDEXED; values: new, read, archived |
| submitted_ip | VARCHAR(45) NOT NULL | Supports IPv4 and IPv6 |
| created_at | DATETIME NOT NULL | INDEXED |

## Database Table Naming Convention

Always use `$wpdb->prefix . 'enquiries'`. Never hardcode `wp_`.

## Database Version Strategy

`em_db_version` option stores the current schema version. Future schema migrations should check this value and run upgrades if the stored version is older.

## Shortcode

`[enquiry_form]`

Renders a complete enquiry form. JS/CSS assets are only loaded on pages containing this shortcode (via `has_shortcode()` check).

## REST Namespace and Endpoint

- **Namespace**: `enquiry-manager/v1`
- **Endpoint**: `POST /wp-json/enquiry-manager/v1/enquiries`
- **Permission callback**: `__return_true` (public endpoint)
- **Nonce**: `wp_create_nonce('wp_rest')` sent as `X-WP-Nonce` header

## REST Request/Response

### Request Parameters (POST body as URL-encoded form data)
| Parameter | Required | Max Length |
|---|---|---|
| name | Yes | 255 |
| email | Yes | 255 |
| phone | No | 50 |
| subject | Yes | 255 |
| message | Yes | 10000 (min 10) |

### Success Response (201)
```json
{
  "success": true,
  "message": "Your enquiry has been submitted successfully."
}
```

### Error Response (400/500)
```json
{
  "success": false,
  "message": "Human-readable error message."
}
```

## Nonce Handling

- **REST API**: Standard WordPress REST nonce (`wp_create_nonce('wp_rest')`) sent as `X-WP-Nonce` header. The `handle_submission()` callback explicitly verifies the nonce via `wp_verify_nonce()` and returns 403 on failure. This provides CSRF protection for both logged-in and anonymous users.
- **Admin actions**: Custom nonces named `em_admin_action_{action}_{id}` created per action+ID combination.

## Validation Rules

### Server-side (PHP — class-em-rest-api.php)
Same rules as client-side, enforced independently. All inputs are sanitized with `sanitize_text_field()` / `sanitize_email()` / `sanitize_textarea_field()` and wp_unslash() before validation.

### Client-side (JS — public/js/frontend.js)
- Name: Required, max 255 chars
- Email: Required, valid email regex, max 255 chars
- Phone: Optional, max 50 chars
- Subject: Required, max 255 chars
- Message: Required, 10-10000 chars

## Admin Menu Structure

```
Enquiries (dashicons-email-alt)
├── All Enquiries (em_enquiries)
├── Detail (em_enquiry_detail) — hidden, accessed via name links
└── Settings (em_settings)
```

## Admin Actions

| Action | Description | Nonce Pattern |
|---|---|---|
| mark_read | Changes status to "read" | em_admin_action_mark_read_{id} |
| archive | Changes status to "archived" | em_admin_action_archive_{id} |
| mark_new | Changes status to "new" (reopen) | em_admin_action_mark_new_{id} |
| delete | Permanently removes enquiry | em_admin_action_delete_{id} |

## Capability Requirements

All admin pages and actions require `manage_options`. This is the standard WordPress capability for plugin administration.

## Admin Nonce Usage

Nonces are created with `wp_create_nonce('em_admin_action_' . $action . '_' . $id)`, passed as `_wpnonce` query parameter, and verified with `wp_verify_nonce()`.

On failure: `wp_die('Security check failed. Please try again.')`.

## Settings API Usage

- **Group**: `em_settings_group`
- **Option**: `em_notification_email`
- **Section**: `em_notification_section`
- **Field**: Rendered by `EM_Settings::render_notification_email_field()`
- **Sanitization**: `EM_Settings::sanitize_notification_email()`

## Notification Email Flow

1. Enquiry is inserted into database
2. `EM_Rest_API::send_notification()` reads `em_notification_email` option
3. Falls back to `get_option('admin_email')` if not configured
4. Composes plain-text email with all enquiry data
5. Sends via `wp_mail()`
6. If `wp_mail()` returns false: logs error to PHP error log (error_log)
7. Enquiry remains saved regardless of email success/failure

## Hooks/Actions/Filters Used

| Hook | Purpose |
|---|---|
| `plugins_loaded` | Initialize plugin |
| `init` | Load textdomain and register shortcode |
| `rest_api_init` | Register REST routes |
| `wp_enqueue_scripts` | Enqueue frontend CSS/JS |
| `admin_menu` | Register admin menu pages |
| `admin_enqueue_scripts` | Enqueue admin CSS/JS |
| `admin_init` | Register settings |

## Coding Conventions

- All PHP identifiers prefixed with `em_` / `EM_`
- All CSS classes prefixed with `em-`
- All JS variables under `EM_Frontend` / `EM_Admin` namespaces
- ABSPATH check at the top of every PHP file
- No closing `?>` tag in PHP-only files (for class files)
- Use WP escaping functions: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Use WP sanitization: `sanitize_text_field()`, `sanitize_email()`, `absint()`, `wp_unslash()`
- Use `$wpdb->prepare()` for all dynamic SQL queries
- Use $wpdb methods: `insert()`, `update()`, `delete()` where possible
- Whitelist dynamic values (status, orderby) before SQL use
- Translatable strings using `__()` and `_e()` with text domain `enquiry-manager`

## Security Conventions

1. **SQL**: Always prepared statements. Never concatenate user input into SQL.
2. **Output**: Always escape. Use appropriate WP escaping functions.
3. **Input**: Always sanitize and validate server-side.
4. **Nonces**: Every state-changing request must include a nonce.
5. **Capabilities**: Check `current_user_can('manage_options')` for admin actions.
6. **IP**: Capture server-side from `$_SERVER['REMOTE_ADDR']` only. Never trust client.
7. **Errors**: Never expose SQL queries, file paths, or stack traces to users.

## How to Run/Lint/Test

### PHP Linting
```bash
php -l enquiry-manager.php
php -l uninstall.php
php -l includes/*.php
```

### Manual Testing Checklist
1. Activate plugin → table created
2. Create page with `[enquiry_form]` → form renders
3. Submit valid form → success message, enquiry in database
4. Submit invalid form → client-side errors shown
5. Disable JS → server-side errors returned
6. Check admin → enquiry visible in list
7. Search by name/email → correct results
8. Filter by status → correct results
9. Change status → updated correctly
10. Delete → removed with confirmation
11. Configure notification email → email sent on new enquiry
12. Deactivate → data preserved
13. Delete plugin → table and options removed
14. Enable WP_DEBUG → no notices/warnings

## Known Limitations

- REST nonce verification relies on WordPress cookie-based authentication. For purely headless/API-only setups, additional authentication may be required.
- Phone validation is hardcoded to Bangladesh 11-digit format (`^01[0-9]{9}$`). No country/region configuration option exists yet.
- No export functionality.
- Email delivery depends on hosting server's mail configuration.
- No CSRF token for REST endpoint beyond WordPress's built-in nonce system.

## Important Assumptions

- Phone validation uses Bangladesh 11-digit mobile format `^01[0-9]{9}$` (e.g. 01712345678). This is a project-specific decision — the original assignment did not specify a format, so this was chosen for a plugin developed and tested in Bangladesh. The validation rule can be changed if target site requirements differ. Phone remains optional — empty values pass validation.
- `manage_options` capability is the appropriate level for plugin administration.
- The plugin is self-contained; no Composer or npm dependencies.
- Vanilla JavaScript is preferred over frameworks for simplicity.
- WordPress 6.0+ and PHP 7.4+ are minimum versions.
