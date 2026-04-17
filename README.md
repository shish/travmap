# TravMap - Travian Game Mapper

A mapping tool for the online game Travian, displaying players, alliances, and villages on interactive maps.

## Requirements

- PHP 8.4
- SQLite3
- GD library for image generation
- Python 3.x (for management scripts)

## General use

```bash
cd htdocs && php -S 127.0.0.1:8000 router.php
./manage.py add ts1.x3.europe.travian.com
./manage.py remove ts1.x3.europe.travian.com
./manage.py update
```

`update` can be run daily from cron

## Docker use

Build image:

```bash
docker build -t travmap .
```

Run it, exposing public port 8805:

```bash
docker run --name sn-travmap --rm -ti -p 0.0.0.0:8805:8000 -t travmap
```

Run this as a cronjob to update internal data:

```bash
docker exec sn-travmap /utils/manage.py update
```
