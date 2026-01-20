
import sys
import re
import os
import time
import os.path as opath
from configparser import ConfigParser
import optparse
import xml.dom.minidom
from lib import snapshot
from lib import iterator
import itertools

SNAPSHOTFILE_re = re.compile(r'iphone_app_snapshot_([^_-]*)-(\d\d-\d\d-\d\d\d\d)\.')

USAGE='''
This is a command line tool to compute diffs between snapshot files for the iPhone app.
To see options:
  %prog -h
There are two modes: basic and auto. In basic mode, you supply the names of the two snapshot 
files you want to diff (use options -p and -c), and a name for the output file (default is stdout). 
The -t argument is required in basic mode.
Usage:
  %prog  [-g config] -p oldSnapshot -c currentSnapshot -t snapshotType [-o diffFile]
In auto mode, you supply directories and an age (-d and -a)
The tool locates the appropriate snapshot files and produces a diff for the last N days from 
the current snapshot. 
Usage:
  %prog [-g config] -d datadir [-d ...] -a age -t snapshotType [-t ...] [-o outputdir]
The script collects all snapshot files that it finds in those dirs, groups them by type 
(genes, mp, omim) and sorts by age. The diff is computed between the newest snapshot and 
the oldest snapshot not more than age days old. Note that age is computed from the file's 
modification date, NOT from anything embedded in the file's name.
If -t is specified, only the specified type(s) is/are processed. Otherwise, all types are processed.
'''
class IpaDiff:
    #-------------------------------------------
    def __init__(self, argv=None):
        self.myDir = opath.abspath(opath.dirname(__file__))
        self.cwd = os.getcwd()
        self.argv = argv and argv or sys.argv
        self.op = None
        self.opts = None
        self.args = None
        self.cp = None
        self.outputs = {}
        self.initCmdLineParser()
        self.processCmdLine()
        self.processConfig()
        self.genFilePairs()

    #-------------------------------------------
        
    def initCmdLineParser(self):
        self.op = optparse.OptionParser(usage=USAGE)
        self.op.add_option(
            "-g","--config",
            dest="configs",
            default=[opath.join(self.myDir, 'config')], 
            action="append",
            metavar="FILE",
            help="Config file or directory. If directory, reads all .cfg files in it. Repeatable. Default=%default.")
        self.op.add_option(
            "-t",
            dest="snaptype",
            default=[],
            action="append",
            metavar="TYPE", 
            choices=["genes","mp","omim"],
            help="Snapshot type. One of: genes mp omim. Required in basic mode. Defaults to all types in auto mode.")
        self.op.add_option(
            "-s","--sort",
            dest="sortneeded",
            default=False,
            action="store_true",
            help="Causes the input files to be sorted before diff'ing. " + \
                 "Needed if files are not already sorted by ID column. Default=%default.")
        self.op.add_option(
            "--limit",
            dest="limit",
            type="int",
            default=0,
            action="store",
            help="If >0, limits the number of diffs reported. For debugging. Default=%default.")

        # Basic mode.
        self.op.add_option(
            "-p","--previous",
            dest="file1",
            default=None,
            metavar="FILE", 
            help="Previous snapshot file. Basic mode.")
        self.op.add_option(
            "-c","--current",
            dest="file2",
            default=None,
            metavar="FILE",
            help="Current snapshot file. Basic mode.")

        # Auto mode.
        self.op.add_option(
            "-d","--data",
            dest="dataDirs",
            metavar="DIR",
            #default=[self.cwd], 
            default=[], 
            action="append",
            help="Data file directory. Auto mode. Repeatable. Default=current dir %default.") 
        self.op.add_option(
            "-a","--age",
            dest="age",
            default=30,
            metavar="N",
            type="int",
            help="Age to look back from current snapshot. Auto mode. Default=%default.")

        self.op.add_option(
            "-o","--output",
            dest="ofile",
            default=None,
            metavar="FILE",
            help="Output file or directory. Default=standard out.")

    #-------------------------------------------
    def processCmdLine(self):
        #
        self.opts, self.args = self.op.parse_args(self.argv)

        # If a config dir was specified, remove the default.
        if len(self.opts.configs) > 1:
            del self.opts.configs[0:1]

        # Find all the actual config files.
        tmp = []
        for c in self.opts.configs:
            if opath.isfile(c):
                tmp.append(c)
            elif opath.isdir(c):
                cfgs = filter( lambda f: f.endswith(".cfg"), os.listdir(c) )
                tmp += map(lambda f:opath.join(c,f), cfgs )
            else:
                self.op.error("Bad config: no such file or directory: "+c)
        self.opts.configs = map( lambda f: opath.abspath(f), tmp )

    #-------------------------------------------
    def processConfig(self):
        self.cp = ConfigParser()
        self.cp.read(self.opts.configs)
    
    #-------------------------------------------
    def addFilePair(self, typ, f1, f2, df=None):
        self.opts.filePairs.append({
        'type':typ, 
        'cfile':f2, 
        'pfile':f1,
        'dfile':df,
        })

    #-------------------------------------------
    '''
    genFilePairs: builds list of snapshot pairs to be processed, based on args
    Initializes, file pairs array. Each item is actually a 4-tuple:
        (type, prev snapshot file, current snapshot file, output file)
    '''
    def genFilePairs(self):
        self.opts.filePairs = []
        if len(self.opts.dataDirs)>0 and (self.opts.file1 or self.opts.file2):
            self.op.error("Either specify -p/-c or -d, but not both.")
        elif self.opts.file1 and self.opts.file2:
            # basic mode
            if len(self.opts.snaptype) != 1:
                self.op.error("Please specify exactly one snapshot type.")
            if self.opts.ofile and opath.isdir(self.opts.ofile):
                self.op.error("Output file is a directory:" + self.opts.ofile)
            self.addFilePair( self.opts.snaptype[0], self.opts.file1, self.opts.file2, self.opts.ofile )
        elif len(self.opts.dataDirs)>0:
            # auto mode
            snfs = self.getSnapshotFiles(self.opts.dataDirs)
            if len(snfs) == 0:
                self.op.error("No snapshot files found.")
            typs = len(self.opts.snaptype) and self.opts.snaptype or sorted(snfs.keys())
            for typ in typs:
                self.genFilePair(typ, snfs[typ], days=self.opts.age)
        else:
            self.op.error("Specify either a pair of files or a data directory.")

    #-------------------------------------------
    '''
    For a given type and list of snapshots of that type, finds a pair to use for
    doing a diff. Specify the "current" snapshot as in index into the list (by default,
    i==0, the most recent) and an age in days (default=30). The "previous" snapshot
    is then located, ie, the oldest one no more than age days older than the current.
    The pair are added to our work list.
    '''
    def genFilePair(self, typ, files, i=0, days=30):
        if len(files) < 2:
            return
        iAge,iDt, iFile = files[i]
        j = -1
        # find largest j where age diff <= days arg.
        for jAge,jDt,jFile in files:
            if jAge-iAge <= days:
                j += 1
        if j==-1 or j==i:
            return

        jAge, jDt, jFile = files[j]
        df = None
        if self.opts.ofile:
            if opath.isdir(self.opts.ofile):
                # generate output file name
                df = opath.abspath(opath.join(self.opts.ofile, 
                     "iphone_app_diffs_%s_%s_%s.xml"%(typ,jDt,iDt)))
            else:
                df = self.opts.ofile
        self.addFilePair(typ, jFile, iFile, df)
        

    #-------------------------------------------
    '''
    Returns all the snapshot files found in the given list of dirs.
    Dirs are NOT searched recursively.
    Snapshot files are detected by matching name pattern (which is currently hardcoded).
    A snapshot file's name contains a type and a date. The date is ignored except for matching.
    (The file's modification date is what counts.)
    Returns a dict keyed by type. For each type, the list of snapshot files of that type, sorted
    by file modification date, newest to oldest.
    '''
    def getSnapshotFiles(self, dirs):
        dfmt = '%m-%d-%Y'
        rx = SNAPSHOTFILE_re
        files = {}
        fdate = time.time()
        for dir in dirs:
            for f in os.listdir(dir):
                m = rx.search(f)
                if m is None:
                    continue
                typ = m.group(1) # genes, mp, or omim
                ndt = m.group(2)
                ffn = os.path.abspath(os.path.join(dir,f)) # full file name
                statinfo = os.stat(ffn)
                #fdt = time.mktime(time.strptime(m.group(2),dfmt))
                fdt = statinfo.st_mtime
                ageDays = round((fdate-fdt) / (24*60*60))
                files.setdefault(typ,[]).append( (ageDays, ndt, ffn) )
        for typ in files:
            files[typ].sort()
        return files

    #-------------------------------------------
    '''
    do1pair: find diffs between one pair of snapshot files.
    tp (string) type (genes/mp/omim)
    pf (string) previous file
    cf (string) current file
    df (string) diff file
    '''
    def do1pair(self, tp, pf, cf, df=None):
        # create a snapshot object of the right type
        try:
            self.ss = snapshot.Snapshot(self.cp, tp)
        except Exception as e:
            self.op.error(e)

        ofd = self.openOutput(df)

        ofd.write('''\n\n<!--\n\tCURR:%s\n\tPREV:%s\n-->\n\n''' % (cf, pf))

        if self.opts.sortneeded:
            pf = iterator.sortedIter(pf)
            cf = iterator.sortedIter(cf)
        
        diter = self.ss.diffs(pf, cf)
        if self.opts.limit:
            diter = itertools.islice(diter, self.opts.limit)
        # loop over the diffs and write them out
        for d in diter:
            ofd.write( self.cp.get( 'RSS','itemTemplate',True) % d )
            ofd.write('\n')


    #-------------------------------------------
    def openOutput(self, oname):
        f = self.outputs.get(oname,None)
        if f:
            return f
        if oname is None or oname == '-':
            f = sys.stdout
        else:
            f = open(oname,'w')
        f.write(self.cp.get('RSS','header'))
        self.outputs[oname] = f
        return f

    #-------------------------------------------
    def closeOutput(self, oname):
        f = self.outputs[oname]
        f.write(self.cp.get('RSS','footer'))
        f.close()
        del self.outputs[oname]
        return True

    #-------------------------------------------
    '''
    go: Processes the file pairs.
    '''
    def go(self):
        for fp in self.opts.filePairs:
            self.do1pair(fp['type'], fp['pfile'], fp['cfile'], fp['dfile'])
        for df in self.outputs.keys():
            self.closeOutput(df)

#-------------------------------------------
#-------------------------------------------

#
if __name__=="__main__":
    IpaDiff(sys.argv).go()
