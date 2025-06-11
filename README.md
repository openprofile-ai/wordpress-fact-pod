# Official OpenProfile.AI WordPress Fact Pod Plugin

## WordPress & Apache configuration

### Requirements:

docker

### Setup:

Create and modify your own `.env` file:

```shell
cp .env-sample .env
```

Default host name is `docker.vm`.
See [Docker image documentation](https://dockerfile.readthedocs.io/en/latest/content/DockerImages/dockerfiles/php-apache-dev.html).

We need to add it to the `/etc/hosts` file: `127.0.0.1 docker.vm`.

Regenerate if you want your own SSL certificate files in `docker/apache/ssl` and uncomment the volume in `docker-compose.yml` file.
The `mkcert` tool can be used for it.
See [mkcert installation](https://github.com/FiloSottile/mkcert)

Execute command if you'd like to use `mkcert`:

```shell
mkcert -cert-file docker/apache/ssl/server.pem -key-file docker/apache/ssl/server.key docker.vm localhost 127.0.0.1 ::1
cp docker/apache/ssl/server.pem docker/apache/ssl/server.crt
```

Make sure you have a database `.sql` backup file in `/tmp` folder

If you don't have `wp-config.php` file, you can create if from sample file inside `app` folder:

```shell
cd app
cp wp-config-sample.php wp-config.php
```

Run docker:

```shell
docker compose up
```
Also there is Makefile with most popular commands.

Now you're able to reach you site via `https://docker.vm` or `http://docker.vm`.

Happy coding!