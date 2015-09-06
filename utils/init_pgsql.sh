#!/bin/bash

#
# init_pgsql.sh (c) Shish 2009
#
# a script to initialise a pgsql database
#

. config.sh

echo "CREATE TABLE servers (
	name VARCHAR(64) NOT NULL PRIMARY KEY,
	country VARCHAR(32) NOT NULL,
	num INTEGER NOT NULL,
	width INTEGER NOT NULL DEFAULT 250,
	height INTEGER NOT NULL DEFAULT 250,
	villages INTEGER DEFAULT 0 NOT NULL,
	visible BOOLEAN DEFAULT true NOT NULL,
	updated TIMESTAMP WITH TIME ZONE DEFAULT '1970-01-01' NOT NULL,
	status VARCHAR(255),
	owners INTEGER DEFAULT 0 NOT NULL,
	guilds INTEGER DEFAULT 0 NOT NULL,
	population INTEGER DEFAULT 0 NOT NULL,
	privateApiKey VARCHAR(255) DEFAULT NULL,
	publicSiteKey VARCHAR(255) DEFAULT NULL,
	UNIQUE (country, num)
);" | psql -q -U $SQL_USER $SQL_DB

