#!/usr/bin/env python3

#
# update.py (c) Shish 2015
#

import re
import os
import sys
import json
import gzip
import argparse
import requests
import sqlite3
from contextlib import closing
from glob import glob
from time import time
from datetime import datetime
import typing as t


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

    @property
    def dbname(self) -> str:
        return self.name.replace("-", "_").replace(".", "_")

    def update(self) -> None:
        set_global_status("Updating %s" % self.name)
        if self.update_text():
            self.load_data(self._create_data_from_text())
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
            # fp = StringIO(data)
            # cur.copy_from(fp, self.dbname, columns="lochash, x, y, race, town_id, town_name, owner_id, owner_name, guild_id, guild_name, population".split(", "))
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


def get_config() -> None:
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


def connect() -> None:
    global conn
    conn = sqlite3.connect(os.environ["SQL_DB"])


###################################################################
# Command handlers


def cmd_add(args) -> None:
    """Add a new server to the database"""
    print(f"Adding {args.server} to database")

    with closing(conn.cursor()) as cur:
        cur.execute(
            "INSERT INTO servers(name) VALUES(?)",
            (args.server,),
        )
        conn.commit()

    print("Loading data")
    cmd_update(args)


def cmd_remove(args) -> None:
    """Remove a server from the database"""
    server_name = args.server
    dbname = server_name.replace("-", "_").replace(".", "_")

    print(f"Removing {server_name} from database")

    with closing(conn.cursor()) as cur:
        cur.execute("DELETE FROM servers WHERE name=?", (server_name,))
        try:
            cur.execute(f"DROP TABLE {dbname}")
        except Exception:
            pass
        conn.commit()

    # Clear cache
    cache_servers = os.path.join(os.path.dirname(os.environ["CACHE"]), "cache", "servers.txt")
    if os.path.exists(cache_servers):
        os.unlink(cache_servers)

    print(f"Server {server_name} removed")


def cmd_update(args) -> None:
    set_global_status("Update starting")

    with closing(conn.cursor()) as cur:
        cur.execute("SELECT name FROM servers")
        for row in cur.fetchall():
            s = Server(*row)
            if not args.servers or s.name in args.servers:
                try:
                    s.update()
                except Exception as e:
                    s.set_status("Error: " + str(e))

    # Delete old servers
    with closing(conn.cursor()) as cur:
        cur.execute("DELETE FROM servers WHERE (julianday('now') - julianday(updated)) > 14")
        conn.commit()

    # Drop any tables which don't have a matching server
    with closing(conn.cursor()) as cur:
        protected_tables = {"servers"}

        cur.execute("SELECT name FROM servers")
        valid_servers = {row[0] for row in cur.fetchall()}

        cur.execute("SELECT name FROM sqlite_master WHERE type='table'")
        for row in cur.fetchall():
            tablename = row[0]
            if tablename not in protected_tables and tablename not in valid_servers:
                try:
                    cur.execute(f"DROP TABLE {tablename}")
                except Exception:
                    pass

        conn.commit()

    clear_cache()
    set_global_status("Update complete")


def main() -> int:
    parser = argparse.ArgumentParser(description="Manage TravMap servers")
    subparsers = parser.add_subparsers(dest="command", help="Command to execute")

    parser_add = subparsers.add_parser("add", help="Add a new server")
    parser_add.add_argument("server", help="Server name (e.g., ts1.x3.europe.travian.com)")
    parser_add.set_defaults(func=cmd_add)

    parser_remove = subparsers.add_parser("remove", help="Remove a server")
    parser_remove.add_argument("server", help="Server name to remove")
    parser_remove.set_defaults(func=cmd_remove)

    parser_update = subparsers.add_parser("update", help="Update server data")
    parser_update.add_argument("servers", nargs="*", help="Servers to update (empty for all)")
    parser_update.set_defaults(func=cmd_update)

    args = parser.parse_args()

    if not args.command:
        parser.print_help()
        return 1

    try:
        get_config()
        connect()
        args.func(args)
        return 0
    except KeyboardInterrupt:
        set_global_status("Interrupted")
        return 1
    except Exception as e:
        print(f"Error: {e}")
        import traceback

        traceback.print_exc()
        return 1


if __name__ == "__main__":
    sys.exit(main())
