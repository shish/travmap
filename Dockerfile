FROM debian:stable
EXPOSE 8000
RUN apt update && apt install -y curl
HEALTHCHECK --interval=1m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
ENV SQL_HOST=localhost \
    SQL_USER=travmap \
    SQL_PASS=travmap \
    SQL_DB=travmap
VOLUME /cache

ENV PYTHONUNBUFFERED 1
RUN apt install -y php-cli php-gd php-pgsql postgresql-client python3-requests python3-psycopg2

COPY htdocs /app
COPY utils /utils
CMD ["/utils/docker_run.sh"]
