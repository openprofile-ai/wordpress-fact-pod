# wordpress-fact-pod-plugin

openssl genrsa -out ./private.key 4096
openssl rsa -in private.key -pubout -outform PEM -out public.key

## Add a test client with secret=SECRET

```sql
INSERT INTO `openprofile`.`wp_fact_pod_oauth_clients` (`id`, `name`, `secret`, `redirect_uri`, `grant_types`) VALUES ('40b46cf8-e2eb-491a-8e2a-6e38d164c377', 'Gateway', '$2y$10$iJ71798kw9EbYI/rZ2UzAeM7YUHSfIQgrNi.Q7HpUw/1btTpVQxoK', 'https://gateway.openprofile.ai/oauth/callback', 'authorization_code refresh_token');
```

## Redirect to login

http://docker.vm/wp-json/openprofile/oauth/authorize?response_type=code&client_id=40b46cf8-e2eb-491a-8e2a-6e38d164c377&redirect_uri=https://gateway.openprofile.ai/oauth/callback&scope=facts:category-16%20facts:category-18%20facts:category-19%20facts:category-26%20facts:category-36%20facts:category-75%20facts:category-76%20facts:category-77%20facts:category-78%20facts:category-79%20facts:category-80%20facts:wishlist&state=abc123xyz