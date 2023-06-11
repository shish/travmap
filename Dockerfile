FROM debian:stable
EXPOSE 8000
# RUN apt update && apt install -y curl
# HEALTHCHECK --interval=1m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
ENV SQL_HOST=localhost \
    SQL_USER=travmap \
    SQL_PASS=travmap \
    SQL_DB=travmap
VOLUME /cache

ENV PYTHONUNBUFFERED 1
# pcre fails to compile regexes if the packages aren't manually installed?
RUN apt install -y php-cli php-gd php-pgsql postgresql-client python3-requests python3-psycopg2 libpcre2-16-0 libpcre2-8-0 libpcre2-32-0

COPY htdocs /app
COPY utils /utils
CMD ["/utils/docker_run.sh"]
