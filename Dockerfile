FROM debian:stable
EXPOSE 8000
# RUN apt update && apt install -y curl
# HEALTHCHECK --interval=1m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
VOLUME /cache

ENV PYTHONUNBUFFERED 1
# pcre fails to compile regexes if the packages aren't manually installed?
RUN apt update && apt install -y php-cli php-gd php-sqlite3 python3-requests libpcre2-16-0 libpcre2-8-0 libpcre2-32-0

COPY htdocs /app
COPY utils /utils
CMD ["/utils/docker_run.sh"]
