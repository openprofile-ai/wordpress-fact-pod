<?php
return new class {
    public function up()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'fact_pod_';

        // Table: oauth_clients
        $sql_clients = "CREATE TABLE {$prefix}oauth_clients (
        id varchar(36) NOT NULL,
        name varchar(255) NOT NULL,
        secret varchar(100) DEFAULT NULL,
        redirect_uri text NOT NULL,
        grant_types text DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

        // Table: oauth_refresh_tokens
        $sql_refresh_tokens = "CREATE TABLE {$prefix}oauth_refresh_tokens (
        refresh_token varchar(100) NOT NULL,
        access_token VARCHAR(100) DEFAULT NULL,
        revoked TINYINT(1) DEFAULT 0,
        expires datetime NOT NULL,
        PRIMARY KEY  (refresh_token)
    ) $charset_collate;";

        // Table: oauth_auth_codes
        $sql_auth_codes = "CREATE TABLE {$prefix}oauth_auth_codes (
        authorization_code varchar(100) NOT NULL,
        client_id varchar(80) NOT NULL,
        user_id varchar(80) NOT NULL,
        redirect_uri text NOT NULL,
        expires datetime NOT NULL,
        scope text DEFAULT NULL,
        PRIMARY KEY  (authorization_code)
    ) $charset_collate;";

        // Table: oauth_scopes
        $sql_scopes = "CREATE TABLE {$prefix}oauth_scopes (
        scope varchar(80) NOT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        description varchar(100) DEFAULT NULL,
        PRIMARY KEY  (scope)
    ) $charset_collate;";

        // Create tables
        dbDelta($sql_clients);
        dbDelta($sql_refresh_tokens);
        dbDelta($sql_auth_codes);
        dbDelta($sql_scopes);

        $this->addOauthScopes();
    }

    private function addOauthScopes() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'fact_pod_oauth_scopes';

        $categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
        ));

        foreach ($categories as $category) {
            $scope = 'facts:category-' . $category->term_id;

            // Check if scope already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE scope = %s",
                $scope
            ));

            if (!$exists) {
                $wpdb->insert(
                    $table_name,
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
        $additional_scopes = array(
            array(
                'scope' => 'facts:wishlist',
                'description' => 'Facts about items in the client\'s wishlist',
            )
        );

        foreach ($additional_scopes as $scope_data) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE scope = %s",
                $scope_data['scope']
            ));

            if (!$exists) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'scope' => $scope_data['scope'],
                        'description' => $scope_data['description'],
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