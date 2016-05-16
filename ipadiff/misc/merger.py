

import sys
from xml.dom.minidom import parse

def mergeDiffs( diffFiles, outFile=None ):
    merged = None
    channelelt = None
    for fn in diffFiles:
	dom = parse(fn)
	if merged is None: 
	    merged = dom
	    channelelt = dom.getElementsByTagName('channel')[0]
	else:
	    for n in dom.getElementsByTagName('item'):
		channelelt.appendChild(n)

    mxml = merged.toxml()
    if outFile:
	ofd = open(outfile, 'w')
	ofd.write( mxml )
	ofd.close()

    return mxml

if __name__ == "__main__":
    print mergeDiffs( sys.argv[1:] )
