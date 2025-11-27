FROM debian:stable
EXPOSE 8000
# RUN apt update && apt install -y curl
# HEALTHCHECK --interval=1m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
VOLUME /data
VOLUME /cache

ENV PYTHONUNBUFFERED=1
# pcre fails to compile regexes if the packages aren't manually installed?
RUN apt update && apt install -y php-cli php-gd php-sqlite3 python3-requests libpcre2-16-0 libpcre2-8-0 libpcre2-32-0 sqlite3 rsync

COPY htdocs /htdocs
COPY utils /utils

ARG BUILD_HASH=unknown
ENV BUILD_HASH=${BUILD_HASH}
ARG BUILD_TIME=unknown
ENV BUILD_TIME=${BUILD_TIME}

WORKDIR /htdocs
CMD ["/bin/sh", "-c", "exec /usr/bin/php -S 0.0.0.0:8000 2>&1 | grep --line-buffered -vE ' (Accepted|Closing)'"]
