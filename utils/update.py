#!/usr/bin/env python

#
# update.py (c) Shish 2015
#

import re
import os
import sys
import json
import gzip
import requests
import psycopg2
from glob import glob
from time import time
from datetime import datetime
from cStringIO import StringIO
from collections import namedtuple


fields = ['name', 'mapfile', 'privateApiKey', 'publicSiteKey']

conn = None


def set_global_status(text):
    text += " at %s" % str(datetime.now())[:16]
    print(text)
    open(os.environ['STATUS'], 'w').write(text)


def cache_name(server, ext):
    return os.path.join(os.environ["CACHE"], server + ext)


def is_cached(path):
    if os.path.exists(path):
        if os.stat(path).st_mtime > time() - 12 * 60 * 60:
            return True
    return False


def safe(x):
    return unicode(x).replace('\\', '\\\\').replace("\t", " ")


class Server(namedtuple('Server', fields)):
    @property
    def dbname(self):
        return self.name.replace("-", "_").replace(".", "_")

    def update(self):
        set_global_status("Updating %s" % self.name)
        if self.mapfile == "map":
            if self.update_text():
                self.load_data(self._create_data_from_text())
        elif self.mapfile == "json":
            if self.update_json():
                self.load_data(self._create_data_from_json())
        self.set_status("ok")

    def set_status(self, text):
        print("[%s] %s" % (self.name, text))
        with conn.cursor() as cur:
            cur.execute(
                "UPDATE servers SET status=%(status)s WHERE name=%(name)s",
                {"name": self.name, "status": text}
            )
            conn.commit()

    def fetch(self, url, path, params=None):
        if is_cached(path):
            self.set_status("map data cached")
            return True

        try:
            res = requests.get(url, stream=True, params=params)
            length = res.headers.get('content-length')
            fp = file(path + ".tmp", "w")
            if length is None:
                fp.write(res.content)
            else:
                length = int(length)
                done = 0
                last_perc = -1
                for data in res.iter_content():
                    done += len(data)
                    perc = int(100 * done / length)
                    if perc != last_perc and perc % 20 == 0:
                        self.set_status("%d%% downloaded" % perc)
                        last_perc = perc
                    fp.write(data)
            os.rename(path + ".tmp", path)
            return True
        except Exception as e:
            print(e)
            return False

    ###################################################################
    # both school
    def load_data(self, data):
        self.set_status("loading data...")
        cur = conn.cursor()

        # tables
        try:
            cur.execute("""
                DROP TABLE DBNAME;
            """.replace("DBNAME", self.dbname));
        except Exception:
            conn.rollback()
        cur.execute("SET client_encoding = 'UTF8';")
        cur.execute("""
            CREATE TABLE DBNAME(
                lochash INTEGER NOT NULL, x SMALLINT NOT NULL, y SMALLINT NOT NULL, race SMALLINT NOT NULL,
                town_id  INTEGER NOT NULL, town_name  VARCHAR(128) NOT NULL, -- 20
                owner_id INTEGER NOT NULL, owner_name VARCHAR(128) NOT NULL, -- 16
                guild_id INTEGER NOT NULL, guild_name VARCHAR(64) NOT NULL,  -- 8
                population SMALLINT NOT NULL
            );
        """.replace("DBNAME", self.dbname))

        # data
        fp = StringIO(data.encode('utf8'))
        try:
            cur.copy_from(fp, self.dbname, columns="lochash, x, y, race, town_id, town_name, owner_id, owner_name, guild_id, guild_name, population".split(", "))
        except Exception as e:
            self.set_status(str(e))
            conn.rollback()
            #file(cache_name(self.name, ".err"), "w").write(data.encode('utf8'))
            return

        # metadata
        cur.execute("""
            CREATE INDEX DBNAME_town_id ON DBNAME(town_id);
            CREATE INDEX DBNAME_town_name ON DBNAME(town_name);
            -- CREATE INDEX DBNAME_town_name_lower ON DBNAME(lower(town_name));
            CREATE INDEX DBNAME_owner_id ON DBNAME(owner_id);
            CREATE INDEX DBNAME_owner_name ON DBNAME(owner_name);
            -- CREATE INDEX DBNAME_owner_name_lower ON DBNAME(lower(owner_name));
            CREATE INDEX DBNAME_guild_id ON DBNAME(guild_id);
            CREATE INDEX DBNAME_guild_name ON DBNAME(guild_name);
            -- CREATE INDEX DBNAME_guild_name_lower ON DBNAME(lower(guild_name));
            CREATE INDEX DBNAME_x ON DBNAME(x);
            CREATE INDEX DBNAME_y ON DBNAME(y);
            -- CREATE INDEX DBNAME_diag ON DBNAME((x-y));
            -- CREATE INDEX DBNAME_race ON DBNAME(race);
            CREATE INDEX DBNAME_population ON DBNAME(population);
        """.replace("DBNAME", self.dbname))
        cur.execute("""
            UPDATE servers
            SET
                villages=(SELECT COUNT(*) FROM DBNAME),
                owners=(SELECT COUNT(DISTINCT owner_id) FROM DBNAME),
                guilds=(SELECT COUNT(DISTINCT guild_id) FROM DBNAME),
                population=coalesce((SELECT SUM(population) FROM DBNAME), 0),
                width =coalesce((SELECT MAX(x) - MIN(x) FROM DBNAME), 0),
                height=coalesce((SELECT MAX(y) - MIN(y) FROM DBNAME), 0),
                updated=now()
            WHERE name=%s;
        """.replace('DBNAME', self.dbname), [self.name])

        conn.commit()

    ###################################################################
    # old school
    def update_text(self):
        self.set_status("downloading sql...")
        path = cache_name(self.name, ".sql.gz")

        if self.fetch("http://%s/map.sql.gz" % self.name, path):
            if os.stat(path).st_size < 64 * 1024:
                self.set_status("map.sql.gz is short")
            else:
                self.set_status("map.sql.gz downloaded")
            return True

        self.set_status("map.sql.gz missing")
        return False

    def _create_data_from_text(self):
        data = []
	p = re.compile("(\d+),(-?\d+),(-?\d+),(\d+),(\d+),'(.*)',(\d+),'(.*)',(\d+),'(.*)',(\d+)")
        for line in gzip.open(cache_name(self.name, ".sql.gz")):
            try:
                line = line.decode("uso-8859-1")
            except Exception:
                line = line.decode("utf8")
	    line = line.replace("INSERT INTO `x_world` VALUES (", "")
	    line = line.replace(");", "")
            for subline in line.split("),("):
                m = p.match(subline)
                if m:
                    data.append("\t".join([safe(x) for x in m.groups()]))
        return "\n".join(data)


    ###################################################################
    # new school
    def get_key(self):
        url = "http://%s/api/external.php" % self.name
        if not (self.privateApiKey and self.publicSiteKey):
            self.set_status("generating key...")
            res = requests.get(url, params={
                'action': 'requestApiKey',
                'email': "shish+travmap@shishnet.org",
                'siteName': "TravMap",
                'siteUrl': "http://travmap.shishnet.org/",
                'public': 'true',
            })
            with conn.cursor() as cur:
                j = res.json()['response']
                cur.execute(
                    """
                    UPDATE servers
                    SET privateApiKey=%s, publicSiteKey=%s, mapfile=%s
                    WHERE name=%s
                    """,
                    (j['privateApiKey'], j['publicSiteKey'], 'json', self.name)
                )
                conn.commit()
                return j['privateApiKey']
        return self.privateApiKey

    def update_json(self):
        self.set_status("downloading json...")
        path = cache_name(self.name, ".json")

        params = {
            'action': 'getMapData',
            'privateApiKey': self.get_key(),
        }
        if self.fetch("http://%s/api/external.php" % self.name, path, params):
            if os.stat(path).st_size < 64 * 1024:
                self.set_status("map.json is short")
            else:
                self.set_status("map.json downloaded")
            return True

        self.set_status("map.json missing")
        return False

    def _create_data_from_json(self):
        data = []
        j = json.load(open(cache_name(self.name, '.json')))['response']
        alliances = {0: {'nameShort': ''}}
        for alliance in j['alliances']:
            alliances[int(alliance['allianceId'])] = alliance
        for player in j['players']:
            for village in player['villages']:
                data.append("\t".join([safe(x) for x in [
                    village['villageId'],
                    village['x'],
                    village['y'],
                    '0',
                    village['villageId'],
                    village['name'],
                    player['playerId'],
                    player['name'],
                    player['allianceId'],
                    alliances[int(player['allianceId'])]['nameShort'],
                    village['population'],
                ]]))
        return "\n".join(data)


def get_config():
    for line in open('config.sh'):
        k, _, v = line.strip().partition("=")
        os.environ[k] = v


def clear_cache():
    set_global_status("Cleaning cache")
    for fn in glob(os.path.join(os.environ["CACHE"], "*.txt")):
        os.unlink(fn)


def update_servers(servers):
    with conn.cursor() as cur:
        cur.execute("""
            SELECT """ + ", ".join(fields) + """
            FROM servers
            WHERE visible=True
            ORDER BY country, num
        """)
        for row in cur.fetchall():
            s = Server(*row)
            if s.name in servers or not servers:
                try:
                    s.update()
                except Exception as e:
                    s.set_status(str(e))


def connect():
    global conn
    dsn = "host=%s dbname=%s user=%s password=%s" % \
        (os.environ["SQL_HOST"], os.environ["SQL_DB"],
        os.environ["SQL_USER"], os.environ["SQL_PASS"])
    conn = psycopg2.connect(dsn)


def main(argv):
    try:
        get_config()
        set_global_status("Update starting")
        connect()
        #mkdirs()
        update_servers(argv[1:])
        clear_cache()
        set_global_status("Update complete")
    except KeyboardInterrupt:
        set_global_status("Interrupted")


if __name__ == "__main__":
    sys.exit(main(sys.argv))

