FROM debian:stable
EXPOSE 8000
# RUN apt update && apt install -y curl
# HEALTHCHECK --interval=1m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
VOLUME /data
VOLUME /cache

ENV PYTHONUNBUFFERED 1
# pcre fails to compile regexes if the packages aren't manually installed?
RUN apt update && apt install -y php-cli php-gd php-sqlite3 python3-requests libpcre2-16-0 libpcre2-8-0 libpcre2-32-0

COPY htdocs /app
COPY utils /utils
RUN echo '<?php $sql_dsn = "sqlite:/data/travmap.sqlite";' >/app/config.php
RUN echo "SQL_DB=/data/travmap.sqlite\nCACHE=/cache\nSTATUS=/app/status.txt" >/utils/config.sh

CMD cd /app && /usr/bin/php -S 0.0.0.0:8000 | grep --line-buffered -vE " (Accepted|Closing)"
