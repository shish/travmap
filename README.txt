Notes on travmap hosting:
~~~~~~~~~~~~~~~~~~~~~~~~~

Requires Postgresql 8.X; MySQL's query optimiser manages to take perfectly
fast queries and make them painfully slow... If you really want to use
mysql it shouldn't be too hard to hack, but I don't have time to help (I
will gladly accept any patches which don't break the existing postgres
support though)

arialuni.ttf is used as the font; this is not included as it's 22MB and
IIRC copyrighted by someone other than me...

If you want to host all maps for all servers, 15GB of disk space is a good
idea (while there's only ~5GB of permanant data, caching and temp files
while updating can take a lot). For a single server, 50MB should be enough.


General use:
~~~~~~~~~~~~

Adding a new server:
 ./add_server s1.foo.com CountryName ServerNum

CountryName is what appears in the drop down list, servernum is the number
of the server within that country, used to order the results

Updating data:
 ./update.sh

Can be run daily from cron
