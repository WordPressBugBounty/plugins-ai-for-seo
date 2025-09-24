# AI for SEO – AJAX Guide

This guide explains how to add and manage AJAX functions in the **AI for SEO** plugin.

---

## General Rules

* **If the AJAX function saves settings or environmental variables:**

  * Use `ai4seo_save_anything()` via JavaScript instead of creating a new AJAX function which calls `ai4seo_save_anything()` in `ai-for-seo.php`.

* **If saving many (form) data at once that are not settings or environmental variables:**

  * Create a file under `includes/ajax/process/save-anything-categories/` using the form name (e.g., `save-xxxx-editor-values.php`).
  * Declare the import of this file in the `ai4seo_save_anything()` function in `ai-for-seo.php`.
  * Still call the JS function `ai4seo_save_anything()` to save the form/editor.
  * Make sure to create a validation function in JS for all input fields and pass it to `ai4seo_save_anything()`.

* **For smaller operations:**

  * Create a new function following the pattern of `ai4seo_stop_bulk_generation()` (simple) or `ai4seo_generate_metadata()` (complex). You'll find a JavaScript and a PHP function for each with the same name.
  * For more complex operations, create a corresponding file under `includes/ajax/process/` (e.g., `generate-metadata.php`) and include/require it in the function.

* **For displaying content via AJAX:**

  * Use `ai4seo_show_metadata_editor()` as an example.
  * Create a file in `includes/ajax/display/` (e.g., `metadata-editor.php`) and include/require it in the function.

---

## Implementation Rules

* Always end an AJAX call with:

  * `ai4seo_send_json_success()` or `ai4seo_send_json_error()`
  * **Do not** use `wp_send_json_success()` or `wp_send_json_error()`.

* Sanitize and validate parameters in the AJAX function.

* Prevent duplicate calls by adding:

  ```php
  if (!ai4seo_singleton(__FUNCTION__)) {
      return;
  }
  ```

* Always whitelist the AJAX action and function in **two places**:

  1. `ai4seo_allowed_ajax_actions` array in `ai-for-seo-scripts.js`
  2. `AI4SEO_ALLOWED_AJAX_FUNCTIONS` constant in `ai-for-seo.php`

* No manual nonce handling necessary. Ensure the AJAX action is prefixed with `ai4seo`; the nonce will then be generated and checked automatically.

---

## Calling AJAX from JavaScript

Refer to the `ai4seo_import_nextgen_gallery_images()` function in `ai-for-seo-scripts.js`:

```javascript
function ai4seo_import_nextgen_gallery_images(submit_element) {
    ai4seo_add_loading_html_to_element(submit_element);
    ai4seo_lock_and_disable_lockable_input_fields();

    ai4seo_perform_ajax_call('ai4seo_import_nextgen_gallery_images')
        .then(response => {
            ai4seo_reload_page();
        })
        .catch(error => { /* auto error handler enabled */ })
        .finally(() => {
            ai4seo_remove_loading_html_from_element(submit_element);
            ai4seo_unlock_and_enable_lockable_input_fields();
        });
}
```

---

## AJAX Modals

* Load content via AJAX inside a modal.
* Only one AJAX modal can be open at once.
* Close it with `ai4seo_close_ajax_modal()`.

### Function

```javascript
ais4seo_open_ajax_modal(ajax_action, ajax_data = {}, modal_settings = {});
```

> For more details on modals, see `docs/modals.md`.

### Key Practices

* When triggered by a button click:

  * Add a loading spinner into the button label with `ai4seo_add_loading_html_to_element()`.
  * Lock all inputs/buttons with `ai4seo_lock_and_disable_lockable_input_fields()`.
  * Use `ai4seo_perform_ajax_call(action, data = {}, auto_check_response = true, additional_error_list = {}, show_generic_error = true, add_contact_us_link = true)` for the AJAX call.
  * In `.catch()` remove the loading HTML and unlock all input fields.

---

## Developer Checklist

* [ ] Decide if the operation requires `ai4seo_save_anything()` or a new AJAX function.
* [ ] If creating a new function, follow naming patterns (`ai4seo_*`).
* [ ] If complex, create a file in `includes/ajax/process/` or `includes/ajax/display/`.
* [ ] Always end calls with `ai4seo_send_json_success()` / `ai4seo_send_json_error()`.
* [ ] Sanitize and validate all parameters.
* [ ] Add duplicate-call prevention with `ai4seo_singleton(__FUNCTION__)`.
* [ ] Whitelist the function in `ai-for-seo-scripts.js` and `ai-for-seo.php`.
* [ ] Call from JS using `ai4seo_perform_ajax_call()`.
* [ ] Add loading spinner and lock/unlock inputs in the JS handler.
* [ ] Test full request-response cycle (including error handling).
