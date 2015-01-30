# SpotwebRating
Rating script / addon for Spotweb
Based on code from Tweakers MR_Blobby and Satom
http://gathering.tweakers.net/forum/list_message/43647658#43647658
http://gathering.tweakers.net/forum/list_message/43652465#43652465


Usage:
- Download both the addrating.php and imdb.php files
- Change the parameters in the addrating.php file
- Run it, that's all


Example shell script you can use in a cron (don't forget to make your file executable: chmod a+x addrating.sh)

#!/bin/bash
cd /var/www/path/to/your/spotweb
/usr/bin/php addrating.php > /var/log/spotwebrating
