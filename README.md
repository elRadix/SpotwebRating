# SpotwebRating
##Rating script / addon for Spotweb
Adds a rating to a spot, ie "NIGHTCRAWLER (2014) x264 1080p Bluray DD5.1 + DTS NLSubs -Q o Q-" will become "NIGHTCRAWLER (2014) x264 1080p Bluray DD5.1 + DTS NLSubs -Q o Q- **[8.1]**" and will have a spotrating of 10.

Based on code from Tweakers MR_Blobby and Satom
http://gathering.tweakers.net/forum/list_message/43647658#43647658
http://gathering.tweakers.net/forum/list_message/43652465#43652465


### Usage
- Download both the addrating.php and imdb.php files
- Change the parameters in the addrating.php file
- Run it, that's all

### Example shell script
This you can use in a cron (don't forget to make your file executable: chmod a+x addrating.sh)

```
#!/bin/bash
cd /var/www/path/to/your/spotweb
/usr/bin/php addrating.php > /var/log/spotwebrating
```
