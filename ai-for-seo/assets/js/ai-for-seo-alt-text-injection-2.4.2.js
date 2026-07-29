// The release version is part of this asset's physical filename for CDN cache isolation.

if (typeof jQuery === 'function') {
	// Call above function for each editor element
	jQuery( document ).ready(
		function () {
			setTimeout(
				function () {
					ai4seo_init_alt_text_injection();
				},
				1000
			);
		}
	);

	// =========================================================================================== \\

	/**
	 * Fill missing or empty alt attributes by reusing the first non‑empty alt
	 * found for the same src elsewhere on the page
	 */
	function ai4seo_init_alt_text_injection() {
		const $all_images = jQuery( 'img' );

		// Stop early when the page has no images for the reuse scan.
		if ( ! $all_images.length) {
			return;
		}

		// Inspect each missing-alt image so repeated src values can reuse an existing non-empty alt.
		$all_images.each(
			function () {
				const $this_image    = jQuery( this );
				const this_src_value = $this_image.attr( 'src' );
				const this_alt_value = $this_image.attr( 'alt' );

				// Only reuse alt text when this image has a source but no usable alt text.
				if (this_src_value && ( typeof this_alt_value === 'undefined' || this_alt_value === '' )) {
					// Compare attributes in JavaScript because image URLs can contain CSS selector metacharacters.
					const $all_similar_images_with_alt = $all_images.filter(
						function () {
							const $candidate_image    = jQuery( this );
							const candidate_alt_value = $candidate_image.attr( 'alt' );

							return $candidate_image.attr( 'src' ) === this_src_value
								&& typeof candidate_alt_value !== 'undefined'
								&& candidate_alt_value !== '';
						}
					);

					// Leave this image untouched when no matching non-empty alt text exists elsewhere on the page.
					if ( ! $all_similar_images_with_alt.length) {
						return;
					}

					$this_image.attr( 'alt', $all_similar_images_with_alt.first().attr( 'alt' ) );
				}
			}
		);
	}
}
