<?php
/*
This section is for configuration of the page!
*/

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

/*
DO NOT EDIT BELOW UNLESS YOU KNOW WHAT YOU ARE DOING
*/
?>

<?php
/*
Function declarations
*/
function db_connect() {
	global $dbhost, $dbname, $dbuser, $dbpass;
	return new PDO('mysql:host='.$dbhost.';dbname='.$dbname.';charset=utf8', $dbuser, $dbpass);
}
function db_getuserinfo($dbh, $username) {
	try
	{
		$qInfo = "SELECT username, private, private_key FROM users WHERE username = :username";
		$qInfoPrepared = $dbh->prepare($qInfo);
		$qInfoPrepared->execute(array(':username' => $username));
		return $qInfoPrepared->fetch(PDO::FETCH_ASSOC);
	}
	catch(exception $e)
	{
		/* Failed? */
	}
}
function db_checkuser($dbh, $username) {
	$qExists = "SELECT username FROM users WHERE username = :user"; 
	$qExistsPrepared = $dbh->prepare($qExists); 
	$qExistsPrepared->execute(array(':user' => $username));
	return ($qExistsPrepared->fetch() > 0) ? true : false;
}

function views($stream) {
	global $stats_secret, $stats_protocol, $stats_host, $stats_port, $stats_api_type, $stats_vhost_query, $stats_app;
	$headers = [
		'Authorization: Basic '.base64_encode($stats_secret)
	];
	$ch = curl_init();	
	$url = $stats_protocol.$stats_host.":".$stats_port."/".$stats_api_type."/stats/current/vhosts/".$stats_vhost_query."/apps/".$stats_app."/streams/".$stream;
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$response = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$response = substr($response, $headerSize);
	$json = returnJSONDecode($response);
	
	if(curl_error($ch) || ($httpcode == 0) || ($httpcode == 404)) {
		return "Stream is offline!";
	}else{
		return "Viewers: ".$json->{'response'}->{'totalConnections'};
	}
	curl_close($ch);
}

function returnJSONDecode($content) {
	return json_decode($content);
}
?>
<?php
/*
Beginning of page
*/
$dbcon = db_connect();

if((isset($_GET['stream']) || !(empty($_GET['stream'])))) {
	$stream = filter_var($_GET['stream'], FILTER_SANITIZE_STRING);
	$stream = strtolower($stream);
	$stream = htmlentities($stream);
	$dbCheck = db_checkuser($dbcon, $stream);
} else {
	$dbCheck = false;
}
if(isset($_GET['key'])) {
	$key = filter_var($_GET['key'], FILTER_SANITIZE_STRING);
	$key = strtolower($key);
	$key = htmlentities($key);
}
else
{
	$key = "";
}
if ($dbCheck) { $qInfoResults = db_getuserinfo($dbcon, $stream); } else { $qInfoResults = false; }
?>

<!-- 
    Copyright (C) 2021 Katie < https://github.com/Sora012/ome-stream-site >

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
-->

<html>
<head>
	<meta content="text/html; charset=utf-8" http-equiv="content-type">
	<title>Live Streams</title>
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<link rel="stylesheet" href='<?php echo $normalizeCSS; ?>' type="text/css">
	<link rel="stylesheet" href='<?php echo $awsmCSS; ?>' type="text/css">
	<style>
		#player {
			height: 504px !important;
			width: 896px;
		}
		legend {
			margin: 0 auto;
		}
		form input[type=submit] {
			display: block;
			margin: 0 auto;
		}
		form input[type=text] {
			display: block;
			margin: 0 auto;
			text-align: center;
			max-width: 120px;
		}
		form label {
			text-align: center;
		}
		@media only screen and (max-height: 650px) {
			footer {
				display: none;
			}
		}
		.loader, .loader:after {
			border-radius: 50%;
			width: 32px;
			height: 32px;
		}
		.loader {
			margin: 60px auto;
			font-size: 10px;
			position: relative;
			text-indent: -9999em;
			border-top: 8px solid rgba(255, 255, 255, 0.2);
			border-right: 8px solid rgba(255, 255, 255, 0.2);
			border-bottom: 8px solid rgba(255, 255, 255, 0.2);
			border-left: 8px solid #ffffff;
			-webkit-transform: translateZ(0);
			-ms-transform: translateZ(0);
			transform: translateZ(0);
			-webkit-animation: load8 1.1s infinite linear;
			animation: load8 1.1s infinite linear;
		}
		@-webkit-keyframes load8 {
			0% {
				-webkit-transform: rotate(0deg);
				transform: rotate(0deg);
			}
			100% {
				-webkit-transform: rotate(360deg);
				transform: rotate(360deg);
			}
		}
		@keyframes load8 {
			0% {
				-webkit-transform: rotate(0deg);
				transform: rotate(0deg);
			}
			100% {
				-webkit-transform: rotate(360deg);
				transform: rotate(360deg);
			}
		}
	</style>
	</head>
	<body>
		<main style="max-width: 896px !important; position: fixed;left: 50%;top: 50%;transform: translate(-50%,-50%);">	
			<?php if ((!isset($_GET['stream']) || (empty($_GET['stream'])))) { ?>
			<form>
				<h4 style="text-align: center;">Live Stream Access</h4>
				<br>
				<fieldset>
					<legend>Welcome</legend>
					<label>Stream Name: </label><input style="" type="text" name="stream" value=<?php if (isset($_GET['stream'])) { echo '"'; echo $stream; echo '"'; } else { echo '""'; }; ?>>
					<label>Access Code: </label><input style="" type="text" name="key" value=<?php if (isset($_GET['key'])) { echo '"'; echo $key; echo '"'; } else { echo '""'; }; ?>>
					<input type="submit" value="Submit">
				</fieldset>
			</form>
			<?php 
				} if (!is_bool($qInfoResults)) { if (($qInfoResults['private'] == '0') || ($qInfoResults['private'] == '1') && (!empty($key) && ($key == $qInfoResults['private_key']))) {
			?>
			
			<script src=<?php echo $OvenPlayer; ?>></script>
			<script src=<?php echo $jQuery; ?>></script>
			
			<div id="loader" class="loader"></div>
			<div id="video-content" style="display: none;">
				<div id="player" style="height: 480px; width: 100%"></div>
				<div id="video-stats"><?php echo views($stream); ?></div>
				
				<script>
					setInterval(RefreshVideoStats, 5000);
					function RefreshVideoStats() {
						$("#video-stats").load(window.location.href + " #video-stats" );
					}
				</script>
			</div>
			<div id="message" style="display: none; height: 480px; width: 896px; text-align: center; position: fixed;left: 50%;top: 70%;transform: translate(-50%,-50%);"><span style="margin: 0; position: absolute; top: 45%; left: 50%; transform: translate(-50%, -50%);"><h3><?php echo $stream ?> is offline!</h3><p id="countdown">Refreshing: 30</p></span></div>
			<script>
				function LoadOMEPlayer(timeoutPeriod) {
					document.getElementById("loader").classList.add("loader");
					const ovenplayer = OvenPlayer.create('player', {	
						volume: '50',
						sources: [{	
							label: 'WebRTC', 
							type: 'webrtc', 
							file: '<?php echo $websocket_protocol.$websocket_host.":".$websocket_port."/".$websocket_app."/".$stream.$websocket_key.$key?>'
						}]
					});
					ovenplayer.on("metaChanged", function (data) {
						if (data && data.type === "webrtc") {
							document.getElementById("video-content").style.display = "block";
							document.getElementById("loader").classList.remove("loader");
							document.getElementById("message").style.display = "none";
						}
					});
					ovenplayer.on('error', function () {
						document.getElementById("video-content").style.display = "none";
						document.getElementById("loader").classList.remove("loader");
						document.getElementById("message").style.display = "block";
						var timer = setInterval(function() {
							if (timeoutPeriod > 0) {
								timeoutPeriod -= 1;
								document.getElementById("countdown").innerHTML = "Refreshing: " + timeoutPeriod;
							} else {
								clearInterval(timer);
								LoadOMEPlayer(30);
							};
						}, 1000);
					});
				}
				LoadOMEPlayer(30);
			</script>
			<?php
				} if (($qInfoResults['private'] == '1') && (empty($key) || ($key != $qInfoResults['private_key']))) {
			?>
			<form>
				<h4 style="text-align: center;">Live Stream Access</h4>
				<br>
				<fieldset>
					<legend>Code Required</legend>
					<label>Stream Name: </label><input style="" type="text" name="stream" value=<?php if (isset($_GET['stream'])) { echo '"'; echo $stream; echo '"'; } else { echo '""'; }; ?>>
					<label>Access Code: </label><input style="" type="text" name="key" value=<?php if (isset($_GET['key'])) { echo '"'; echo $key; echo '"'; } else { echo '""'; }; ?>>
					<input type="submit" value="Submit">
				</fieldset>
			</form>
			
			<?php } elseif (($qInfoResults['username'] != $stream)) { ?>
			
			<form>
				<h4 style="text-align: center;">Live Stream Access</h4>
				<br>
				<fieldset>
					<legend>Unknown Stream</legend>
					<label>Stream Name: </label><input style="" type="text" name="stream" value=<?php if (isset($_GET['stream'])) { echo '"'; echo $stream; echo '"'; } else { echo '""'; }; ?>>
					<label>Access Code: </label><input style="" type="text" name="key" value=<?php if (isset($_GET['key'])) { echo '"'; echo $key; echo '"'; } else { echo '""'; }; ?>>
					<input type="submit" value="Submit">
				</fieldset>
			</form>
			
			<?php } } ?>
			<br><br>
		</main>
		<footer style="position: absolute; left: 50%; transform: translate(-50%,-50%); bottom: -1;">
			<p>Dev: <a href="https://steamcommunity.com/id/darth_revan/">Katie</a> Source: <a href="https://github.com/Sora012/ome-stream-site">Github</a> Theme: <a href="https://igoradamenko.github.io/awsm.css/">awsm css</a> Player: <a href="https://github.com/AirenSoft/OvenPlayer">OvenPlayer</a></p>
		</footer>
	</body>
</html>
