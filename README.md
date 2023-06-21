Notes on travmap hosting:
=========================

Requires Postgresql 8.X; MySQL's query optimiser manages to take perfectly
fast queries and make them painfully slow... If you really want to use
mysql it shouldn't be too hard to hack, but I don't have time to help (I
will gladly accept any patches which don't break the existing postgres
support though)

If you want to host all maps for all servers, 15GB of disk space is a good
idea (while there's only ~5GB of permanant data, caching and temp files
while updating can take a lot). For a single server, 50MB should be enough.


General use:
------------

Adding a new server:

```
./add_server s1.foo.com CountryName ServerNum
```

CountryName is what appears in the drop down list, servernum is the number
of the server within that country, used to order the results

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
