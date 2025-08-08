<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\ClientEntity;

class ClientRepository implements ClientRepositoryInterface
{
    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fact_pod_oauth_clients';

        $client = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %s",
                $clientIdentifier
            )
        );

        if (!$client) {
            return null;
        }

        return new ClientEntity(
            $client->id,
            $client->name,
            $client->redirect_uri,
        );
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fact_pod_oauth_clients';

        $client = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %s", $clientIdentifier
            )
        );

        if (!$client) {
            return false;
        }

        if (!$clientSecret || !password_verify($clientSecret, $client->secret)) {
            return false;
        }

        if ($client->grant_types) {
            $allowed = array_map('trim', explode(' ', $client->grant_types));
            if (!in_array($grantType, $allowed, true)) {
                return false;
            }
        }

        return true;
    }
}