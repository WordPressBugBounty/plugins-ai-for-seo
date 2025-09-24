# AI for SEO – Notices Guide

This guide explains how **notices** (plugin notifications) work inside the **AI for SEO** plugin and how to create or manage them.

---

## General Rules

* Notices appear only from defined **Notifications** inside the AI for SEO dashboard.
* Notices from other plugins are hidden and never appear inside AI for SEO.
* All notifications are stored in the database in the WordPress option `AI4SEO_NOTIFICATIONS_OPTION_NAME`.
* Always use provided helper functions (see below) to manage notifications.
* Notifications auto-dismiss after `AI4SEO_NOTIFICATION_AUTO_DISMISS_DAYS` days.
* The number of unread notifications is shown on the AI for SEO menu item in the WordPress sidebar.
* Main logic: see `// === NOTIFICATIONS` section in `ai-for-seo.php`.
* Only add notifications if explicitly advised by the AI for SEO team.
* Notification types: **info**, **success**, **warning**, **error**.
* Notifications can be **permanent** (e.g., critical issues) or **temporary** (e.g., feature announcements).
* Notifications can have **buttons** (e.g., "Learn more", "Rate us", "Dismiss").
* Notifications can have **conditions** (e.g., only show if a setting is enabled, or if another plugin is active).
* Notifications can be dismissed by the user. Dismissed notifications will not show again unless re-enabled by `ai4seo_remove_notification()` or by pushing the notification again using \$force = true.

---

## Check Functions

Defined in `// === NOTIFICATION CHECKS` section of `ai-for-seo.php`.

* Add a new check function inside `ai4seo_check_for_new_notifications()` if it should run on every page load.
* Check functions can also be called from anywhere in the plugin when a notification should be triggered.
* Example: `ai4seo_check_for_rate_us_notification($force = false)`.

---

## Push Function

Use inside a check function to add a notification:

```php
/**
 * Push a new unread notification.
 *
 * @param string $notification_index Identifier
 * @param string $message Notification message
 * @param bool   $force Force replace existing notification
 * @param array  $additional_fields Extra fields (notice_type, is_permanent, etc.)
 *
 * @return bool True if added, false otherwise
 */
function ai4seo_push_notification( string $notification_index, string $message, bool $force = false, array $additional_fields = array() ): bool {
    // Implementation…
}
```

* See `ai4seo_get_notification_buttons()` for possible buttons (add via `$additional_fields`).
* See `ai4seo_check_notification_conditions()` for possible conditions (add via `$additional_fields`).

---

## Remove Function

* Use `ai4seo_remove_notification($notification_index)` if a notification is no longer valid.
* This removes the notification and its history from the database.
* Do **not** use for dismissing by user — dismiss is handled automatically with `ai4seo_mark_notification_as_dismissed()`.

---

## Developer Checklist

- [ ] Advised to create a notification
- [ ] Unique index defined
- [ ] Type set (info/success/warning/error)
- [ ] Temporary vs permanent decided
- [ ] Conditions/buttons added if needed
- [ ] Implement check function or hook into logic
- [ ] Use `ai4seo_push_notification()` (with fields)
- [ ] Use `ai4seo_remove_notification()` if invalid 