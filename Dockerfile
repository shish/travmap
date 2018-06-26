FROM python:3.6-slim-stretch
EXPOSE 8000
RUN apt update && apt install -y curl
HEALTHCHECK --interval=5m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
ENV DB_DSN=postgres://foo:bar@172.17.0.1/mydatabase
VOLUME /cache

ENV PYTHONUNBUFFERED 1
RUN apt install -y php7.0-cli php7.0-gd php7.0-pgsql
RUN /usr/local/bin/pip install --upgrade pip setuptools wheel
RUN /usr/local/bin/pip install requests psycopg2-binary

COPY htdocs /app
COPY utils /utils
WORKDIR /app
CMD ["/usr/bin/php", "-S", "0.0.0.0:8000"]

