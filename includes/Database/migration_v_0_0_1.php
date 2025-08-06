<?php
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

return new class {
    public function up()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'fact_pod_';

        // Table: oauth_clients
        $sqlClients = "CREATE TABLE {$prefix}oauth_clients (
        id varchar(36) NOT NULL,
        name varchar(255) NOT NULL,
        secret varchar(100) DEFAULT NULL,
        redirect_uri text NOT NULL,
        grant_types text DEFAULT NULL,
        PRIMARY KEY  (id)
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

        // Create tables
        dbDelta($sqlClients);
        dbDelta($sqlRefreshTokens);
        dbDelta($sqlAuthCodes);
        dbDelta($sqlScopes);

        $this->addOauthScopes();
    }

    private function addOauthScopes() {
        global $wpdb;

        $tableName = $wpdb->prefix . 'fact_pod_oauth_scopes';

        $categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
        ));

        foreach ($categories as $category) {
            $scope = 'facts:category-' . $category->term_id;

            // Check if scope already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tableName WHERE scope = %s",
                $scope
            ));

            if (!$exists) {
                $wpdb->insert(
                    $tableName,
                    array(
                        'scope' => $scope,
                        'description' => sprintf('Facts about client\'s purchases in the %s category', $category->name),
                    ),
                    array(
                        '%s',
                    )
                );
            }
        }

        // Insert additional scopes
        $additionalScopes = array(
            array(
                'scope' => 'facts:wishlist',
                'description' => 'Facts about items in the client\'s wishlist',
            )
        );

        foreach ($additionalScopes as $scopeData) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tableName WHERE scope = %s",
                $scopeData['scope']
            ));

            if (!$exists) {
                $wpdb->insert(
                    $tableName,
                    array(
                        'scope' => $scopeData['scope'],
                        'description' => $scopeData['description'],
                    ),
                    array(
                        '%s',
                        '%s',
                    )
                );
            }
        }
    }
};