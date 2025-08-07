<?php

defined('ABSPATH') || exit;

add_filter('woocommerce_account_menu_items', function ($items) {
    unset($items['factpod']);

    $last = array_slice($items, -1, 1, true);
    $rest = array_slice($items, 0, -1, true);

    return $rest + ['factpod' => __('Fact Pod', 'woocommerce')] + $last;
}, 100);

add_action('woocommerce_account_factpod_endpoint', 'wpfp_render_fact_pod_tab');

function wpfp_render_fact_pod_tab() {
    if (!is_user_logged_in()) {
        echo '<p>Only for registered users.</p>';
        return;
    }
    $userId = get_current_user_id();

    $options = [
        'wpfp_purchases'  => get_option('wpfp_purchases', 'yes'),
        'wpfp_wish_lists' => get_option('wpfp_wish_lists', 'yes'),
        'wpfp_reviews'    => get_option('wpfp_reviews', 'yes'),
    ];
    $enabled = array_filter($options, fn($v) => $v === 'yes');

    if (empty($enabled)) {
        echo '<p>No options available.</p>';
        return;
    }

    $fake = isset($_POST['wpfp_fake_login']);
    echo '<h3>Fact Pod</h3>';

    if (!$fake) {
        echo '<form method="post">' .
                '<p><label>Login<br><input type="text" name="wpfp_login"></label></p>' .
                '<p><label>Password<br><input type="password" name="wpfp_password"></label></p>' .
                '<p><button type="submit" name="wpfp_fake_login" class="button">Login</button></p>' .
            '</form>';
    } else {
        echo '<form method="post">';
        wp_nonce_field('wpfp_save_form', 'wpfp_nonce');

        $labels = [
            'wpfp_purchases'  => 'Purchases',
            'wpfp_wish_lists' => 'Wish Lists',
            'wpfp_reviews'    => 'Reviews',
        ];

        foreach ($enabled as $key => $value) {
            $meta = get_user_meta($userId, $key, true);
            if ($meta === '') {
                update_user_meta($userId, $key, $value);
                $meta = $value;
            }
            echo '<p><label><input type="checkbox" name="' . esc_attr($key) . '" ' . checked($meta, 'yes', false)
                . '> ' . esc_html($labels[$key]) . '</label></p>';
        }

        echo '<p><button type="submit" name="wpfp_save" class="button">Save</button></p>';
        echo '</form>';
    }
}

add_action('template_redirect', function () {
    if (!is_account_page() || !isset($_POST['wpfp_save']) || !isset($_POST['wpfp_nonce'])) {
        return;
    }

    if (wp_verify_nonce($_POST['wpfp_nonce'], 'wpfp_save_form')) {
        $userId = get_current_user_id();
        foreach (['wpfp_purchases','wpfp_wish_lists','wpfp_reviews'] as $key) {
            update_user_meta($userId, $key, isset($_POST[$key]) ? 'yes' : 'no');
        }
        wc_add_notice('Saved successfully.', 'success');
        wp_redirect(wc_get_account_endpoint_url('factpod'));
        exit;
    }
});