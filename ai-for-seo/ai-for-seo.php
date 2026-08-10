<?php
/**
 * Plugin Name: SOOZ - AI for SEO
 * Plugin URI: https://sooz.ai
 * Description: One-Click SEO solution. *SOOZ - AI for SEO* helps your website to rank higher in Web Search results.
 * Version: 2.4.3
 * Author: spacecodes
 * Author URI: https://spa.ce.codes
 * Text Domain: ai-for-seo
 * Domain Path: /languages
 * Copyright 2026 spacecodes
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 7.4
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Store the plugin entry file for hooks and URL/path helpers that require main-file semantics.
const AI4SEO_PLUGIN_FILE     = __FILE__;
const AI4SEO_PLUGIN_DIR_PATH = __DIR__ . '/';

// Load the early prohibit-token handler separately while keeping the final stop decision in this loader.
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/prohibit-plugin.php';

// Stop before normal module loading when the extracted handler validates a one-time prohibition token.
if ( ai4seo_should_prohibit_plugin() ) {
	return;
}

// Declarations provide constants, static registries, and globals required by the core modules.
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/declarations.php';

// Procedural modules load before bootstrap code registers hooks or calls helpers.
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/initialization.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/rights.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/plans.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/helpers.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/semaphores.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/posts.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/taxonomies.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/output.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/third-party-seo-plugins.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/external-plugins.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/multilingual-plugins.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/cron-jobs.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/generation-status-analysis.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/metadata.php';

// Metadata fallback helpers extend metadata and are used by settings and frontend injection callbacks.
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/metadata-fallbacks.php';

// Custom-instruction helpers extend metadata and use the shared render helpers loaded above.
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/custom-instructions.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/attachments-media.php';

// Frontend injection callbacks depend on attachment helpers and load before bootstrap registers their hooks.
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/frontend-injections.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/post-meta.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/wordpress-options.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/settings.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/ajax.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/bulk-generation.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/native-bulk-actions.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/environmental-variables.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/notifications-notices.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/terms-of-service.php';
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/latest-activity.php';

// Account operations load after their plan, storage, notification, and API dependencies.
require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/account.php';

// Register hooks last and return the bootstrap result so its early blockers exit this plugin file.
return require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/core/bootstrap-hooks.php';
