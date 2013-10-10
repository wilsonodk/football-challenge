#!/usr/bin/env bash

# Need to run the update.sql
echo "Running update.sql"
RESULTS=`my_sql < update.sql`
echo "Results from update.sql: $RESULTS"
