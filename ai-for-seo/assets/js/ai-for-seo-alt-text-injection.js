if (typeof jQuery === 'function') {
    // Call above function for each editor element
    jQuery(document).ready(function(){
        setTimeout(function() {
            ai4seo_init_alt_text_injection();
        }, 1000);
    });

    // =========================================================================================== \\

    /**
     * Fill missing or empty alt attributes by reusing the first non‑empty alt
     * found for the same src elsewhere on the page
     */
    function ai4seo_init_alt_text_injection() {
        let all_images = jQuery('img');

        // if there are no images at all, bail out
        if (!all_images.length) {
            return;
        }

        // for each img without an alt or with an empty alt
        all_images.each(function() {
            let this_image      = jQuery(this);
            let this_src_value  = this_image.attr('src');
            let this_alt_value  = this_image.attr('alt');

            // only proceed if src is set and alt is missing or empty
            if (this_src_value && ( typeof this_alt_value === 'undefined' || this_alt_value === '' )) {
                let all_similar_images_with_alt = jQuery('img[src="' + this_src_value + '"][alt!=""]');

                // if there are no images with the same src and a non‑empty alt, skip this image
                if (!all_similar_images_with_alt.length) {
                    return;
                }

                this_image.attr('alt', all_similar_images_with_alt.first().attr('alt'));
            }
        });
    }
}