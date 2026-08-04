FROM dunglas/frankenphp
HEALTHCHECK --start-period=30s --start-interval=5s --interval=5m --timeout=3s \
    CMD curl --fail http://127.0.0.1:80/ || exit 1
VOLUME /data

ENV PYTHONUNBUFFERED=1
ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN=true

RUN install-php-extensions gd sqlite3 xml
RUN apt-get update && apt-get install -y curl python3-requests sqlite3 rsync

COPY utils/Caddyfile /etc/frankenphp/Caddyfile
COPY htdocs /htdocs
COPY utils /utils

ARG BUILD_HASH=unknown
ENV BUILD_HASH=${BUILD_HASH}
ARG BUILD_TIME=unknown
ENV BUILD_TIME=${BUILD_TIME}
