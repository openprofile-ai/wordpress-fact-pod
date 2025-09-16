<?php
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

use OpenProfile\WordpressFactPod\Utils\AbstractRepository;
use OpenProfile\WordpressFactPod\Utils\WooCommerce;

return new class {
    public function up()
    {
        $wpdb = AbstractRepository::getDB();

        $charsetCollate = $wpdb->get_charset_collate();
        $prefix = AbstractRepository::getPrefix();

        // Table: oauth_clients
        $sqlClients = "CREATE TABLE {$prefix}oauth_clients (
        id varchar(36) NOT NULL,
        name varchar(255) NOT NULL,
        secret varchar(100) DEFAULT NULL,
        redirect_uri varchar(255) NOT NULL,
        domain varchar(255) NOT NULL,
        grant_types text DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY domain (domain)
    ) $charsetCollate;";

        // Table: oauth_refresh_tokens
        $sqlRefreshTokens = "CREATE TABLE {$prefix}oauth_refresh_tokens (
        refresh_token varchar(100) NOT NULL,
        access_token VARCHAR(100) DEFAULT NULL,
        revoked TINYINT(1) DEFAULT 0,
        expires datetime NOT NULL,
        PRIMARY KEY  (refresh_token)
    ) $charsetCollate;";

        // Table: oauth_auth_codes
        $sqlAuthCodes = "CREATE TABLE {$prefix}oauth_auth_codes (
        authorization_code varchar(100) NOT NULL,
        client_id varchar(80) NOT NULL,
        user_id varchar(80) NOT NULL,
        redirect_uri text NOT NULL,
        expires datetime NOT NULL,
        scope text DEFAULT NULL,
        PRIMARY KEY  (authorization_code)
    ) $charsetCollate;";

        // Table: oauth_scopes
        $sqlScopes = "CREATE TABLE {$prefix}oauth_scopes (
        scope varchar(80) NOT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        description varchar(100) DEFAULT NULL,
        PRIMARY KEY  (scope)
    ) $charsetCollate;";


        $sqlAccessTokens = "CREATE TABLE {$prefix}oauth_access_tokens (
            access_token varchar(100) NOT NULL,
            client_id varchar(80) NOT NULL,
            user_id varchar(80) DEFAULT NULL,
            revoked tinyint(1) DEFAULT 0,
            expires datetime NOT NULL,
            scope text DEFAULT NULL,
            PRIMARY KEY  (access_token)
    ) $charsetCollate;";

        // Create tables
        dbDelta($sqlClients);
        dbDelta($sqlRefreshTokens);
        dbDelta($sqlAuthCodes);
        dbDelta($sqlScopes);
        dbDelta($sqlAccessTokens);

        $wpdb->query("
            ALTER TABLE {$prefix}oauth_refresh_tokens
            ADD CONSTRAINT fk_access_token
            FOREIGN KEY (access_token)
            REFERENCES {$prefix}oauth_access_tokens(access_token)
            ON DELETE CASCADE
    ");

        $this->addOauthScopes();
    }

    private function addOauthScopes() {
        $wpdb = AbstractRepository::getDB();
        $tableName = AbstractRepository::getPrefix() . 'oauth_scopes';
        $categories = WooCommerce::getTopLevelCategories();

        foreach ($categories as $category) {
            $scope = 'facts:category-' . $category['id'];

            $wpdb->query($wpdb->prepare(
                "REPLACE INTO $tableName (scope, description) VALUES (%s, %s)",
                $scope,
                sprintf('Purchases in the %s category', $category['name'])
            ));
        }

        // Insert additional scopes
        $additionalScopes = array(
            array(
                'scope' => 'facts:wishlist',
                'description' => 'Items in your wishlist',
            )
        );

        foreach ($additionalScopes as $scopeData) {
            $wpdb->query($wpdb->prepare(
                "REPLACE INTO $tableName (scope, description) VALUES (%s, %s)",
                $scopeData['scope'],
                $scopeData['description']
            ));
        }
    }
};