FROM php:8.1-apache

# Pin the Docker CLI version. Bump as needed; see
# https://download.docker.com/linux/static/stable/<arch>/
ARG DOCKER_CLI_VERSION=27.3.1

# mysqli for the app; curl + ca-certificates only to fetch the static CLI.
RUN docker-php-ext-install mysqli \
    && apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install ONLY the Docker client from Docker's official static release archive.
# This avoids the heavy/version-lagged Debian `docker.io` package (which also
# pulls dockerd/containerd/runc we don't need). The archive contains the full
# engine; we extract just `docker/docker` (the CLI) and install it world-
# executable (0755) at /usr/bin/docker so www-data can run it natively.
RUN set -eux; \
    arch="$(uname -m)"; \
    case "$arch" in \
        x86_64)  dockerArch='x86_64'  ;; \
        aarch64) dockerArch='aarch64' ;; \
        *) echo "Unsupported architecture: $arch" >&2; exit 1 ;; \
    esac; \
    curl -fsSL "https://download.docker.com/linux/static/stable/${dockerArch}/docker-${DOCKER_CLI_VERSION}.tgz" -o /tmp/docker.tgz; \
    tar -xzf /tmp/docker.tgz -C /tmp docker/docker; \
    install -o root -g root -m 0755 /tmp/docker/docker /usr/bin/docker; \
    rm -rf /tmp/docker /tmp/docker.tgz; \
    docker --version

# Serve from the new public/ directory so config/ and lib/ live OUTSIDE the web
# root and can never be fetched over HTTP.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copies src/{public,config,lib} -> /var/www/html/{public,config,lib}.
COPY src/ /var/www/html/
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# The entrypoint grants www-data access to the mounted docker socket, then hands
# off to the base image's apache2-foreground.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
