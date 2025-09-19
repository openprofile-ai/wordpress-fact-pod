<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\ClientEntity;
use OpenProfile\WordpressFactPod\Utils\AbstractRepository;

class ClientRepository extends AbstractRepository implements ClientRepositoryInterface
{
    const array GRAND_TYPES = ['authorization_code', 'refresh_token'];

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
                "SELECT * FROM $table WHERE id = %s",
                $clientIdentifier
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

    /**
     * @return ClientEntityInterface[]
     */
    public function getClients(): array
    {
        $wpdb = self::getDB();
        $table = $this->getTable();

        $clients = $wpdb->get_results("SELECT id, name, redirect_uri FROM $table");

        if (!$clients) {
            return [];
        }

        return array_map(
            fn($client) => new ClientEntity(
                $client->id,
                $client->name,
                $client->redirect_uri
            ),
            $clients
        );
    }

    /**
     * @throws \Exception
     */
    public function createClient(string $clientId, string $name, string $clientSecret, string $redirectUri): void
    {
        $wpdb = self::getDB();
        $domain = parse_url($redirectUri, PHP_URL_HOST);

        $result = $wpdb->insert(
            $this->getTable(),
            array(
                'id'           => $clientId,
                'name'         => $name,
                'secret'       => $clientSecret,
                'redirect_uri' => $redirectUri,
                'domain'       => $domain,
                'grant_types'  => implode(' ', self::GRAND_TYPES),
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        if ($result === false) {
            error_log('Failed to create client: ' . $wpdb->last_error);

            throw new \Exception('Failed to create client.');
        }
    }
}
