# AI for SEO – Changelog Guide

This guide explains how and when to add changelog entries for the **AI for SEO** plugin. Changelogs inform users about updates, new features, fixes, and improvements.

---

## Where to Add Changelog Entries

1. **`readme.txt`**

    * Section: `== Changelog ==`
    * Add a new section for the current version (check `Stable tag`).

2. **`changelog.txt`**

    * Same format and rules as `readme.txt`.

3. **`ai-for-seo.php`**

    * Function: `ai4seo_get_change_log()`
    * Add a new array entry for a new version if needed.
    * Use a date **seven days in the future** (developers may adjust).
    * Set `important => false`, unless a developer already has set it to "true".
    * Add updates inside the `updates` array.

---

## When to Add a Changelog Entry

1. **New Feature or Setting**
   Example: "Added a setting for render-level alt text injection."

2. **Significant Changes in Behavior**
   Example: "Changed how plugin notifications are displayed in the admin menu."

3. **Significant Improvements**
   Example: "Improved context awareness for pages, posts, and products."

4. **Minor Changes** (Bug fixes, performance, or security updates)
   Combine into one line starting with **"Bug Fixes & Maintenance:"**

    * Example: "Bug Fixes & Maintenance: Fixed 9 minor bugs, implemented 6 performance optimizations, and 1 security update."

---

## Formatting Rules

* Start a new section for each version under its version header:

  ```
  = 2.1.2 =
  * Added new XYZ feature.
  * Bug Fixes & Maintenance: Fixed 3 bugs, 2 security updates.
  ```

* In `ai-for-seo.php`, add entries like:

  ```php
  [
      'date'     => '2025-09-26', // 7 days in future
      'version'  => '2.1.2',
      'important'=> false,
      'updates'  => [
          'Added new XYZ feature.',
          'Bug Fixes & Maintenance: Fixed 3 bugs, 2 security updates.',
      ],
  ],
  ```

## Developer Checklist

* [ ] Check `Stable tag` in `readme.txt` for the current version
* [ ] Create or update version header in `readme.txt` under `== Changelog ==`
* [ ] Add matching entry in `changelog.txt`
* [ ] Add matching entry in `ai-for-seo.php > ai4seo_get_change_log()`
* [ ] Use a **future date** for new versions
* [ ] Keep `important => false` unless instructed otherwise
* [ ] Use grouped line for minor bug fixes and optimizations
* [ ] Review related FAQ entries and update/remove if the feature changed or was removed