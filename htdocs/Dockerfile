FROM debian:stretch-slim
ENV DB_DSN=postgres://foo:bar@172.17.0.1/mydatabase
EXPOSE 8000

RUN apt update
RUN apt install -y php7.0-cli php7.0-gd php7.0-pgsql

RUN mkdir /cache
COPY . /app
WORKDIR /app
CMD ["/usr/bin/php", "-S", "0.0.0.0:8000"]

