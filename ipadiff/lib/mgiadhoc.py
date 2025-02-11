#!/opt/local/bin/python
#
# mgiadhoc.py
#
# Simple library for querying the MGI ad hoc database.
#
import os
import sys
import types

import psycopg2
import psycopg2.extras

# Default connection parameters
HOST="bhmgidb07lp.jax.org"
DATABASE="pub"
USER="mgd_public"
PASSWORD="mgdpub"

#
def connect(host=None,database=None, user=None, password=None):
    con = psycopg2.connect( host=host or HOST, database=database or DATABASE, user=user or USER, password=password or PASSWORD )
    return con

#
def sql(queries, parsers=None, connection=None):
    single = False
    if type(queries) not in [list, tuple]:
        queries = [queries]
        single=True
    if type(parsers) not in [list, tuple]:
        parsers = [parsers]*len(queries)

    if len(queries) != len(parsers):
        raise RuntimeError("Number of queries != number of parsers.")

    closeCon = False
    if connection is None:
        connection = connect()
        closeCon = True

    results = []
    for i,q in enumerate(queries):
        cur = connection.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
        cur.execute(q)
        p = parsers[i]
        if p == 'ignore':
            results.append(None)
        elif cur.statusmessage.startswith('SELECT'):
            if p is None:
                qr = []
                for r in cur:
                    qr.append( dict(r) )
                results.append(qr)
            else:
                for r in cur:
                    p( dict(r) )
                results.append(None)
        else:
            results.append(None)
        cur.close()

    if closeCon:
        connection.close()

    if single:
        return results[0]
    else:
        return results

#
def __test__():
    def p(r):
        print( r['symbol'], r['name'])

    qlist = [
      'select count(*) from mrk_marker',
      'select * from mrk_marker where _marker_key = 964'
      ]
    sql( qlist, ['ignore', p])

if __name__ == "__main__":
    __test__()

