<?php
/**
 * Manual license change confirmation dialog.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_administer_plugin() ) {
	return;
}
?>
<div class="ai4seo-modal-schema-headline"><?php esc_html_e( 'Change license credentials', 'ai-for-seo' ); ?></div>
<div class="ai4seo-modal-schema-content">
	<div class="ai4seo-license-change-details">
		<div class="ai4seo-license-change-name">
			<label for="ai4seo-new-license-owner"><?php esc_html_e( 'New license owner', 'ai-for-seo' ); ?></label>
			<input id="ai4seo-new-license-owner" class="ai4seo-textfield ai4seo-lockable" type="text" minlength="5" maxlength="48" autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="ai4seo-license-owner-rules" />
			<p id="ai4seo-license-owner-rules" class="ai4seo-form-item-description"><?php esc_html_e( '5–48 lowercase letters, numbers, hyphens or underscores. Include a letter; start and end with a letter or number. No consecutive special characters or reserved system names.', 'ai-for-seo' ); ?></p>
		</div>
		<p><label><input type="checkbox" class="ai4seo-license-change-acknowledge ai4seo-lockable" /> <span class="ai4seo-license-change-acknowledgement-text"></span></label></p>
	</div>
	<div class="ai4seo-license-change-confirmation ai4seo-display-none">
		<p id="ai4seo-license-change-email" class="ai4seo-license-change-email" role="status" aria-live="polite"></p>
		<div class="ai4seo-license-change-code-entry">
			<p><label for="ai4seo-license-change-code"><?php esc_html_e( 'Confirmation code', 'ai-for-seo' ); ?></label></p>
			<p><input id="ai4seo-license-change-code" class="ai4seo-textfield ai4seo-lockable" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="8" pattern="[0-9]{8}" aria-describedby="ai4seo-license-change-email" /></p>
		</div>
	</div>
</div>
<div class="ai4seo-modal-schema-footer">
	<span class="ai4seo-license-change-confirm-actions ai4seo-unsaved-changes-warnings ai4seo-display-none">
		<button type="button" class="ai4seo-button ai4seo-submit-button ai4seo-primary-button ai4seo-inactive-button ai4seo-start-inactive ai4seo-lockable ai4seo-license-change-confirm" disabled><?php esc_html_e( 'Confirm change', 'ai-for-seo' ); ?></button>
	</span>
	<button type="button" class="ai4seo-button ai4seo-primary-button ai4seo-lockable ai4seo-license-change-request"><?php esc_html_e( 'Send confirmation code', 'ai-for-seo' ); ?></button>
	<button type="button" class="ai4seo-button ai4seo-secondary-button ai4seo-lockable ai4seo-license-change-status ai4seo-display-none"><?php esc_html_e( 'Check again', 'ai-for-seo' ); ?></button>
</div>
