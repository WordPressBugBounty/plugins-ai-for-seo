# AI for SEO – FAQ Guide

This guide explains how and when to add FAQ entries for the **AI for SEO** plugin. FAQs help users understand new features, solve common problems, and quickly find answers.

---

## When to Add an FAQ Entry

1. **Clarification of New Implementations**
   Add an FAQ when a new feature or setting may confuse users or needs explanation.

2. **Problem-Solving Scenarios**
   Add an FAQ when the new feature helps fix a common issue or prevents unwanted behavior.

3. **Feature Discovery**
   Add an FAQ when users might not easily find where or how to use the feature.

4. **Specific User Setups**
   Add an FAQ when a feature addresses issues in certain environments (e.g., WPML, themes skipping alt text output).

5. **Feature Removal or Change**
    * If a feature, function, or setting is **removed**, check for outdated FAQ entries and remove them.
    * If a feature, function, or setting is **changed**, update existing FAQ entries accordingly.

---

## How to Write an FAQ Entry

* Formulate the **question** as the user would ask it (problem-driven, not solution-driven).
* Make it **keyword-friendly** for search.
* Provide a clear, concise **answer** that points directly to the solution.

---

## Where to Add FAQ Entries

1. **`readme.txt`**

    * Section: `== Frequently Asked Questions ==`
    * Insert the entry in a logical position based on related features.

2. **`help.php`**

    * Section: `// === FAQ`
    * Choose the correct sub-section: `GENERAL`, `AUTOMATION`, `NAVIGATION`, `PLANS / SUBSCRIPTIONS`.
    * Only create a new section if explicitly advised.
    * Add the entry in a logical position based on related features.
    * Always add the exact same FAQ as in `readme.txt`.
    * Follow the structure and functions already in use.

3. **Troubleshooting (if applicable)**

    * Section: `// === TROUBLESHOOTING F.A.Q` in `help.php`
    * If the feature solves a common error, add a troubleshooting entry.
    * This may duplicate the FAQ entry since users often search troubleshooting first.

---

## Formatting Rules

* In `readme.txt`:

  ```
  = Why is my alt text missing from images? =
  Some themes or plugins do not correctly output stored alt text. Enable the setting "Render-Level Alt Text Injection" in the plugin settings to ensure alt text is injected directly.
  ```

* In `help.php` FAQ:

  ```php
  $ai4seo_this_accordion_content = __("If you notice that alt text is missing on images, it may be due to your theme or other plugins not properly outputting the alt text stored in the database. In such cases, you can enable the 'Render-Level Alt Text Injection' setting in the plugin settings. This feature injects alt text directly at the render level, ensuring that images display the correct alt text and improving accessibility and SEO compliance.", "ai-for-seo");
  echo ai4seo_wp_kses(ai4seo_get_accordion_element("> " . esc_html__("What can I do if alt text is missing on images?", "ai-for-seo"), $ai4seo_this_accordion_content));
  ```

* In `help.php` troubleshooting:

  ```php
  $ai4seo_this_accordion_content = __("If you want to revert the plugin settings to their default state: Use the Reset Settings option under Help > Troubleshooting > Reset Plugin. This will restore all settings to their original values but will not delete generated metadata or media attributes.", "ai-for-seo");
  echo ai4seo_wp_kses(ai4seo_get_accordion_element("> " . esc_html__("How can I reset the plugin settings to their default values?", "ai-for-seo"), $ai4seo_this_accordion_content));
  ```

---

## Developer Checklist

* [ ] Determine if the new feature/change needs clarification.
* [ ] Phrase the question as a **user problem**.
* [ ] Add entry to `readme.txt` under FAQs.
* [ ] Add entry to `help.php` under the correct FAQ section.
* [ ] Add a troubleshooting entry in `help.php` if relevant.