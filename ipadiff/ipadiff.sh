PYTHON=/usr/local/bin/python
MV=mv
#
REPORTSDIR=/export/gondor/ftp/pub/reports
ARCHIVEDIR=${REPORTSDIR}/archive/iphone
IPHONEAPPDIR=/usr/local/mgi/proto/prototypes/iphone_app
TMPFILE=${IPHONEAPPDIR}/htdocs/.mgiRSS.xml
OUTPUTFILE=${IPHONEAPPDIR}/htdocs/mgiRSS.xml
#
$PYTHON ipadiff.py -s -d $REPORTSDIR -d $ARCHIVEDIR -o $TMPFILE 
if [ $? -eq 0 ]
then
    $MV -f $TMPFILE $OUTPUTFILE
fi
