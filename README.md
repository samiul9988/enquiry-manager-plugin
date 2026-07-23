# Enquiry Manager

A self-contained WordPress plugin for managing visitor enquiries submitted via a frontend form.

## Overview

Enquiry Manager allows website visitors to submit enquiries from any WordPress page using a simple shortcode. Administrators can view, search, filter, manage, and delete enquiries from the WordPress admin dashboard. Submissions use the WordPress REST API (no page reloads), and email notifications keep admins informed of new enquiries.

## Features

- Frontend enquiry form via `[enquiry_form]` shortcode
- AJAX submission through the WordPress REST API (no page reload)
- Client-side and server-side validation
- Server-side IP address capture (IPv4/IPv6)
- Admin dashboard with enquiry listing, pagination, search, and status filtering
- Enquiry detail view with full information
- Status management: New, Read, Archived
- Secure delete actions with confirmation
- Configurable email notifications for new enquiries
- Clean uninstall (removes all plugin data)

## Quick Start

1. Activate the plugin.
2. Create a new WordPress page (or edit an existing one).
3. Add the shortcode `[enquiry_form]` to the page content.
4. Publish the page.

The enquiry form will appear on that page with fields for Name, Email, Phone, Subject, and Message.

> **Tip:** Visit **Enquiries → Settings** in the WordPress admin to see the shortcode and copy it with one click.

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher (tested with PHP 8.3)
- MySQL 5.7+ or MariaDB 10.3+

## Installation

### Via WordPress Admin (ZIP Upload)

1. Download the plugin ZIP file.
2. Go to **Plugins > Add New > Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

### Via wp-content/plugins

1. Extract the plugin folder into `wp-content/plugins/`.
2. Ensure the folder is named `enquiry-manager`.
3. Go to **Plugins** and activate **Enquiry Manager**.

## Database Table

On activation, the plugin creates a custom table: `{prefix}enquiries`

This table uses your WordPress table prefix dynamically. No manual database setup is required. On uninstall, the table is removed.

## Usage

### Frontend: Add the Enquiry Form

Add the `[enquiry_form]` shortcode to any page or post:

1. Go to **Pages → Add New** (or edit an existing page).
2. Add the shortcode `[enquiry_form]` in the content editor.
3. Publish or update the page.
4. Visit the page to see the enquiry form.

You can also find the shortcode on the **Enquiries → Settings** page.

### Frontend Validation

The form validates:
- **Name**: Required, max 255 characters
- **Email**: Required, valid format, max 255 characters
- **Phone**: Optional, max 50 characters
- **Subject**: Required, max 255 characters
- **Message**: Required, 10–10000 characters

Errors appear inline near each field. The form cannot be submitted twice accidentally (duplicate submission prevention while in progress).

After successful submission, the form resets and a success message appears.

### Admin: View Enquiries

1. Go to **Enquiries** in the WordPress admin sidebar.
2. All enquiries are listed in a table with: ID, Name, Email, Phone, Subject, Status, Date, Actions.

### Admin: Search and Filter

- **Search**: Enter text in the search box to filter by name or email.
- **Status Filter**: Use the dropdown to filter by status (All, New, Read, Archived).
- Search and filter work together. Click **Clear** to reset filters.

### Admin: Pagination

Enquiries are paginated at 10 per page. Use the pagination controls at the bottom of the table.

### Admin: View Enquiry Details

Click an enquiry name (name column link) to view the full detail, including:
- All fields
- Full message content
- Submitted IP address
- Created date

### Admin: Change Status

Use the action buttons to change an enquiry's status:

| Current Status | Available Actions |
|---|---|
| New | Mark Read, Delete |
| Read | Archive, Delete |
| Archived | Reopen, Delete |

Actions are also available on the detail view page.

### Admin: Delete

Click **Delete** next to any enquiry. A confirmation dialog appears. Deleting permanently removes the enquiry from the database.

## Settings

### Notification Email

1. Go to **Enquiries > Settings**.
2. Enter the email address that should receive new enquiry notifications.
3. Click **Save Changes**.

**Default**: The site's admin email address (`Settings > General > Admin Email`).

When a new enquiry is submitted, an email notification is sent with:
- Name, Email, Phone, Subject, Message
- Submission date
- A link to the admin enquiries page

### Email Delivery Notes

WordPress uses `wp_mail()` for email delivery. Depending on your hosting environment, you may need to configure SMTP for reliable email delivery. Recommended SMTP plugins include WP Mail SMTP or Post SMTP. If email sending fails, the enquiry is still saved successfully.

## Shortcode Reference

| Shortcode | Description |
|---|---|
| `[enquiry_form]` | Renders the frontend enquiry submission form |

## REST API

| Method | Endpoint | Description |
|---|---|---|
| POST | `/wp-json/enquiry-manager/v1/enquiries` | Submit a new enquiry |

The REST endpoint requires a valid WordPress REST nonce, sent via the `X-WP-Nonce` header.

## Uninstall

When the plugin is deleted (not just deactivated):
- The `{prefix}enquiries` database table is dropped.
- All plugin options (`em_db_version`, `em_notification_email`) are removed.

Deactivating the plugin does NOT remove any data.

## Troubleshooting

### Form submission returns an error
- Check that the REST API is accessible: visit `/wp-json/` on your site.
- Ensure the nonce is valid (the form should handle this automatically).
- Check the PHP error log for any issues.

### Email notifications not arriving
- Verify the notification email address in **Enquiries > Settings**.
- WordPress email delivery depends on server configuration. Install an SMTP plugin if needed.
- Check if emails are going to spam.

### WP_DEBUG Compatibility
The plugin is designed to work without PHP notices, warnings, or errors when `WP_DEBUG` is enabled.

## Security

- All form input is sanitized and validated on both client and server.
- All SQL queries use WordPress prepared statements (`$wpdb->prepare()`).
- All output is escaped using WordPress escaping functions.
- Admin actions require the `manage_options` capability.
- Nonces protect all state-changing requests.
- IP addresses are captured server-side only (never trusted from client).
- No sensitive data is exposed to unauthorized users.

## File Structure

```
enquiry-manager/
├── enquiry-manager.php        # Plugin bootstrap file
├── uninstall.php              # Uninstall handler
├── README.md                  # This file
├── CLAUDE.md                  # Developer/AI agent documentation
├── AI_USAGE.md                # AI usage documentation
├── includes/
│   ├── class-em-plugin.php    # Main plugin bootstrap class
│   ├── class-em-database.php  # Database operations and schema
│   ├── class-em-rest-api.php  # REST API endpoint handler
│   ├── class-em-admin.php     # Admin dashboard and actions
│   ├── class-em-settings.php  # Settings page handler
│   └── class-em-frontend.php  # Frontend shortcode and assets
├── admin/
│   ├── css/admin.css          # Admin UI styles
│   └── js/admin.js            # Admin JS (delete confirmation)
└── public/
    ├── css/frontend.css       # Frontend form styles
    └── js/frontend.js         # Frontend form validation and AJAX
```

## Development / Testing

### PHP Lint
```bash
php -l enquiry-manager.php
php -l uninstall.php
php -l includes/*.php
```

### Testing in a WordPress Environment
1. Activate the plugin on a WordPress test site.
2. Verify the `{prefix}enquiries` table is created.
3. Create a page with `[enquiry_form]`.
4. Submit a test enquiry.
5. Check the admin Enquiries page.
6. Test search, filters, status changes, and delete.
7. Test with `WP_DEBUG` enabled.
8. Uninstall and verify the table is removed.

## Implementation Decisions / Assumptions

- **Phone field is optional**. Not all users want to require it. This is a deliberate design choice.
- **Phone validation is Bangladesh-specific**. When a phone number is provided, it is validated as an 11-digit Bangladesh mobile number in the `01XXXXXXXXX` format (e.g. `01712345678`). The original assignment did not specify a country-specific phone format, so this was chosen as a reasonable project-specific validation rule for a plugin developed and tested in Bangladesh. This rule can be changed if the target site's requirements differ.
- **Admin capability** `manage_options` is used for access control (standard for plugin admin pages).
- **No JavaScript framework** — vanilla JS only to keep the plugin lightweight and dependency-free.
- **No Composer or external PHP dependencies** — the plugin is fully self-contained.

## Potential Future Improvements / Suggestions

1. **Configurable phone validation**: The Bangladesh-specific 11-digit validation could be replaced with a configurable strategy that supports different countries or regions. A settings field could allow admins to choose their target country's phone format.
2. **International phone format support**: The plugin could support normalized international formats such as E.164 (e.g. `+8801712345678`), either alongside or instead of local formats.
3. **Configurable phone validation strategy**: Instead of hard-coding a single country's rules, the plugin could offer a dropdown of predefined country formats or a custom regex field in the settings.
4. **Spam protection**: A honeypot field or reCAPTCHA integration could be added to reduce bot submissions.
5. **Export functionality**: Admins could export enquiries to CSV for reporting.
