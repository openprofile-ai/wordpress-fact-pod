# WordPress Fact Pod Plugin

## Setup

Generate keys for OAuth server:

```bash
openssl genrsa -out ./private.key 4096
openssl rsa -in private.key -pubout -outform PEM -out public.key
```

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

| Parameter | Required | Description |
|-----------|----------|-------------|
| `name` | Yes | A descriptive name for the client application |
| `redirect_uri` | Yes | The URI to redirect to after authorization is complete (must be a valid URL) |

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

## OAuth 2.0 Authorization

### Generating the Authorization Link

To initiate the OAuth flow, generate an authorization link with the following format:

```
http://docker.vm/wp-json/openprofile/oauth/authorize?response_type=code&client_id=40b46cf8-e2eb-491a-8e2a-6e38d164c377&redirect_uri=https://gateway.openprofile.ai/oauth/callback&scope=facts:category-16%20facts:category-18%20facts:category-19&state=abc123xyz
```

#### Authorization Parameters

| Parameter | Required | Description |
|-----------|----------|-------------|
| `response_type` | Yes | Must be set to `code` for the authorization code flow |
| `client_id` | Yes | The client identifier issued to the client during registration |
| `redirect_uri` | Yes | The URI to redirect to after authorization is complete (must match the registered URI) |
| `scope` | Yes | Space-separated list of scopes the client is requesting access to |
| `state` | Recommended | An opaque value used by the client to maintain state between the request and callback |

### Requesting an Access Token

After the user approves the authorization request, they will be redirected to the specified `redirect_uri` with an authorization code. This code can be exchanged for an access token by making a POST request to the token endpoint:

```
POST http://docker.vm/wp-json/openprofile/oauth/access_token
```

#### Token Request Parameters

| Parameter | Required | Description |
|-----------|----------|-------------|
| `grant_type` | Yes | Must be set to `authorization_code` |
| `client_id` | Yes | The client identifier issued to the client during registration |
| `client_secret` | Yes | The client secret issued to the client during registration |
| `redirect_uri` | Yes | Must be identical to the redirect URI provided in the authorization request |
| `code` | Yes | The authorization code received from the authorization server |

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

| Parameter | Required | Description |
|-----------|----------|-------------|
| `grant_type` | Yes | Must be set to `refresh_token` |
| `refresh_token` | Yes | The refresh token previously issued to the client |
| `client_id` | Yes | The client identifier issued to the client during registration |
| `client_secret` | Yes | The client secret issued to the client during registration |
| `scope` | No | Space-separated list of scopes (must be equal or a subset of the original scopes) |

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