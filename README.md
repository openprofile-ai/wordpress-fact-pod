# wordpress-fact-pod-plugin

openssl genrsa -out ./private.key 4096
openssl rsa -in private.key -pubout -outform PEM -out public.key

http://docker.vm/wp-json/openprofile/oauth/authorize?response_type=code&client_id=40b46cf8-e2eb-491a-8e2a-6e38d164c377&redirect_url=https://gateway.openprofile.ai/oauth/callback&scope=facts:read&state=abc123xyz

CREATE TABLE wp_fact_pod_oauth_clients (
id varchar(36) NOT NULL,
name varchar(255) NOT NULL,
secret varchar(100) DEFAULT NULL,
redirect_uri text NOT NULL,
grant_types text DEFAULT NULL,
PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE wp_fact_pod_oauth_refresh_tokens (
refresh_token varchar(100) NOT NULL,
access_token VARCHAR(100) DEFAULT NULL,
revoked TINYINT(1) DEFAULT 0,
expires datetime NOT NULL,
PRIMARY KEY  (refresh_token)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE wp_fact_pod_oauth_auth_codes (
authorization_code varchar(100) NOT NULL,
client_id varchar(80) NOT NULL,
user_id varchar(80) NOT NULL,
redirect_uri text NOT NULL,
expires datetime NOT NULL,
scope text DEFAULT NULL,
PRIMARY KEY  (authorization_code)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE wp_fact_pod_oauth_scopes (
scope varchar(80) NOT NULL,
is_active tinyint(1) NOT NULL DEFAULT 1,
description varchar(100) DEFAULT NULL,
PRIMARY KEY  (scope)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

INSERT INTO `openprofile`.`wp_fact_pod_oauth_clients` (`id`, `name`, `secret`, `redirect_uri`, `grant_types`) VALUES ('40b46cf8-e2eb-491a-8e2a-6e38d164c377', 'Gateway', 'SECRET', 'https://gateway.openprofile.ai/oauth/callback', 'authorization_code, refresh_token');