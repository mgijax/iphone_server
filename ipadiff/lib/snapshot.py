
import time
from .iterator import mergeIter
from .labeler import Labeler
from .comparator import Comparator
from xml.sax.saxutils import escape as xmlEscape
import logging
try:
    import json
except:
    import simplejson as json

class Snapshot:
    def __init__(self, cp, snaptype):
        self.sec = 'snapshot.'+snaptype
        self.cp = cp
        self.labeler = Labeler(cp)
        self.cmprs = []
        for col,cmpn in json.loads(self.cp.get(self.sec, "comparators")):
            cmpsn = "comparator.%s"%cmpn
            self.cmprs.append( Comparator(col-1, cp, cmpsn, self.labeler) )

        self.pubDate = time.strftime(self.cp.get(self.sec, 'dateFormat', raw=True))
        self.typ = self.cp.get(self.sec,'type')
        self.stype = self.cp.get(self.sec,'stype')
        self.sformat = self.cp.get(self.sec,'sformat',raw=True)

    def convertZeros(self, r):
        for i in range(len(r)):
            if r[i] == '0':
                r[i] = ''
        return r

    # Compares two files, f1 and f2, and reports any differences
    # Written as an iterator that yields a series of dicts.
    # Each one is a difference record.
    def diffs(self, f1, f2):
        # foreach pair of records
        for idVal, r1, r2 in mergeIter(f1, f2, all=True):
            r1 = r1 and self.convertZeros(r1) or None
            r2 = r2 and self.convertZeros(r2) or None
            # try each comparison
            for cmpr in self.cmprs:
                try:
                    # report any diffs
                    for d in cmpr.diffs(r1, r2):
                        d['id'] = idVal
                        d['type'] = self.typ
                        if d.get('subject',None) is None:
                            d['subject'] = self.labeler.get(self.stype, idVal, self.sformat)
                            if d['subject'] is None:
                                logging.warn("No label found for: "+idVal)
                                d['subject'] = '???'
                        d['label'] = xmlEscape(d['subject']) + ' [' + idVal + ']'
                        d['updateMessage'] = xmlEscape(d['updateMessage'] % d)
                        d['pubDate'] = self.pubDate
                        yield d
                except:
                    print("ERROR!")
                    print("comparator=",str(cmpr))
                    print("r1=",r1)
                    print("r2=",r2)
                    raise

