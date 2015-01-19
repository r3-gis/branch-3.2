# GisClient, branch 3.2

GisClient is a platform for publishing spatial data.

This branch is actively mantained.

#IN ANONIMO (READONLY)
git clone https://github.com/gisclient/branch-3.2.git author

#SE SVILUPPATORE
git clone git@github.com:gisclient/branch-3.2.git author

cd author/

chgrp -R apache fonts/

chgrp -R apache map/

chgrp -R apache config/debug/

chgrp -R apache public/services/tmp/

chgrp -R apache public/admin/export/

chgrp -R apache import/

chgrp -R apache files/

chgrp -R apache pixmap/

chgrp -R apache map/tmp/

chgrp apache public/services/print/tmp

chgrp -R apache tmp

chmod -R g+ws fonts/

chmod -R g+ws map/

chmod -R g+ws config/debug/

chmod -R g+ws public/services/tmp/

chmod -R g+ws public/admin/export/

chmod -R g+ws import/

chmod -R g+ws files/

chmod -R g+ws pixmap/

chmod g+ws map/tmp/

chmod g+ws  public/services/print/tmp

chmod -R g+ws tmp
