FROM debian:buster
EXPOSE 8000
RUN apt update && apt install -y curl
HEALTHCHECK --interval=5m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
ENV DB_DSN=postgres://foo:bar@172.17.0.1/mydatabase
VOLUME /cache

ENV PYTHONUNBUFFERED 1
RUN apt install -y php7.3-cli php7.3-gd php7.3-pgsql postgresql-client python3-requests python3-psycopg2

COPY htdocs /app
COPY utils /utils
WORKDIR /app
CMD ["/usr/bin/php", "-S", "0.0.0.0:8000"]

