# AI_USAGE.md

Documentation of AI tool usage in the development of Enquiry Manager.

## AI Tools Used

- **OpenCode** (powered by DeepSeek v4 Pro): Used as the primary AI coding assistant for architecture planning, code generation, documentation, and security review.

## Parts of the Project AI Helped With

1. **Architecture Design**: The modular class-based architecture (EM_Plugin singleton, separate classes per concern) was designed collaboratively. The file structure and separation of concerns were AI-assisted.

2. **Database Schema**: The `{$wpdb->prefix}enquiries` table schema with indexes and the `dbDelta()` usage pattern.

3. **REST API Implementation**: The `EM_Rest_API` class including route registration, validation callbacks, server-side IP capture, and notification integration.

4. **Frontend JavaScript**: The form validation script (public/js/frontend.js) including inline error display, loading state management, and Fetch API submission with nonce headers.

5. **Admin Dashboard**: The admin listing page with search, status filtering, pagination, action buttons, and the detail view.

6. **Settings Page**: The Settings API integration for the notification email field.

7. **Security Hardening**: Sanitization, validation, nonce verification, capability checks, prepared statements, and output escaping.

8. **Documentation**: README.md, CLAUDE.md, and this file.

## One Effective Prompt or Approach

**Prompt**: Requesting the entire plugin specification as a single comprehensive document, then asking for an implementation plan before any code was written. This forced a structured approach where:
- Environment inspection came first
- Architecture was planned before coding
- Each phase was implemented incrementally
- Verification followed each phase

The "implementation plan first" approach was the most effective pattern. It prevented the common AI trap of diving into code generation without enough context or structure.

## Example Where AI Generated Something Suboptimal

**Issue 1 — dbDelta PRIMARY KEY spacing**: The `PRIMARY KEY (`id`)` line in the CREATE TABLE SQL had only one space between `PRIMARY KEY` and the opening parenthesis. WordPress's `dbDelta()` function requires **two spaces** for proper parsing and table comparison. With single spacing, dbDelta may fail to match the primary key definition during schema diff, potentially causing it to incorrectly attempt ALTER TABLE operations.

**How It Was Detected**: During the detailed security/specification audit by re-reading the WordPress dbDelta documentation and comparing against the generated SQL.

**How It Was Corrected**: Changed `PRIMARY KEY (`id`)` to `PRIMARY KEY  (`id`)` (two spaces).

**Issue 2 — REST nonce not verified server-side**: The `X-WP-Nonce` header was sent from the frontend JavaScript, but the REST API endpoint callback never verified it. The `permission_callback` was `__return_true` and the handler did no nonce check. WordPress core's automatic nonce check only applies to authenticated (logged-in) users, not anonymous visitors — the primary users of the enquiry form.

**How It Was Detected**: Security audit of the REST API handler logic. Traced the flow: frontend sends nonce → `register_rest_route` → `permission_callback => __return_true` (bypasses) → `handle_submission` (no nonce check).

**How It Was Corrected**: Added explicit nonce verification at the top of `handle_submission()`: read `X-WP-Nonce` header, call `wp_verify_nonce( $nonce, 'wp_rest' )`, return 403 on failure.

**Issue 3 — `settings_errors()` missing**: The settings page form did not call `settings_errors()`, meaning WordPress save confirmation notices and validation error messages from `add_settings_error()` were never displayed to the admin.

**How It Was Detected**: Reviewing the settings page template against WordPress Settings API best practices documentation.

**How It Was Corrected**: Added `<?php settings_errors(); ?>` call before the `<form>` tag in `render_settings_page()`.

**Issue 4 — `register_activation_hook()` in `plugins_loaded`**: The activation hook was registered inside the `plugins_loaded` hook rather than directly in the main plugin file. This violates WordPress convention and could theoretically miss the activation event in edge cases.

**How It Was Detected**: Comparing implementation against WordPress Plugin Handbook examples and the standard practice of placing `register_activation_hook()` in the main plugin file.

**How It Was Corrected**: Moved the hook to `enquiry-manager.php`, calling `register_activation_hook( __FILE__, array( 'EM_Database', 'activate_on_hook' ) )` directly. Added a static method `activate_on_hook()` to `EM_Database` for the callback.

**Issue 5 — `load_plugin_textdomain()` missing**: The plugin used `__()` and `_e()` throughout but never called `load_plugin_textdomain()`, meaning translations would not load.

**How It Was Detected**: Reviewing all initialization hooks against i18n requirements.

**How It Was Corrected**: Added `load_textdomain()` method to `EM_Plugin`, hooked to `init`.

**Issue 6 — PII in error_log**: The database insert failure log included the user's email address, which is PII.

**How It Was Detected**: Privacy review of all logging statements.

**How It Was Corrected**: Removed the email from the log message. Changed to `'[Enquiry Manager] Database insert failed.'`.

## What Human Review Was Performed

1. **PHP Linting**: All PHP files were linted with `php -l`. Zero syntax errors.
2. **Security Audit**: Manual review of every file for:
   - SQL injection vulnerabilities (verified all queries use `$wpdb->prepare()` or safe `$wpdb` methods)
   - XSS vectors (verified all output uses `esc_html()`, `esc_attr()`, `esc_url()`)
   - CSRF protection (verified nonces on all admin actions)
   - Missing capability checks (verified `current_user_can('manage_options')` on all admin endpoints)
   - Unsafe IP handling (verified server-side only via `$_SERVER['REMOTE_ADDR']`)
   - Input sanitization (verified sanitization before all database operations)
3. **Code Architecture Review**: Verified separation of concerns, consistent prefixing, proper hook usage, and WordPress coding standards compliance.
4. **Static Analysis**: Reviewed variable initialization, null safety, array key existence checks, and error handling paths.
