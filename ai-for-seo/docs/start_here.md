# AI for SEO – Start Here Guide

This guide helps GitHub Copilot and similar AI agents to understand the **AI for SEO** project, its scope, context, and coding style. It provides an overview of the plugin structure and points to the correct documentation for details.

---

## Plugin Scope

* Only modify files inside: `wp-content/plugins/ai-for-seo` unless explicitly instructed otherwise.
* Check `wp-content/plugins/ai-for-seo/docs/start_here.md` first (this file)
* Ignore these folders:
    * `wp-content/plugins/ai-for-seo/svn/`
    * `wp-content/plugins/ai-for-seo/dev/`
* Use `wp-content/plugins/ai-for-seo/docs` for **reference only**; do not change docs files directly.
* Only modify files in `robhub-api/v1/` if explicitly instructed.

---

## Files Overview

* **`ai-for-seo.php`** → Main plugin file, core functions, hooks, and logic.
* **`/assets/css/ai-for-seo-styles.css`** → All plugin-specific CSS.
* **`/assets/js/ai-for-seo-scripts.js`** → All plugin-specific JavaScript.
* **`/includes/menu-frame.php`** → Renders admin menu, handles page routing, displays notices.
* **`/includes/ajax/display/`** → Outputs content via AJAX.
* **`/includes/ajax/process/`** → Processes backend AJAX actions.
* **`/includes/ajax/process/save-anything-categories/`** → Handles saving categorized settings/environmental variables.
* **`/includes/api/class-robhub-api-communicator.php`** → Communication with RobHub API (OpenAI-backed, user/license queries).
* **`/includes/modal_schemas/`** → Reusable modal UI components.
* **`/includes/modal_schemas/autoload-modal-schemas.php`** → Injects modals dynamically into site footer.
* **`/includes/pages/account.php`** → Account page (license key, incognito mode, white label).
* **`/includes/pages/settings.php`** → Settings page for plugin options.
* **`/includes/pages/dashboard.php`** → Dashboard page (stats, credits, SEO autopilot, activity, plugin updates).
* **`/includes/pages/help.php`** → Help page (Getting Started, FAQ, Troubleshooting, Contact).
* **`/includes/pages/content_types/`** → Media, Posts, Pages, Products, etc.
* **`/docs/`** → Developer documentation (not user-facing).

---

## Docs Folder

### `code_formation.md`

Code formatting and style conventions for PHP.
**When to check:** Always check first before writing any code.

### `javascript.md`

JavaScript coding standards and best practices.
**When to check:** When adding or modifying JavaScript.

### `stylesheet.md`

CSS standards and best practices.
**When to check:** When adding or modifying CSS.

### `changelog.md`

Rules for adding changelog entries.
**When to check:** Always — changelog entries are required with every implementation.

### `faq.md`

Rules for adding FAQ entries.
**When to check:** For clarifications, problem-solving, feature discovery, specific setups, feature changes/removals.

### `ajax.md`

AJAX function standards and patterns.
**When to check:** When adding AJAX functionality.

### `modals.md`

How to use and implement modals.
**When to check:** When creating or modifying modal dialogs.

### `notices.md`

How plugin notifications and notices work.
**When to check:** When adding or modifying notices/notifications.

### `settings.md`

How to add and validate new plugin settings.
**When to check:** When creating or editing settings in the settings page.

### `environmental_variables.md`

How to manage environmental variables (internal state, metadata).
**When to check:** When adding or modifying environmental variables.

### `robhub_api.md`

How to interact with the RobHub API (User account and AI services).
**When to check:** When adding or modifying API interactions.

---

## Developer Workflow

1. Start with `code_formation.md` to ensure formatting and naming consistency.
2. Identify the type of change:

    * **Setting** → Check `settings.md`
    * **Environmental variable** → Check `environmental_variables.md`
    * **AJAX** → Check `ajax.md`
    * **Modal** → Check `modals.md`
    * **Notice/notification** → Check `notices.md`
    * **JavaScript** → Check `javascript.md`
    * **API** → Check `robhub_api.md`
    * **CSS** → Check `stylesheet.md`
    * **Documentation** → Check `faq.md`, `changelog.md`
3. Always update the **changelog** for every change.
4. Add or update FAQs and troubleshooting if relevant.

---

This file (`start_here.md`) is the entry point for GitHub Copilot and similar AI agents to quickly understand where to look and what to follow when contributing to the AI for SEO plugin.
