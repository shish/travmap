#!/usr/bin/env python3

#
# update.py (c) Shish 2015
#

import re
import os
import sys
import json
import gzip
import requests
import sqlite3
from contextlib import closing
from glob import glob
from time import time
from datetime import datetime
import typing as t
from collections import namedtuple


fields = ["name", "mapfile", "privateApiKey", "publicSiteKey"]

conn = None


def set_global_status(text: str) -> None:
    text += " at %s" % str(datetime.now())[:16]
    print(text)
    open(os.environ["STATUS"], "w").write(text)


def cache_name(server: str, ext: str) -> str:
    return os.path.join(os.environ["CACHE"], server + ext)


def is_cached(path: str) -> bool:
    if os.path.exists(path):
        if os.stat(path).st_mtime > time() - 12 * 60 * 60:
            return True
    return False


def safe(x: str) -> str:
    return str(x).replace("\\", "\\\\").replace("\t", " ")


class Server(t.NamedTuple):
    name: str
    mapfile: str
    privateApiKey: str
    publicSiteKey: str

    @property
    def dbname(self) -> str:
        return self.name.replace("-", "_").replace(".", "_")

    def update(self) -> None:
        set_global_status("Updating %s" % self.name)
        if self.mapfile == "map":
            if self.update_text():
                self.load_data(self._create_data_from_text())
                self.set_status("ok")
        elif self.mapfile == "map.gz":
            if self.update_text_gz():
                self.load_data(self._create_data_from_text_gz())
                self.set_status("ok")
        elif self.mapfile == "json":
            if self.update_json():
                self.load_data(self._create_data_from_json())
                self.set_status("ok")

    def set_status(self, text: str) -> None:
        print(f"[{self.name}] {text}")
        with closing(conn.cursor()) as cur:
            cur.execute("UPDATE servers SET status=? WHERE name=?", (text[:250], self.name))
            conn.commit()

    def fetch(self, url: str, path: str, params=None) -> bool:
        if is_cached(path):
            self.set_status("map data cached")
            return True

        try:
            res = requests.get(url, stream=True, params=params)
            if res.status_code != 200:
                raise Exception("Error %d while fetching %s" % (res.status_code, url))
            with open(path + ".tmp", "wb") as fp:
                fp.write(res.content)
            os.rename(path + ".tmp", path)
            return True
        except Exception as e:
            self.set_status("Fetch: " + str(e))
            return False

    ###################################################################
    # both school
    def load_data(self, data):
        self.set_status("loading data...")
        cur = conn.cursor()

        # tables
        try:
            cur.execute(
                """
                DROP TABLE DBNAME;
            """.replace(
                    "DBNAME", self.dbname
                )
            )
        except Exception:
            conn.rollback()
        cur.execute(
            """
            CREATE TABLE DBNAME(
                lochash INTEGER NOT NULL, x SMALLINT NOT NULL, y SMALLINT NOT NULL, race SMALLINT NOT NULL,
                town_id  INTEGER NOT NULL, town_name  VARCHAR(128) NOT NULL, -- 20
                owner_id INTEGER NOT NULL, owner_name VARCHAR(128) NOT NULL, -- 16
                guild_id INTEGER NOT NULL, guild_name VARCHAR(64) NOT NULL,  -- 8
                population SMALLINT NOT NULL
            );
        """.replace(
                "DBNAME", self.dbname
            )
        )

        # data
        try:
            cur.executemany(
                f"INSERT INTO {self.dbname} VALUES (:lochash, :x, :y, :race, :town_id, :town_name, :owner_id, :owner_name, :guild_id, :guild_name, :population)",
                data,
            )
        except Exception as e:
            self.set_status("Load: " + str(e))
            conn.rollback()
            # open(cache_name(self.name, ".err"), "w").write(data.encode('utf8'))
            return

        # metadata
        cur.execute(f"CREATE INDEX {self.dbname}_town_id ON {self.dbname}(town_id)")
        cur.execute(f"CREATE INDEX {self.dbname}_town_name ON {self.dbname}(town_name)")
        # cur.execute(f"CREATE INDEX {self.dbname}_town_name_lower ON {self.dbname}(lower(town_name))")
        cur.execute(f"CREATE INDEX {self.dbname}_owner_id ON {self.dbname}(owner_id)")
        cur.execute(f"CREATE INDEX {self.dbname}_owner_name ON {self.dbname}(owner_name)")
        # cur.execute(f"CREATE INDEX {self.dbname}_owner_name_lower ON {self.dbname}(lower(owner_name))")
        cur.execute(f"CREATE INDEX {self.dbname}_guild_id ON {self.dbname}(guild_id)")
        cur.execute(f"CREATE INDEX {self.dbname}_guild_name ON {self.dbname}(guild_name)")
        # cur.execute(f"CREATE INDEX {self.dbname}_guild_name_lower ON {self.dbname}(lower(guild_name))")
        cur.execute(f"CREATE INDEX {self.dbname}_x ON {self.dbname}(x)")
        cur.execute(f"CREATE INDEX {self.dbname}_y ON {self.dbname}(y)")
        # cur.execute(f"CREATE INDEX {self.dbname}_diag ON {self.dbname}((x-y))")
        # cur.execute(f"CREATE INDEX {self.dbname}_race ON {self.dbname}(race)")
        cur.execute(f"CREATE INDEX {self.dbname}_population ON {self.dbname}(population)")
        cur.execute(
            f"""
            UPDATE servers
            SET
                villages=(SELECT COUNT(*) FROM {self.dbname}),
                owners=(SELECT COUNT(DISTINCT owner_id) FROM {self.dbname}),
                guilds=(SELECT COUNT(DISTINCT guild_id) FROM {self.dbname}),
                population=coalesce((SELECT SUM(population) FROM {self.dbname}), 0),
                width =coalesce((SELECT MAX(x) - MIN(x) FROM {self.dbname}), 0),
                height=coalesce((SELECT MAX(y) - MIN(y) FROM {self.dbname}), 0),
                updated=?
            WHERE name=?;
        """,
            (str(datetime.now()), self.name),
        )

        conn.commit()

    ###################################################################
    # old school
    def update_text(self) -> bool:
        self.set_status("downloading sql...")
        path = cache_name(self.name, ".sql")

        if self.fetch("http://%s/map.sql" % self.name, path):
            if os.stat(path).st_size < 64 * 1024:
                self.set_status("map.sql is short")
            else:
                self.set_status("map.sql downloaded")
            return True

        self.set_status("map.sql missing")
        return False

    def _create_data_from_text(self) -> list[dict[str, t.Any]]:
        data = []
        p = re.compile(r"(\d+),(-?\d+),(-?\d+),(\d+),(\d+),'(.*)',(\d+),'(.*)',(\d+),'(.*)',(\d+)")
        for bline in open(cache_name(self.name, ".sql"), "rb"):
            try:
                line = bline.decode("uso-8859-1")
            except Exception:
                line = bline.decode("utf8")
                line = line.replace("INSERT INTO `x_world` VALUES (", "")
                line = line.replace(");", "")
            for subline in line.split("),("):
                m = p.match(subline)
                if m:
                    # data.append("\t".join([safe(x) for x in m.groups()]))
                    keys = "lochash, x, y, race, town_id, town_name, owner_id, owner_name, guild_id, guild_name, population".split(
                        ", "
                    )
                    vals = m.groups()
                    data.append(dict(zip(keys, vals)))
        return data

    ###################################################################
    # old school gz
    def update_text_gz(self) -> bool:
        self.set_status("downloading sql.gz...")
        path = cache_name(self.name, ".sql.gz")

        if self.fetch("http://%s/map.sql.gz" % self.name, path):
            if os.stat(path).st_size < 64 * 1024:
                self.set_status("map.sql.gz is short")
            else:
                self.set_status("map.sql.gz downloaded")
            return True

        self.set_status("map.sql.gz missing")
        return False

    def _create_data_from_text_gz(self) -> str:
        data = []
        p = re.compile(r"(\d+),(-?\d+),(-?\d+),(\d+),(\d+),'(.*)',(\d+),'(.*)',(\d+),'(.*)',(\d+)")
        for bline in gzip.open(cache_name(self.name, ".sql.gz")):
            try:
                line = bline.decode("uso-8859-1")
            except Exception:
                line = bline.decode("utf8")
                line = line.replace("INSERT INTO `x_world` VALUES (", "")
                line = line.replace(");", "")
            for subline in line.split("),("):
                m = p.match(subline)
                if m:
                    data.append("\t".join([safe(x) for x in m.groups()]))
        return "\n".join(data)

    ###################################################################
    # new school
    def get_key(self) -> str:
        url = "http://%s/api/external.php" % self.name
        if not (self.privateApiKey and self.publicSiteKey):
            self.set_status("generating key...")
            res = requests.get(
                url,
                params={
                    "action": "requestApiKey",
                    "email": "shish+travmap@shishnet.org",
                    "siteName": "TravMap",
                    "siteUrl": "https://travmap.shishnet.org/",
                    "public": "true",
                },
            )
            if res.status_code != 200:
                raise Exception("Error %d while requesting API key" % (res.status_code,))
            with closing(conn.cursor()) as cur:
                j = res.json()["response"]
                cur.execute(
                    """
                    UPDATE servers
                    SET privateApiKey=%s, publicSiteKey=%s, mapfile=%s
                    WHERE name=%s
                    """,
                    (j["privateApiKey"], j["publicSiteKey"], "json", self.name),
                )
                conn.commit()
                return j["privateApiKey"]
        return self.privateApiKey

    def update_json(self) -> bool:
        self.set_status("downloading json...")
        path = cache_name(self.name, ".json")

        params = {
            "action": "getMapData",
            "privateApiKey": self.get_key(),
        }
        if self.fetch("http://%s/api/external.php" % self.name, path, params):
            if os.stat(path).st_size < 64 * 1024:
                self.set_status("map.json is short")
            else:
                self.set_status("map.json downloaded")
            return True

        self.set_status("map.json missing")
        return False

    def _create_data_from_json(self) -> str:
        data = []
        j = json.load(open(cache_name(self.name, ".json")))
        if j["error"]:
            raise Exception(j["error"]["message"])
        r = j["response"]
        alliances = {0: {"nameShort": ""}}
        for alliance in r["alliances"]:
            alliances[int(alliance["allianceId"])] = alliance
        for player in r["players"]:
            for village in player["villages"]:
                data.append(
                    "\t".join(
                        [
                            safe(x)
                            for x in [
                                village["villageId"],
                                village["x"],
                                village["y"],
                                "0",
                                village["villageId"],
                                village["name"],
                                player["playerId"],
                                player["name"],
                                player["allianceId"],
                                alliances[int(player["allianceId"])]["nameShort"],
                                village["population"],
                            ]
                        ]
                    )
                )
        return "\n".join(data)


def config_to_environ() -> None:
    try:
        for line in open("/utils/config.sh"):
            k, _, v = line.strip().partition("=")
            os.environ[k] = v
    except Exception as e:
        print("Failed to load config.sh: " + str(e))


def clear_cache() -> None:
    set_global_status("Cleaning cache")
    for fn in glob(os.path.join(os.environ["CACHE"], "*.txt")):
        os.unlink(fn)


def update_servers(servers):
    with closing(conn.cursor()) as cur:
        cur.execute(
            """
            SELECT """
            + ", ".join(fields)
            + """
            FROM servers
            WHERE visible=True
            ORDER BY country, num
        """
        )
        for row in cur.fetchall():
            s = Server(*row)
            if s.name in servers or not servers:
                try:
                    s.update()
                except Exception as e:
                    s.set_status("Error: " + str(e))
    with closing(conn.cursor()) as cur:
        cur.execute("DELETE FROM servers WHERE (julianday('now') - julianday(updated)) > 14")


def connect() -> None:
    global conn
    conn = sqlite3.connect(os.environ["SQL_DB"])


def main(argv: list[str]) -> int:
    try:
        config_to_environ()
        set_global_status("Update starting")
        connect()
        # mkdirs()
        update_servers(argv[1:])
        clear_cache()
        set_global_status("Update complete")
        return 0
    except KeyboardInterrupt:
        set_global_status("Interrupted")
        return 1


if __name__ == "__main__":
    sys.exit(main(sys.argv))
