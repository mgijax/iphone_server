
try:
    #python 2.5+
    import json
except:
    #python 2.4
    import simplejson as json

import urllib
import types
import sys
import logging
import mgiadhoc as db

class Labeler:
    def __init__(self, cp):
        self.cp = cp
	self.cache = {}
	self.cxn = db.connect()
        
    def resetCache(self):
        self.cache = {}

    def get(self, typ, oid, format=True):
	k = (typ,oid,format)
	if self.cache.has_key(k):
	    return self.cache[k]


	sec = 'query.'+typ
	mgiq = self.cp.get(sec,'mgiquery', False, {'id':oid})
	r = db.sql(mgiq, None, connection=self.cxn)

	if len(r) == 0:
	    val = None
	elif format is None:
	    val = r
	elif format is False:
	    val = r[0]
	elif type(format) is types.StringType:
	    val = format % r[0]
	elif type(format) is types.FunctionType:
	    val = format(r[0])
	elif self.cp.has_option(sec,'format'):
	    val = self.cp.get(sec,'format',False,r[0])
	else:
	    val = r[0]

	if val is None:
	    logging.warn("No label found. %s"%str((typ,oid,format,mgiq)))
	    val = "???"
	self.cache[k] = val
	return val

if __name__ == "__main__":
    # self test
    import sys
    import ConfigParser
    cp = ConfigParser.ConfigParser()
    cp.read(sys.argv[1])
    l = Labeler(cp)
    print l.get('term','168601')
    print l.get('gene','MGI:96677')
    print l.get('allele','MGI:1857897')
    print l.get('genotype','MGI:3613478')
    print l.get('publication','MGI:5571472')
    print l.get('term','GO:0042592')
    print l.get('term','MP:0005376')
    #
    print l.get('gene','MGI:96677',True)
    print l.get('gene','MGI:96677',False)
    print l.get('gene','MGI:96677',lambda d: "Hi there: "+d['symbol'])
    print l.get('gene','MGI:96677',"Hi there: %(name)s")
