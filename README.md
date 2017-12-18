# Albion Discord KillBoard
Send AlbionOnline events to Discord

#### Before starting
Make sure you already have PHP and Composer ready to use

You have the following files :
- AlbionKillScan.php
- composer.json
- lastEvent.txt (with write permissions)
- /images folder (with write permissions)

#### Configuration
Update some variables with your own data
```php
//Line 17 : your discord webhook URL
$webhookURL = 'https://discordapp.com/api/webhooks/SET_YOUR_OWN_DISCORD_WEBHOOK_URL'; 

//Line 23 : your alliance TAG
$alliance = 'RIXE';

//Line 141 : your server URL
$embed->image('http://YOUR_SERVER_URL.com/albion' . $image);
```

#### Installation
Run the following inside the project folder to install dependencies into /vendor
```sh
composer install
```

Then you need to add execution of the script inside your crontab
```sh
crontab -e
```
Add a new line at the end with your own path so the script runs automatically every minutes
```sh
* * * * * php /var/www/albion/AlbionKillScan.php
```