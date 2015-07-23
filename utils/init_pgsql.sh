#!/bin/bash

#
# init_pgsql.sh (c) Shish 2009
#
# a script to initialise a pgsql database
#

. config.sh

echo "CREATE TABLE servers (
		name character varying(64) NOT NULL PRIMARY KEY,
		country character varying(32) NOT NULL,
		num integer NOT NULL,
		width integer NOT NULL DEFAULT 250,
		height integer NOT NULL DEFAULT 250,
		villages integer DEFAULT 0 NOT NULL,
		visible boolean DEFAULT true NOT NULL,
		updated timestamp with time zone DEFAULT '1970-01-01' NOT NULL,
		status character varying(255),
		owners integer DEFAULT 0 NOT NULL,
		guilds integer DEFAULT 0 NOT NULL,
		population integer DEFAULT 0 NOT NULL,
		UNIQUE (country, num)
);" | psql -q -U $SQL_USER $SQL_DB

