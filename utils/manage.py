#!/usr/bin/env python3

#
# update.py (c) Shish 2015
#

import re
import os
import sys
import argparse
import requests
import sqlite3
from contextlib import closing
from datetime import datetime
import typing as t


def set_global_status(text: str) -> None:
    text += " at %s" % str(datetime.now())[:16]
    print(f"[Global] {text}")
    open("../htdocs/status.txt", "w").write(text)


@t.final
class Server:
    def __init__(self, conn: sqlite3.Connection, name: str) -> None:
        self.conn = conn
        self.name = name

    @property
    def dbname(self) -> str:
        return self.name.replace("-", "_").replace(".", "_")

    def set_status(self, text: str) -> None:
        print(f"[{self.name}] {text}")
        with closing(self.conn.cursor()) as cur:
            cur.execute("UPDATE servers SET status=? WHERE name=?", (text[:250], self.name))
            self.conn.commit()

    def update(self) -> None:
        set_global_status("Updating %s" % self.name)
        self.set_status("downloading sql...")
        text = requests.get(f"http://{self.name}/map.sql").text
        self.set_status("map.sql downloaded")
        data = self._create_data_from_text(text)
        self.set_status("map.sql parsed")
        self.load_data(data)
        self.set_status("ok")

    def _create_data_from_text(self, text: str) -> list[dict[str, t.Any]]:
        """
        >>> from pprint import pprint
        >>> x = '''
        ... INSERT INTO `x_world` VALUES (50,-151,200,1,53195,'N ♣♥♠♦ RoMa PazZini',950,'DigBoy',45,'PDWNS',498,NULL,FALSE,NULL,NULL,NULL);
        ... INSERT INTO `x_world` VALUES (143,-58,200,2,44039,'3 - Not Urspot',239,'Uranus',45,'PDWNS',921,NULL,FALSE,NULL,NULL,NULL);
        ... INSERT INTO `x_world` VALUES (227,26,200,2,44501,'قرية جديدة',1967,'Mhmd',28,'Rebel',672,NULL,FALSE,NULL,NULL,NULL);
        ... '''
        >>> pprint(Server(None, "test")._create_data_from_text(x))
        [{'guild_id': '45',
          'guild_name': 'PDWNS',
          'lochash': '50',
          'owner_id': '950',
          'owner_name': 'DigBoy',
          'population': '498',
          'race': '1',
          'town_id': '53195',
          'town_name': 'N ♣♥♠♦ RoMa PazZini',
          'x': '-151',
          'y': '200'},
         {'guild_id': '45',
          'guild_name': 'PDWNS',
          'lochash': '143',
          'owner_id': '239',
          'owner_name': 'Uranus',
          'population': '921',
          'race': '2',
          'town_id': '44039',
          'town_name': '3 - Not Urspot',
          'x': '-58',
          'y': '200'},
         {'guild_id': '28',
          'guild_name': 'Rebel',
          'lochash': '227',
          'owner_id': '1967',
          'owner_name': 'Mhmd',
          'population': '672',
          'race': '2',
          'town_id': '44501',
          'town_name': 'قرية جديدة',
          'x': '26',
          'y': '200'}]
        """
        data: list[dict[str, t.Any]] = []

        p = re.compile(
            r"INSERT INTO `x_world` VALUES \("
            r"(?P<lochash>\d+),"
            r"(?P<x>-?\d+),"
            r"(?P<y>-?\d+),"
            r"(?P<race>\d+),"
            r"(?P<town_id>\d+),'(?P<town_name>.*)',"
            r"(?P<owner_id>\d+),'(?P<owner_name>.*)',"
            r"(?P<guild_id>\d+),'(?P<guild_name>.*)',"
            r"(?P<population>\d+)"
            r".*"
            r"\);"
        )
        for line in text.splitlines():
            if m := p.match(line):
                data.append(m.groupdict())
        return data

    def load_data(self, data: list[dict[str, t.Any]]) -> None:
        cur = self.conn.cursor()

        cur.execute(f"DROP TABLE IF EXISTS {self.dbname}")
        cur.execute(
            f"""
            CREATE TABLE {self.dbname}(
                lochash INTEGER NOT NULL, x SMALLINT NOT NULL, y SMALLINT NOT NULL, race SMALLINT NOT NULL,
                town_id  INTEGER NOT NULL, town_name  VARCHAR(128) NOT NULL, -- 20
                owner_id INTEGER NOT NULL, owner_name VARCHAR(128) NOT NULL, -- 16
                guild_id INTEGER NOT NULL, guild_name VARCHAR(64) NOT NULL,  -- 8
                population SMALLINT NOT NULL
            )
        """
        )

        cur.executemany(
            f"INSERT INTO {self.dbname} VALUES (:lochash, :x, :y, :race, :town_id, :town_name, :owner_id, :owner_name, :guild_id, :guild_name, :population)",
            data,
        )

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

        self.conn.commit()


###################################################################
# Command handlers


def cmd_add(conn: sqlite3.Connection, server_name: str) -> None:
    print(f"Adding {server_name} to database")

    with closing(conn.cursor()) as cur:
        cur.execute("INSERT INTO servers(name) VALUES(?)", (server_name,))
        conn.commit()

    cmd_update(conn, [server_name])


def cmd_remove(conn: sqlite3.Connection, server_name: str) -> None:
    print(f"Removing {server_name} from database")

    server = Server(conn, server_name)
    with closing(conn.cursor()) as cur:
        cur.execute("DELETE FROM servers WHERE name=?", (server.name,))
        cur.execute(f"DROP TABLE IF EXISTS {server.dbname}")
        conn.commit()

    print(f"Server {server.name} removed")


def cmd_update(conn: sqlite3.Connection, servers: list[str]) -> None:
    set_global_status("Update starting")

    with closing(conn.cursor()) as cur:
        for (name,) in cur.execute("SELECT name FROM servers").fetchall():
            s = Server(conn, name)
            if not servers or s.name in servers:
                try:
                    s.update()
                except Exception as e:
                    print(e)
                    s.set_status("error")

    # Delete old servers
    with closing(conn.cursor()) as cur:
        cur.execute("DELETE FROM servers WHERE (julianday('now') - julianday(updated)) > 14")
        conn.commit()

    # Drop any tables which don't have a matching server
    with closing(conn.cursor()) as cur:
        protected_tables = {"servers"}

        valid_servers = {row[0] for row in cur.execute("SELECT name FROM servers").fetchall()}

        for (tablename,) in cur.execute("SELECT name FROM sqlite_master WHERE type='table'").fetchall():
            if tablename not in protected_tables and tablename not in valid_servers:
                cur.execute(f"DROP TABLE {tablename}")

        conn.commit()

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
        os.chdir(os.path.dirname(os.path.abspath(__file__)))
        db = sqlite3.connect("../data/travmap.sqlite")
        if args.func == cmd_add:
            cmd_add(db, args.server)
        if args.func == cmd_remove:
            cmd_remove(db, args.server)
        if args.func == cmd_update:
            cmd_update(db, args.servers)
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
