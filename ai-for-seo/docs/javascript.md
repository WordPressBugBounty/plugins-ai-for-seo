# AI for SEO – JavaScript Guide

This guide explains best practices for writing and maintaining JavaScript in the **AI for SEO** plugin. The main file is located at:

```
/assets/js/ai-for-seo-scripts.js
```

---

## Init Process

* Add all initialization logic in the `// === INIT` section of `ai-for-seo-scripts.js`.
* If the logic relates to a specific type of HTML element or module, create a dedicated function inside `ai4seo_init_html_elements()`.

---

## Helper Functions

* `ai4seo_get_input_val(element)` → Get the value of any input (checkbox, radio, text, textarea, select, div, span).
* `ai4seo_get_all_input_values_in_container(container)` → Collect values of all inputs inside a container. Input `name` becomes the array index.
* `ai4seo_reload_page_with_parameter(name, value)` → Reload the page with a query parameter.
* `ai4seo_get_active_subpage()` → Get the current plugin subpage (e.g. `dashboard`, `account`, `media`).
* `ai4seo_get_active_post_type_subpage()` → Get the active post type subpage (e.g. `page`, `post`, `product`).
* `ai4seo_add_loading_html_to_element(element)` → Add a spinner/loading icon to an element (e.g. a button).
* `ai4seo_remove_loading_html_from_element(element)` → Remove the spinner after the operation completes.

---

## Localization Parameters

* Use `ai4seo_get_localization_parameter(name)` to read parameters passed from PHP.
* Define new localization parameters in `ai4seo_set_localization_parameters()` in `ai-for-seo.php`.

---

## Modals

* Refer to `/docs/modals.md` for full guidelines on creating and handling modals.

---

## Saving User Input via AJAX

* Use `ai4seo_save_anything(submit_element, validation_function, success_function, error_function)`.
* Create a dedicated validation function and pass it as the `validation_function`.
* Reference: `ai4seo_validate_account_inputs()`.
* For details, see `/docs/ajax.md`.

---

## Best Practices

1. **Naming**

    * Prefix all functions with `ai4seo_`.

2. **Translations**

    * Use `wp.i18n.__()` for all translatable strings:

      ```js
      wp.i18n.__("Please confirm", "ai-for-seo")
      ```

3. **Code Placement**

    * Avoid inline JavaScript in PHP files.
    * Place functions in `ai-for-seo-scripts.js` at a logical section.

4. **jQuery Usage**

    * Use jQuery elements whenever possible.
    * At the start of a function, ensure a valid jQuery element:

      ```js
      function ai4seo_my_function(element) {
          if (!ai4seo_exists(element)) {
              return false;
          }
          element = ai4seo_jQuery(element);
      }
      ```

---

## Developer Checklist

* [ ] Add init code in the `// === INIT` section or inside `ai4seo_init_html_elements()`.
* [ ] Use helper functions for input handling and spinners.
* [ ] Read localization parameters with `ai4seo_get_localization_parameter()`.
* [ ] Store new parameters in `ai4seo_set_localization_parameters()` (PHP).
* [ ] Follow modal guidelines in `/docs/modals.md`.
* [ ] Save form data with `ai4seo_save_anything()` and a validation function.
* [ ] Prefix functions with `ai4seo_`.
* [ ] Wrap all texts with `wp.i18n.__()` for translations.
* [ ] Avoid inline JavaScript in PHP files.
* [ ] Ensure elements exist and are jQuery wrapped using `ai4seo_exists()` and `ai4seo_jQuery()`.
