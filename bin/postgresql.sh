#!/bin/bash
USER=$1
PASS=$2
DB=$3

# Check if user exists if not create it
psql postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname='$USER'" | grep -q 1 || psql template1 -c "CREATE USER $USER WITH PASSWORD '$PASS'"

# Check database exists if not create it
db_exists=`psql template1 -t -c "SELECT COUNT(1) FROM pg_catalog.pg_database WHERE datname = '$DB'"`

if [ $db_exists -eq 0 ] ; then
    psql template1 -t -c "CREATE DATABASE $DB WITH OWNER $USER"
    psql template1 -c "GRANT ALL PRIVILEGES ON DATABASE $DB to $USER"
fi
