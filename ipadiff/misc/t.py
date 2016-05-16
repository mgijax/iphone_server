import sys
import os
import re
import time

def getSnapshotFiles(dirs):
    dfmt = '%m-%d-%Y'
    rx = re.compile(r'iphone_app_snapshot_([^_-]*)-(\d\d-\d\d-\d\d\d\d)\.')
    files = {}
    fdate = time.time()
    for dir in dirs:
	for f in os.listdir(dir):
	    m = rx.search(f)
	    if m is None:
		continue
	    typ = m.group(1) # genes, mp, or omim
	    ffn = os.path.join(dir,f) # full file name
	    statinfo = os.stat(ffn)
	    #fdt = time.mktime(time.strptime(m.group(2),dfmt))
	    fdt = statinfo.st_mtime
	    ageDays = (fdate-fdt) / (24*60*60)
	    files.setdefault(typ,[]).append( (ageDays, ffn) )
    for typ in files:
	files[typ].sort()
    return files

if __name__=="__main__":
    files = getSnapshotFiles(map(os.path.abspath,sys.argv[1:]))
    for typ in files.keys():
	print "["+typ+"]"
	for age, f in files[typ]:
	    print age, f
