# Notes on travmap hosting

## General use

```
cd htdocs && php -S 127.0.0.1:8000
./manage.py add ts1.x3.europe.travian.com
./manage.py remove ts1.x3.europe.travian.com
./manage.py update
```

`update` can be run daily from cron


## Docker use

Build image:

```
docker build -t travmap .
```

Run it, exposing public port 8805:

```
docker run --name sn-travmap --rm -ti -p 0.0.0.0:8805:8000 -t travmap
```

Run this as a cronjob to update internal data:

```
docker exec sn-travmap /utils/manage.py update
```
