import os
import sys

"""
"""
def sortedIter(fname, args='-k1,1 -t "	"'):
    for l in os.popen("sort %s %s" % (args,fname)):
        yield l

"""
tsvIter
    Iterates over a tab-separated file, yielding an array of fields for each row.

    src	(String or iterable) If src is a string, then it is a file name - the file is opened 
    	read, and closed after the last row is yielded. Otherwise, src is an iterable (such
	as an already open file) - it is simply iterated, but NOT closed at the end (that's
	the caller's job, if appropriate)
    sep (String) The separator character. TAB by default.
    removeNL (Boolean) If true, removes the last character of each line. Default is true.
Yields:
    array of strings
"""
def tsvIter(src, sep='\t', removeNL=True):
    if src == "-":
        fd = src = sys.stdin
    elif type(src) is str:
        fd = open(src, 'r')
    else:
        fd = src

    for line in fd:
        flds = line.split(sep)
        if removeNL:
            flds[-1] = flds[-1][:-1]
        yield flds

    if not fd is src:
        fd.close()

"""
keyedIter
    Computes a key for each item in the source iterable, yield (key,item)
    If checkSorted is True, throws an error if keys are not sorted.
"""
def keyedIter(tsv,key,checkSorted=True):
    lastk = None
    for r in tsv:
        k = r[key]
        if lastk is not None and k < lastk:
            raise RuntimeError("Keys are not sorted! %s %s" % (lastk, k))
        lastk = k
        yield (k, r)

"""
mergeIter
    Iterates over two sources in parallel. Returns pairs of rows that
    are matched on key.
"""
def mergeIter(src1, src2, key1=0, key2=0, all=False):

    tsv1 = keyedIter(tsvIter(src1),key1)
    tsv2 = keyedIter(tsvIter(src2),key2)

    try:
        k1,r1 = next(tsv1)
        k2,r2 = next(tsv2)
        while True:
            if k1 < k2:
                if all:
                    yield (k1, r1, None)
                k1,r1 = next(tsv1)
            elif k2 < k1:
                if all:
                    yield (k2, None, r2)
                k2,r2 = next(tsv2)
            else:
                yield (k1,r1,r2)
                k1,r1 = next(tsv1)
                k2,r2 = next(tsv2)
    except StopIteration:
        if all:
            for k,r in tsv1:
                yield (k, r, None)
            for k,r in tsv2:
                yield (k, None, r)

