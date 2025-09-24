# AI for SEO – Modals Guide

This guide explains how to use modals in the **AI for SEO** plugin. Modals are categorized into notification modals, AJAX modals, and schema modals.

---

## Notification Modals

* Used for quick and small messages.
* Always displayed on top of regular modals.
* Smaller in size and cannot be closed by clicking on the backdrop (must be closed with the close button).
* Only one notification modal can be open at once.

### Functions

* Success notification:

  ```javascript
  ai4seo_open_generic_success_notification_modal(content, footer = "", modal_settings = {});
  ```

* Error notification:

  ```javascript
  ai4seo_open_generic_error_notification_modal(error_code = 999, error_message = "", footer = "", modal_settings = {});
  ```

* Generic notification:

  ```javascript
  ai4seo_open_notification_modal(headline = "", content = "", footer = "", modal_settings = {});
  ```

* Close:

  ```javascript
  ai4seo_close_notification_modal();
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

> For more details on AJAX, see `docs/ajax.md`.

---

## Schema Modals

* Used for larger forms and content that should not be loaded via AJAX (performance reasons).
* Require predefined modal schemas.
* Close a specific schema modal with `ai4seo_close_modal_from_schema(modal_schema_identifier)`.

### Function

```javascript
ai4seo_open_modal_from_schema(modal_schema_identifier, modal_settings = {});
```

### Declaring a Schema Modal

1. Create a file under `/includes/modal_schemas/xxxxxx.php` (`xxxxxx` = modal schema identifier).
2. Open `/includes/modal_schemas/autoload-modal-schemas.php` and check section `// === FIND SUITABLE MODAL SCHEMAS`.
3. Add the identifier into the `$ai4seo_modal_schemas` array.
4. Implement the schema modal only on pages where it will be used.

---

## Other Useful Functions

* Close modal by child element:

  ```javascript
  ai4seo_close_modal_by_child(child_element);
  ```

* Close all modals:

  ```javascript
  ai4seo_close_all_modals();
  ```

---

## General Tips

* For larger operations when opening a modal, consider creating a new wrapper function. Example: `ai4seo_open_metadata_editor_modal()`.
* To gather input values inside a modal (or form container), use:

  ```javascript
  ai4seo_get_all_input_values_in_container(form_container);
  ```

  Input `name` attributes become the indexes for the generated array.

---

## Developer Checklist

* [ ] Choose the correct modal type (notification, AJAX, schema).
* [ ] If schema modal: create file in `/includes/modal_schemas/` and register in `autoload-modal-schemas.php`.
* [ ] Ensure only one notification or AJAX modal is active at once.
* [ ] Use provided functions (`ai4seo_open_*`, `ai4seo_close_*`) consistently.
* [ ] Add wrapper functions for complex workflows if needed.
* [ ] Use `ai4seo_get_all_input_values_in_container()` for efficient form data collection.
* [ ] Test modal opening, interactions, and closing across contexts.
