# AI for SEO – Stylesheet Guide

This guide explains best practices when dealing with stylesheets in the **AI for SEO** plugin.

---

## General Rules

* Avoid inline CSS in PHP files.
* Create CSS classes in the main stylesheet and reference them in PHP/HTML.
* Main stylesheet: `/assets/css/ai-for-seo-styles.css`.
* Prefix all classes with `ai4seo-` to prevent conflicts with other plugins or themes.

---

## Useful CSS Classes

* **Messages**

    * `ai4seo-success-message` → Success messages
    * `ai4seo-error-message` → Error messages

* **Highlights & Status**

    * `ai4seo-bubble` (base)
    * Variants: `ai4seo-green-bubble`, `ai4seo-blue-bubble`, `ai4seo-yellow-bubble`, `ai4seo-red-bubble`

* **Clipboard**

    * `ai4seo-copy-to-clipboard` → Clickable item that copies to clipboard

* **Buttons**

    * `ai4seo-button` → Base button (always use for consistent styling)
    * `ai4seo-submit-button`, `ai4seo-primary-button` → Primary actions
    * `ai4seo-inactive-button` → Disabled/inactive buttons
    * `ai4seo-abort-button`, `ai4seo-secondary-button` → Transparent background, abort/secondary actions
    * `ai4seo-icon-only-button` → Icon-only buttons
    * `ai4seo-small-button` → Small buttons

* **Layout & Spacing**

    * `ai4seo-clear-both`, `ai4seo-clear` → Clear floats
    * Spacing utilities: `ai4seo-gap`, `ai4seo-gap-zero`, `ai4seo-tiny-gap`, `ai4seo-small-gap`, `ai4seo-medium-gap`, `ai4seo-large-gap`

* **Icons & Loaders**

    * `ai4seo-icon`, `ai4seo-spinning-icon`
    * Color variants: `ai4seo-red-icon`, `ai4seo-green-icon`, `ai4seo-blue-icon`, `ai4seo-yellow-icon`

* **Cards**

    * `ai4seo-card` → Bordered box with padding and shadow

* **Responsive Helpers**

    * `ai4seo-visible-on-mobile`
    * `ai4seo-hidden-on-mobile`

* **Positioning**

    * `ai4seo-top-right-refresh-button-wrapper` → Position refresh button in container top-right

* **Forms**

    * `ai4seo-form`
    * `ai4seo-form-section`
    * `ai4seo-form-item`
    * `ai4seo-form-item-divider`
    * `ai4seo-form-item > label`
    * `ai4seo-form-item-input-wrapper`
    * `ai4seo-form-item-description`

* **Animations**

    * See `/*** ANIMATIONS` section in `/assets/css/ai-for-seo-styles.css` for available animation classes.

---

## Developer Checklist

* [ ] Avoid inline styles; create CSS classes in `/assets/css/ai-for-seo-styles.css`.
* [ ] Prefix all class names with `ai4seo-`.
* [ ] Use existing utility classes (`ai4seo-button`, `ai4seo-gap`, `ai4seo-card`, etc.) whenever possible.
* [ ] Follow naming conventions (context + purpose).
* [ ] Test responsiveness with `ai4seo-visible-on-mobile` and `ai4seo-hidden-on-mobile`.
* [ ] Use animation classes only from the stylesheet’s animation section.
* [ ] Keep new styles grouped by feature/module for maintainability.