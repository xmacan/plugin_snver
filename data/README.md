# how to import prepared enterprise-numbers

1) import file ent.sql via mysql client:
mysql -u cacti_user -p cacti_db < ent.sql


# how to import actual enterprise-numbers

1) download from
http://www.iana.org/assignments/enterprise-numbers/enterprise-numbers

2) run prepare_sql.php

3) import file ent.sql via mysql:
mysql -u cacti_user -p cacti_db < ent.sql

