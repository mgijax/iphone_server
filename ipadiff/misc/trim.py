import os
import types

# iphone-genes
#
#       Markers of all types
#       Markers of status official or interum (_marker_status_key in (1,3)
#
#       1:  MGI Marker ID
#       2:  Marker key
#       3:  Symbol
#       4:  Name
#       9:  MGI Ref ID (MGI:xxx|MGI:xxx|...)
#       12: MGI Allele ID (MGI:xxx|MGI:xxx|...)
#       15: GO ID (C group): (GO:xxxx|GO:xxxx|GO:xxxx...)
#       18: GO ID (F group): (GO:xxxx|GO:xxxx|GO:xxxx...)
#       21: GO ID (P group): (GO:xxxx|GO:xxxx|GO:xxxx...)
#       24: MP ID: (MP:xxxx|MP:xxxx|MP:xxxx...)
#       27: OMIM ID: (xxxx|xxxx|xxxx|...)
#       30: OMIM ID: (xxxx|xxxx|xxxx|...)
#
# iphone-mp
#
#       MP annotations
#
#       1: MP ID
#       2: MP Term
#       3: MP Definition
#       5: MGI Ref ID (MGI:xxx|MGI:xxx|MGI:xxx|...)
#       8: MGI Genotype ID (MGI:xxx|MGI:xxx|...)
#       11: MGI Marker ID (MGI:xxx|MGI:xxx|...)
#       14: MGI Allele ID (MGI:xxx|MGI:xxx|...)
#
# iphone-omim
#
#       1: OMIM ID
#       2: OMIM Definition
#       5: MGI Ref ID (MGI:xxx|MGI:xxx|MGI:xxx|...) //for (_annottype_key = 1005)
#       8: MGI Genotype ID (MGI:xxx|MGI:xxx|...)
#       11: MGI Marker ID (MGI:xxx|MGI:xxx|...)
#       14: MGI Allele ID (MGI:xxx|MGI:xxx|...)
#       17: MGI Ref ID (MGI:xxx|MGI:xxx|MGI:xxx|...) //for (_annottype_key = 1006)
#       20: Human EntrezGene ID (xxx|xxx|...)

columns = {
    '_genes-' : [1,2,3,4,9,12,15,18,21,24,27,30],
    '_mp-'    : [1,2,3,5,8,11,14],
    '_omim-'  : [1,2,5,8,11,14,17,20],
    }

def project(src):
    #-----
    def ff(val):
        if "||" in val:
	    if val.endswith("||"):
		val = val[:-2]
	    return "|".join(map(lambda x: x.split("|")[0], val.split("||")))
	else:
	    return val.strip()
    #-----
    for tp in columns.keys():
        if tp in src:
	    cols = columns[tp]
	    break
    else:
        raise RuntimeError("No type found for file: " + src)
    #
    fd = open(src,'r')
    for row in fd:
        iflds = row[:-1].split("\t")
	oflds = map(lambda i: ff(iflds[i-1]), cols)
	yield oflds
    fd.close()

if __name__=="__main__":
    import sys
    for r in project(sys.argv[1]):
        print "\t".join(r)
