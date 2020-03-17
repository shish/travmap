FROM debian:buster
EXPOSE 8000
RUN apt update && apt install -y curl
HEALTHCHECK --interval=5m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
ENV SQL_HOST=localhost \
    SQL_USER=travmap \
    SQL_PASS=travmap \
    SQL_DB=travmap
VOLUME /cache

ENV PYTHONUNBUFFERED 1
RUN apt install -y php7.3-cli php7.3-gd php7.3-pgsql postgresql-client python3-requests python3-psycopg2

COPY htdocs /app
COPY utils /utils
CMD ["/utils/docker_run.sh"]
