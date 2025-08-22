<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\ClientEntity;
use OpenProfile\WordpressFactPod\Utils\AbstractRepository;

class ClientRepository extends AbstractRepository implements ClientRepositoryInterface
{
    public function getTable(): string
    {
        return self::getPrefix() . 'oauth_clients';
    }
    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $wpdb = self::getDB();
        $table = $this->getTable();

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
        $wpdb = self::getDB();
        $table = $this->getTable();

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
    
    /**
     * Check if a client with the given domain already exists
     *
     * @param string $domain The domain to check
     * @return bool True if a client with the domain exists, false otherwise
     */
    public function domainExists(string $domain): bool
    {
        $wpdb = self::getDB();
        $table = $this->getTable();
        
        $existingClient = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE domain = %s",
                $domain
            )
        );
        
        return $existingClient !== null;
    }
}