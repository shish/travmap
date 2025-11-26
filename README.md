Notes on travmap hosting:
=========================

General use:
------------

Adding a new server:

```
./manage.py add s1.foo.com <timestamp> <mapfile>
# e.g., ./manage.py add s1.x3.europe.travian.com 1234567890 json
```

Removing a server:

```
./manage.py remove s1.foo.com
```

Updating data:

```
./manage.py update
# or for specific servers:
./manage.py update s1.foo.com s2.bar.com
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
docker exec sn-travmap /utils/manage.py update
```
