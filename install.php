<?php

defined('ABSPATH') || exit;

use OpenProfile\WordpressFactPod\Utils\WellKnown;

function wp_fact_pod_install_database(string $version):void {
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

function wp_fact_pod_generate_keys():void {
    $keyDir = WORDPRESS_FACT_POD_PATH;
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

function wp_fact_pod_publish_well_known_files():void {
    $siteUrl = get_site_url();
    
    // Get WordPress uploads directory
    $uploadDir = wp_upload_dir();
    
    // Create openprofile-well-known directory in uploads if it doesn't exist
    $wellKnownDir = $uploadDir['basedir'] . '/openprofile/well-known';
    if (!file_exists($wellKnownDir)) {
        wp_mkdir_p($wellKnownDir);
    }
    
    // Generate the JWKS
    $publicKeyPath = WORDPRESS_FACT_POD_PATH . 'public.key';
    $jwks = WellKnown::generateJwks($publicKeyPath);
    
    // Generate the OpenProfile discovery document
    $openProfileDiscovery = WellKnown::generateOpenProfileDiscovery($siteUrl);
    
    // Write the files
    file_put_contents($wellKnownDir . '/openprofile-jwks.json', json_encode($jwks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents($wellKnownDir . '/openprofile.json', json_encode($openProfileDiscovery, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // Set proper permissions
    @chmod($wellKnownDir . '/openprofile-jwks.json', 0644);
    @chmod($wellKnownDir . '/openprofile.json', 0644);
}