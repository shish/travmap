Notes on travmap hosting:
=========================

General use:
------------

Adding a new server:

```
./add_server s1.foo.com "Country Name"
```

Updating data:

```
./update.sh
```

Can be run daily from cron


Docker use:
-----------
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
docker exec sn-travmap /utils/update.py
```
