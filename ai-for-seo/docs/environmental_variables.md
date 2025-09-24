# AI for SEO – Environmental Variables Guide

This guide explains how to add new **environmental variables**. These store internal plugin state or metadata that users normally cannot or should not configure manually.

---

## Steps to Create a New Environmental Variable

### 1. Define a Constant

* Location: `ai-for-seo.php` (section `// === ENVIRONMENTAL VARIABLES`)
* Example:

  ```php
  const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION = 'last_known_plugin_version';
  ```

### 2. Define a Default Value

* Location: `ai-for-seo.php`
* Add the default value in `AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES`.

### 3. Add Validation Logic

* Location: `ai-for-seo.php`
* Extend `ai4seo_validate_environmental_variable_value()` with a case for the constant.
* Example:

  ```php
  case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION:
      // contains only of numbers and dots
      return is_string($environmental_variable_value) && preg_match("/^[0-9.]+$/", $environmental_variable_value);
  ```

### 4. Handle Post-Save Behaviour (Optional)

* Normal saving is handled by `save_everything()`.
* If special actions are required after an environmental variable changes, add logic to:
  `includes/ajax/process/save-anything-categories/save-environmental-variables.php`

### 5. Read/Write Environmental Variables

* **Read:**

  ```php
  $version = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION );
  ```
* **Write:**

  ```php
  ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION, '2.1.1' );
  ```

### 6. Documentation Impact

* Normally, no FAQ or changelog entry is required.
* Only document in `faq.md` or `changelog.md` if explicitly instructed.


## Developer Checklist

* [ ] Create a constant in `ai-for-seo.php` under `// === ENVIRONMENTAL VARIABLES`
* [ ] Define a default value in `AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES`
* [ ] Add validation logic in `ai4seo_validate_environmental_variable_value()`
* [ ] Add post-save handling in `save-environmental-variables.php` if special logic is required
* [ ] Test reading with `ai4seo_read_environmental_variable()`
* [ ] Test writing with `ai4seo_update_environmental_variable()`
* [ ] Skip UI changes (environmental variables are not user-configurable)
* [ ] No FAQ or changelog update unless explicitly instructed