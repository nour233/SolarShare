FROM jenkins/jenkins:lts-jdk21
USER root

RUN apt-get update && apt-get install -y --no-install-recommends \
      php-cli php-mbstring php-xml php-curl php-zip php-sqlite3 \
      php-gd php-bcmath php-intl unzip curl ca-certificates \
 && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=docker:cli /usr/local/bin/docker /usr/local/bin/docker

RUN mkdir -p /usr/local/lib/docker/cli-plugins \
 && curl -fsSL https://github.com/docker/compose/releases/download/v2.40.3/docker-compose-linux-x86_64 \
      -o /usr/local/lib/docker/cli-plugins/docker-compose \
 && chmod +x /usr/local/lib/docker/cli-plugins/docker-compose