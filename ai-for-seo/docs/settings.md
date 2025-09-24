# AI for SEO – Settings Guide

This guide explains how to add new plugin settings so that users can configure them in the **AI for SEO** settings page.

---

## Steps to Create a New Setting

### 1. Define a Constant

* Location: `ai-for-seo.php` ("// === PLUGIN'S SETTINGS" section, search for "// === PLUGIN'S SETTINGS")
* Example:

  ```php
  const AI4SEO_SETTING_BULK_GENERATION_DURATION = 'bulk_generation_duration';
  ```

### 2. Add the Constant to the Settings Array

* Location: `ai-for-seo.php` ("// === PLUGIN'S SETTINGS" section)
* Add the new constant to the corresponding `AI4SEO_ALL_*_SETTINGS` array.&#x20;

    * Example: A setting used on the settings page, add it to AI4SEO\_ALL\_SETTING\_PAGE\_SETTINGS

### 3. Define a Default Value

* Location: `ai-for-seo.php` ("// === PLUGIN'S SETTINGS" section)
* Update `$ai4seo_default_settings` with a sensible default for the new setting.

### 4. Add Validation Logic

* Location: `ai-for-seo.php`
* Extend `ai4seo_validate_setting_value()` with a new case for the constant.
* Example:

  ```php
  case AI4SEO_SETTING_BULK_GENERATION_DURATION:
      $setting_value = (int) $setting_value;
      return $setting_value >= 10 && $setting_value <= 300;
  ```
* Consider adding a Constant in the header section of ai-for-seo.php when dealing with  specifically allowed values, like used in select inputs

### 5. Add the Setting to the Settings Page

* Location: `includes/pages/settings.php`
* Place the form element inside the correct section (Metadata, Media Attributes, User Management, Troubleshooting, or as specified).
* Check other settings for common practices in naming, describing and formatting
* Follow UI conventions:

    * **Checkbox** → Boolean values
    * **Select input** → Multiple choice
    * **Text field** → Free text input
    * **Select-all checkbox** → For groups of checkboxes (use `ai4seo_get_select_all_checkbox()`)
* For advanced-only options, add CSS class `.ai4seo-is-advanced-setting` to the `.ai4seo-form-item` element.

### 6. Handle Post-Save Behavior (Optional)

* Normal saving is handled by `save_everything()`.
* If special actions are needed after a setting changes, add logic to:
  `includes/ajax/process/save-anything-categories/save-settings.php`
* Example:

  ```php
  if (isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES])) {
      ai4seo_refresh_all_posts_seo_coverage();
  }
  ```

### 7. Read/Write Environmental Variables

* **Read:**

  ```php
  $active_bulk_generation_post_types = ai4seo_get_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES);
  ```
* **Write:**

  ```php
  ai4seo_update_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, $new_enabled_bulk_generation_post_types);
  ```


### 8. Update Documentation

* Add a **Changelog** entry (`changelog.md`).
* Add at least one **FAQ entry** (`faq.md`) describing a common use case or problem solved by this setting.
* If relevant, update **help.php** → Troubleshooting section.

## Developer Checklist

* [ ] Create a constant in `ai-for-seo.php` under `// === Plugin's Settings`
* [ ] Add the constant to the correct `AI4SEO_ALL_*_SETTINGS` array
* [ ] Define a default value in `$ai4seo_default_settings`
* [ ] Add validation logic in `ai4seo_validate_setting_value()`
* [ ] If needed, define a constant for allowed values (e.g., for select inputs)
* [ ] Add the form element in `includes/pages/settings.php` in the right section
* [ ] Apply `.ai4seo-is-advanced-setting` class if advanced-only
* [ ] Add post-save handling in `save-settings.php` if special logic is required
* [ ] Test reading/writing with `ai4seo_get_setting()` and `ai4seo_update_setting()`
* [ ] Add a changelog entry in `changelog.md`
* [ ] Add at least one FAQ entry in `faq.md`
* [ ] Update troubleshooting section in `help.php` if relevant