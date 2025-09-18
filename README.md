# WordPress Fact Pod Plugin

## Setup

Generate keys for OAuth server:

```bash
openssl genrsa -out ./private.key 4096
openssl rsa -in private.key -pubout -outform PEM -out public.key
```

## .well-known Files

The plugin creates two important .well-known files that are essential for OpenProfile discovery and authentication:

### 1. openprofile.json

This file serves as the OpenProfile discovery document, allowing clients to automatically discover the OAuth endpoints and capabilities of your OpenProfile implementation. It is accessible at:

```
https://your-domain.com/.well-known/openprofile.json
```

#### Important Fields in openprofile.json

| Field                                   | Description                                                                                      |
|-----------------------------------------|--------------------------------------------------------------------------------------------------|
| `issuer`                                | The base URL of your WordPress site, used to identify the token issuer                           |
| `authorization_endpoint`                | The URL where users are redirected to authorize client applications                              |
| `token_endpoint`                        | The URL where client applications can exchange authorization codes for access tokens             |
| `registration_endpoint`                 | The URL where new client applications can register                                               |
| `jwks_uri`                              | The URL to the JSON Web Key Set (JWKS) containing the public keys used to verify tokens          |
| `response_types_supported`              | The OAuth 2.0 response types supported by this server (e.g., "code")                             |
| `grant_types_supported`                 | The OAuth 2.0 grant types supported by this server (e.g., "authorization_code", "refresh_token") |
| `token_endpoint_auth_methods_supported` | Authentication methods supported at the token endpoint                                           |
| `scopes_supported`                      | List of OAuth scopes supported by this server                                                    |
| `openprofile`                           | Contains OpenProfile artifacts like version                                                      |
| `factpod`                               | Contains Schema.ord JSON-LD formatted information about site and available categories            |


### 2. openprofile-jwks.json

This file contains the JSON Web Key Set (JWKS) with the public key information used to verify the signatures of JSON Web Tokens (JWTs) issued by your server. It is accessible at:

```
https://your-domain.com/.well-known/openprofile-jwks.json
```

#### Important Fields in openprofile-jwks.json

| Field  | Description                                  |
|--------|----------------------------------------------|
| `keys` | Array of JSON Web Keys (JWK)                 |
| `kty`  | Key type (e.g., "RSA")                       |
| `use`  | Public key use (e.g., "sig" for signature)   |
| `kid`  | Key ID used to match a specific key          |
| `alg`  | Algorithm used with this key (e.g., "RS256") |
| `n`    | RSA modulus value (base64url-encoded)        |
| `e`    | RSA exponent value (base64url-encoded)       |

### How Clients Use These Files

1. A client discovers your OpenProfile implementation by accessing the `.well-known/openprofile.json` endpoint
2. The client reads the endpoints from this file to know where to send authorization and token requests
3. When verifying tokens, the client uses the `jwks_uri` to fetch the public keys from `.well-known/openprofile-jwks.json`
4. The client can then use these keys to verify the signature of tokens issued by your server

## Add a test client with secret=SECRET

```sql
INSERT INTO `openprofile`.`wp_fact_pod_oauth_clients` (`id`, `name`, `secret`, `redirect_uri`, `grant_types`) VALUES ('40b46cf8-e2eb-491a-8e2a-6e38d164c377', 'Gateway', '$2y$10$iJ71798kw9EbYI/rZ2UzAeM7YUHSfIQgrNi.Q7HpUw/1btTpVQxoK', 'https://gateway.openprofile.ai/oauth/callback', 'authorization_code refresh_token');
```

## OAuth 2.0 Client Registration

To register a new OAuth client, make a POST request to the registration endpoint:

```
POST http://docker.vm/wp-json/openprofile/oauth/register
```

### Registration Parameters

| Parameter      | Required | Description                                                                  |
|----------------|----------|------------------------------------------------------------------------------|
| `name`         | Yes      | A descriptive name for the client application                                |
| `redirect_uri` | Yes      | The URI to redirect to after authorization is complete (must be a valid URL) |

### Example Registration Request

```
POST http://docker.vm/wp-json/openprofile/oauth/register
Content-Type: application/x-www-form-urlencoded

name=My%20Application&redirect_uri=https://example.com/callback&grant_types=authorization_code%20refresh_token
```

### Example Response

```json
{
  "client_id": "550e8400-e29b-41d4-a716-446655440000",
  "client_secret": "random_generated_secret_that_you_must_store",
  "name": "My Application",
  "redirect_uri": "https://example.com/callback",
  "grant_types": "authorization_code refresh_token"
}
```

> **Important**: The `client_secret` is only returned once during registration. Make sure to store it securely as it cannot be retrieved later.

## Facts API (schema.org)
Returns a schema.org ItemList of the authenticated user’s WooCommerce purchases filtered by product category.

### Endpoint
- GET /wp-json/openprofile/facts

### Authentication
- OAuth 2.0 Bearer token
- Header: Authorization: Bearer YOUR_ACCESS_TOKEN

### Parameters
| Name     | In    | Type   | Required | Description                                                     |
|----------|-------|--------|----------|-----------------------------------------------------------------|
| category | query | string | Yes      | WooCommerce product category slug (preferred) or category name. |

### Example request
```
curl -H "Authorization: Bearer eyJhbGciOi..." \
  "http://docker.vm/wp-json/openprofile/facts?category=body-lotion"
```

### Example response
```json
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "Purchases - Body lotion",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "item": {
        "@type": "Order",
        "@id": "http://docker.vm/my-account-2/view-order/1856/#item-17",
        "orderDate": "2023-09-18",
        "totalPrice": 28.0,
        "priceCurrency": "UAH",
        "seller": { "@type": "Organization", "name": "openprofile" },
        "orderedItem": {
          "@type": "Product",
          "@id": "http://docker.vm/product/339/",
          "name": "Almond Milk Lotion",
          "category": "Body lotion",
          "additionalType": "http://docker.vm/product-category/body-lotion/"
        }
      }
    }
  ]
}
```

### Notes
- Order @id is the order view URL with a line-item fragment (#item-{id}); it resolves and avoids 404s.
- Product @id uses the product permalink (falls back to /?p={id} if pretty permalinks are disabled).
- totalPrice is a number; dates are YYYY-MM-DD.

## OAuth 2.0 Authorization

### Generating the Authorization Link

To initiate the OAuth flow, generate an authorization link with the following format:

```
http://docker.vm/wp-json/openprofile/oauth/authorize?response_type=code&client_id=40b46cf8-e2eb-491a-8e2a-6e38d164c377&redirect_uri=https://gateway.openprofile.ai/oauth/callback&scope=facts:category-16%20facts:category-18%20facts:category-19&state=abc123xyz
```

#### Authorization Parameters

| Parameter       | Required    | Description                                                                            |
|-----------------|-------------|----------------------------------------------------------------------------------------|
| `response_type` | Yes         | Must be set to `code` for the authorization code flow                                  |
| `client_id`     | Yes         | The client identifier issued to the client during registration                         |
| `redirect_uri`  | Yes         | The URI to redirect to after authorization is complete (must match the registered URI) |
| `scope`         | Yes         | Space-separated list of scopes the client is requesting access to                      |
| `state`         | Recommended | An opaque value used by the client to maintain state between the request and callback  |

### Requesting an Access Token

After the user approves the authorization request, they will be redirected to the specified `redirect_uri` with an authorization code. This code can be exchanged for an access token by making a POST request to the token endpoint:

```
POST http://docker.vm/wp-json/openprofile/oauth/access_token
```

#### Token Request Parameters

| Parameter       | Required | Description                                                                 |
|-----------------|----------|-----------------------------------------------------------------------------|
| `grant_type`    | Yes      | Must be set to `authorization_code`                                         |
| `client_id`     | Yes      | The client identifier issued to the client during registration              |
| `client_secret` | Yes      | The client secret issued to the client during registration                  |
| `redirect_uri`  | Yes      | Must be identical to the redirect URI provided in the authorization request |
| `code`          | Yes      | The authorization code received from the authorization server               |

#### Example Token Request

```
POST http://docker.vm/wp-json/openprofile/oauth/access_token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&client_id=40b46cf8-e2eb-491a-8e2a-6e38d164c377&client_secret=SECRET&redirect_uri=https://gateway.openprofile.ai/oauth/callback&code=def502003d0e1b9b6...
```

#### Example Response

```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "def502003d0e1b9b6..."
}
```

### Requesting a Refresh Token

When an access token expires, you can use the refresh token to obtain a new access token without requiring the user to reauthorize:

```
POST http://docker.vm/wp-json/openprofile/oauth/access_token
```

#### Refresh Token Request Parameters

| Parameter       | Required | Description                                                                       |
|-----------------|----------|-----------------------------------------------------------------------------------|
| `grant_type`    | Yes      | Must be set to `refresh_token`                                                    |
| `refresh_token` | Yes      | The refresh token previously issued to the client                                 |
| `client_id`     | Yes      | The client identifier issued to the client during registration                    |
| `client_secret` | Yes      | The client secret issued to the client during registration                        |
| `scope`         | No       | Space-separated list of scopes (must be equal or a subset of the original scopes) |

#### Example Refresh Token Request

```
POST http://docker.vm/wp-json/openprofile/oauth/access_token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token&refresh_token=def502003d0e1b9b6...&client_id=40b46cf8-e2eb-491a-8e2a-6e38d164c377&client_secret=SECRET
```

#### Example Response

```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "def502003d0e1b9b6..."
}
```