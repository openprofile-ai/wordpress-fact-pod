<?php

defined('ABSPATH') || exit;

function wp_fact_pod_install(string $version) {
    $migrations = [
        '0.0.1' => ['migration_v_0_0_1.php'],

        // for future versions, list all needed migrations, including previous ones
        // '0.0.2' => ['migration_v_0_0_1.php', 'migration_v_0_0_2.php']
    ];

    if (isset($migrations[$version])) {
        $migrationFiles = $migrations[$version];

        foreach ($migrationFiles as $migrationFile) {
            $migration = require_once WORDPRESS_FACT_POD_PATH . 'includes/Database/' . $migrationFile;
            $migration->up();
        }
    }
}

function wp_fact_pod_generate_keys() {
    $keyDir = plugin_dir_path(__FILE__);
    $privateKeyFile = $keyDir . 'private.key';
    $publicKeyFile  = $keyDir . 'public.key';

    // Only generate if keys do not exist
    if (!file_exists($privateKeyFile) || !file_exists($publicKeyFile)) {
        $config = array(
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $keyDetails = openssl_pkey_get_details($res);
        $publicKey = $keyDetails['key'];

        file_put_contents($privateKeyFile, $privateKey);
        @chmod($privateKeyFile, 0600);

        file_put_contents($publicKeyFile, $publicKey);
        @chmod($publicKeyFile, 0644);
    }
}