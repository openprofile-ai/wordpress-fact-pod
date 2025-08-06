<?php

defined('ABSPATH') || exit;

function wp_fact_pod_install(string $version) {
    $migrations = [
        '0.0.1' => ['migration_v_0_0_1.php'],

        // for future versions, list all needed migrations, including previous ones
        // '0.0.2' => ['migration_v_0_0_1.php', 'migration_v_0_0_2.php']
    ];

    if (isset($migrations[$version])) {
        $migration_files = $migrations[$version];

        foreach ($migration_files as $migration_file) {
            $migration = require_once WORDPRESS_FACT_POD_PATH . 'includes/Database/' . $migration_file;
            $migration->up();
        }
    }
}

function wp_fact_pod_generate_keys() {
    $key_dir = plugin_dir_path(__FILE__);
    $private_key_file = $key_dir . 'private.key';
    $public_key_file  = $key_dir . 'public.key';

    // Only generate if keys do not exist
    if (!file_exists($private_key_file) || !file_exists($public_key_file)) {
        $config = array(
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $private_key);
        $key_details = openssl_pkey_get_details($res);
        $public_key = $key_details['key'];

        file_put_contents($private_key_file, $private_key);
        @chmod($private_key_file, 0600);

        file_put_contents($public_key_file, $public_key);
        @chmod($public_key_file, 0644);
    }
}