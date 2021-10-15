# OvenMediaEngine Stream Site

Two PHP pages, one for authentication. The other is for displaying the streams.

## License
[AGPL](https://www.gnu.org/licenses/agpl-3.0.txt)

## Installation

Required Software:

MySQL (You should install this from your linux distro repository)

[OvenMediaEngine](https://github.com/AirenSoft/OvenMediaEngine)

[OvenPlayer](https://github.com/AirenSoft/OvenPlayer)

[normalize.css](https://github.com/necolas/normalize.css)

[awsm.css](https://github.com/igoradamenko/awsm.css)

[jQuery](https://jquery.com/download/)

## Installation

### OvenMediaEngine

Make a backup of Server.xml & Copy the basic Server.xml to its location.

Default location: /usr/share/ovenmediaengine/conf

### NGINX
```
server  {
        error_log /etc/nginx/logs/localhost_error.log error;
        listen 127.0.0.1:80;
        server_name localhost 127.0.0.1;
        root /var/www/localhost;

        location / { index ome-hook.php; }

        location ~ \.php$ {
                try_files $uri =404;
                fastcgi_pass unix:/run/php-fpm/php-fpm.sock;
                fastcgi_split_path_info ^(.+\.php)(/.+)$;
                include fastcgi.conf;
        }
}
```

### SQL

Import the SQL file into your database.

Add users.

```
`username` a display name
`stream_key` rtmp stream key
`private` determines if private_key is required
`private_key` if private=1 this key is required to view the stream
```

### ome-hook and index

Move ome-hook.php to /var/www/localhost (setup in the NGINX section)

Move index.php to any domain that's configured e.g. /var/www/video_example_com

# Configuration

## Server.xml

Change all instances of:

```
CHANGEME
video.example.com
```

Where CHANGEME is a secret key, and video.example.com is your DOMAIN.

## ome-hook.php
Adjust ome-hook.php config section to match your database and OME server application name. Make sure to change $omesecret variable to match the Server.xml secret (AccessToken, SecretKey)


```php
$host = "127.0.0.1";
$username = "CHANGEME";
$password = "CHANGEME";
$dbname = "CHANGEME";

$application = "app";
$omesecret = "CHANGEME";
```

## Index.php
Adjust the config section to match your database and OME server application name as well as pointing to the proper JS and CSS files.

```php
$OvenPlayer = "/files/ovenplayer/ovenplayer.js";
$jQuery = "/files/jquery/jquery.min.js";
$normalizeCSS = "/files/css/normalize.css";
$awsmCSS = "/files/css/awsm.css";

$dbhost = "127.0.0.1";
$dbuser = "CHANGEME";
$dbpass = "CHANGEME";
$dbname = "CHANGEME";

$websocket_protocol = ($_SERVER['HTTPS'] ? "wss://" : "ws://");
$websocket_host = "video.example.com";
$websocket_port = ($_SERVER['HTTPS'] ? "3334" : "3333");
$websocket_key = "?key=";
$websocket_app = "app";

$stats_protocol = ($_SERVER['HTTPS'] ? "https://" : "http://");
$stats_host = "video.example.com";
$stats_port = ($_SERVER['HTTPS'] ? "3330" : "3329");
$stats_api_type = "v1";
$stats_vhost_query = "video.example.com";
$stats_app = "app";
$stats_secret = "CHANGEME";
```

## Authorization Test

CHANGEME is "Q0hBTkdFTUU=" when base64_encode
```curl
curl --header "Authorization: Basic Q0hBTkdFTUU=" https://video.example.com:3330/v1/stats/current/vhosts/video.example.com/apps/app/streams/USER
```

# Usage

### OBS

**{vhost}** is your servers IP/Domain configured in OME

**{app}** is the application in OME

**{key}** is the live stream key from the MySQL Database

OBS Stream Settings

```
Server: rtmp://**{vhost}**/**{app}**
Stream Key: **{key}**
```

### Notes
Don't check "Use Authenication"

**The database should use all lower-case information and keys**