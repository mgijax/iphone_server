

INT ="int"
STRING  ="string"
LIST    ="list"

PIPE    = "|"
DPIPE   = "||"


class AbstractComparator(object):
    def __init__(self, col, cfg, sec, labeler):
        self.col = col
        self.cfg = cfg
        self.sec = sec
        self.labeler = labeler
        self.updateType = cfg.get(self.sec, 'updateType')
        self.updateMessage = cfg.get(self.sec, 'updateMessage',raw=True) 
        self.updateLink = cfg.get(self.sec, 'link')

    def __str__(self):
        return str(self.__dict__)


class ExistenceComparator(AbstractComparator):
    pass

class CreatedComparator(ExistenceComparator):
    def diffs(self, r1, r2):
        if r2 and not r1:
            return [{
            'sid' : r2[0],
            'updateType' : self.updateType,
            'updateMessage' : self.updateMessage,
            'link' : self.updateLink + r2[0]
            }]
        else:
            return []

class DeletedComparator(ExistenceComparator):
    def diffs(self, r1, r2):
        if r1 and not r2:
            return [{
            'sid' : r1[0],
            'subject' : r1[2],
            'updateType' : self.updateType,
            'updateMessage' : self.updateMessage,
            'link' : self.updateLink + r1[0]
            }]
        else:
            return []

class AttributeComparator(AbstractComparator):
    def diffs(self, r1, r2):
        if not (r1 and r2):
            return []
        v1 = r1[self.col]
        v2 = r2[self.col]
        if v2 != v1:
            return [{
            'oldvalue' : v1,
            'newvalue' : v2,
            'updateType' : self.updateType,
            'updateMessage' : self.updateMessage,
            'link' : self.updateLink + r1[0]
            }]
        else:
            return []

class StringComparator(AttributeComparator):
    pass


class AssociationComparator(AbstractComparator):
    def __init__(self, col, cfg, sec, labeler):
        super(AssociationComparator,self).__init__(col, cfg, sec, labeler)
        self.otype = cfg.get(self.sec, 'otype')
        self.oformat = cfg.get(self.sec, 'oformat',raw=True)

    def parseAttr(self, v):
        pass
        
    def compareVals(self, v1, v2):
        pass

    def diffs(self, r1, r2):
        if not (r1 and r2):
            return []
        v1 = self.parseAttr(r1[self.col])
        v2 = self.parseAttr(r2[self.col])
        diffs = []
        for v in self.compareVals(v1,v2):
            diffs.append({
              'object' : self.labeler.get( self.otype, v, self.oformat),
              'updateType' : self.updateType,
              'updateMessage' : self.updateMessage,
              'link' : self.updateLink + v
              })
        return diffs

class IdListComparator(AssociationComparator):
    def parseAttr(self, v):
        return v and set(v.split(PIPE)) or set()
    def compareVals(self, v1, v2):
        return v2-v1 

def Comparator(col, cfg, sec, labeler):
    ft = cfg.get(sec, 'parse').lower()
    cls = {
     'string':StringComparator,
     'idlist':IdListComparator,
     'newobj' :CreatedComparator,
     'delobj' :DeletedComparator,
    }[ft]
    return cls(col,cfg,sec,labeler)
    
