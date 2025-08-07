<?php
defined('ABSPATH') || exit;

function wpfp_settings_page_html() {

    if ( isset( $_POST['wpfp_settings_submit'] ) && check_admin_referer( 'wpfp_save_settings' ) ) {
        update_option('wpfp_purchases', isset($_POST['wpfp_purchases']) ? 'yes' : 'no');
        update_option('wpfp_wish_lists', isset($_POST['wpfp_wish_lists']) ? 'yes' : 'no');
        update_option('wpfp_reviews', isset($_POST['wpfp_reviews']) ? 'yes' : 'no');

        echo '<div class="updated"><p>Saved</p></div>';
    }

    $optionPurchases = get_option('wpfp_purchases', 'yes');
    $optionWishLists = get_option('wpfp_wish_lists', 'yes');
    $optionReviews = get_option('wpfp_reviews', 'yes');
    ?>

    <div class="wrap">
        <h1>Admin Settings Fact Pod</h1>
        <form method="post">
            <?php wp_nonce_field('wpfp_save_settings'); ?>
            <h3>Allowed to Share</h3>
                <p>
                    <label for="wpfp_purchases">
                        <input type="checkbox" name="wpfp_purchases" id="wpfp_purchases" <?php checked($optionPurchases, 'yes'); ?> />
                        Purchases
                    </label>
                </p>
                <p>
                    <label for="wpfp_wish_lists">
                        <input type="checkbox" name="wpfp_wish_lists" id="wpfp_wish_lists" <?php checked($optionWishLists, 'yes'); ?> />
                        Wish Lists
                    </label>
                </p>
                <p>
                    <label for="wpfp_reviews">
                        <input type="checkbox" name="wpfp_reviews" id="wpfp_reviews" <?php checked($optionReviews, 'yes'); ?> />
                        Reviews
                    </label>
                </p>
            <?php submit_button('Save', 'primary', 'wpfp_settings_submit'); ?>
        </form>
    </div>

<?php
}