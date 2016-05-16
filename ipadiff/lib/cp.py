# getConfigSection
# Here's a handy little doodad. Takes a ConfigParser and a section name, and returns
# a callable that gives you easy section-specific view of the config. This is often
# just what you want, and is more convenient and concise than carrying around the
# ConfigParser plus a name, which you then have to plug in on every call. 
#           scfg = self.getConfigSection(self.cp, "FooSection")
#           print scfg("varA"), scfg("Xyz")
#           print scfg.options()
# 
import ConfigParser

def getConfigSection(cp, sname):
    f = lambda *a : cp.get(sname, *a)
    f._configParser = cp
    f._sectionName = sname
    f.get = f
    f.has_option = lambda *a : cp.has_option(sname, *a)
    f.options    = lambda *a : cp.options(sname, *a)
    f.items      = lambda *a : cp.items(sname, *a)
    return f

setattr( ConfigParser.ConfigParser, 'getSection', getConfigSection )

if __name__=="__main__":
    import sys
    cp = ConfigParser.ConfigParser()
    cp.read(sys.argv[1])
    g1 = cp.getSection('query.gene')
    g2 = cp.getSection('query.genotype')
    print g1("query",True)
    print g2("query",True)



