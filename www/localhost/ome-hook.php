<?php
/* 
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
*/

$initpayload = file_get_contents('php://input');
$payload = json_decode($initpayload, true);

$host = "127.0.0.1";
$username = "CHANGEME";
$password = "CHANGEME";
$dbname = "CHANGEME";

$application = "app";
$omesecret = "CHANGEME";

function returnJSONEncode($data) {
	header('Content-Type: application/json; charset=utf-8');
	return json_encode($data);
}

function base64url_encode($data){
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}


try {
	$dbh = new PDO('mysql:host='.$host.';dbname='.$dbname.';charset=utf8', $username, $password);
}
catch(PDOException $e) {
	$authResponse['allowed'] = false;
	$authResponse['reason'] = "Status: Database Failure";
	echo returnJSONEncode($authResponse);
}

if((empty($payload['client'])) && (empty($payload['request']))) {
	$authResponse['allowed'] = false;
	$authResponse['reason'] = "Status: Empty CLIENT and REQUEST format - Rejecting Stream";
	echo returnJSONEncode($authResponse);
}

if((empty($payload['client'])) && (empty($payload['request']))) {
	$authResponse['allowed'] = false;
	$authResponse['reason'] = "Status: Empty CLIENT and REQUEST format - Rejecting Stream";
	echo returnJSONEncode($authResponse);
}

$omesig = $_SERVER['HTTP_X_OME_SIGNATURE'];
if ($omesig == base64url_encode(hash_hmac('sha1', $initpayload, $omesecret, true)))
{
	if($payload['request']['direction'] == "incoming")
	{
		$stream_scheme = preg_replace("/:\/\/.*/", "", $payload['request']['url']);
		
		$stream_host = preg_replace("/.*\/\//", "", $payload['request']['url']);
		$stream_host = preg_replace("/:.*/", "", $stream_host);

		$stream_port = preg_replace("/.*:/", "", $payload['request']['url']);
		$stream_port = preg_replace("/\/".$application.".*/", "", $stream_port);
		
		$stream_name = preg_replace("/.*".$application."\//", "", $payload['request']['url']);
		$stream_name = preg_replace("/\/.*/", "", $stream_name);
		$stream_name = preg_replace("/\?.*/", "", $stream_name);
		
		$stream = filter_var($stream_name, FILTER_SANITIZE_STRING);
		
		try {
			$qInfoKey = "SELECT stream_key, username FROM users WHERE stream_key = :stream_key";
			$qInfoKeyPrepared = $dbh->prepare($qInfoKey);
			$qInfoKeyPrepared->execute(array(':stream_key' => $stream));
			$qInfoKeyResults = $qInfoKeyPrepared->fetch(PDO::FETCH_ASSOC);
		}
		catch(PDOException $e) {
			$authResponse['allowed'] = false;
			$authResponse['reason'] = "Status: Database Failure";
			echo returnJSONEncode($authResponse);
		}
		
		if ($stream == $qInfoKeyResults['stream_key']) {
			$authResponse['new_url'] = $stream_scheme."://".$stream_host.":".$stream_port."/".$application."/".$qInfoKeyResults['username'];
			$authResponse['allowed'] = true;
			echo returnJSONEncode($authResponse);
		} else {
			$authResponse['allowed'] = false;
			$authResponse['reason'] = "Status: Stream Key Mismatch - Rejecting Stream";
			echo returnJSONEncode($authResponse);
		}
	}
	
	if($payload['request']['direction'] == "outgoing")
	{
		$stream_name = preg_replace("/.*".$application."\//", "", $payload['request']['url']);
		$stream_name = preg_replace("/\/.*/", "", $stream_name);
		$stream_name = preg_replace("/\?.*/", "", $stream_name);
		
		$stream = filter_var($stream_name, FILTER_SANITIZE_STRING);
		
		$stream_key = preg_replace("/.*key=/", "", $payload['request']['url']);
		$stream_key = preg_replace("/&.*/", "", $stream_key);
		$key = filter_var($stream_key, FILTER_SANITIZE_STRING);
		
		try {
			$qInfo = "SELECT private, private_key FROM users WHERE username = :username";
			$qInfoPrepared = $dbh->prepare($qInfo);
			$qInfoPrepared->execute(array(':username' => $stream));
			$qInfoResults = $qInfoPrepared->fetch(PDO::FETCH_ASSOC);
		}
		catch(PDOException $e)
		{
			$authResponse['allowed'] = false;
			$authResponse['reason'] = "Status: Database Failure";
			echo returnJSONEncode($authResponse);
		}
		
		if($qInfoResults['private'] == '0')
		{
			$authResponse['allowed'] = true;
			$authResponse['reason'] = "Status: Non-Private - Accepting Stream Playback";
			echo returnJSONEncode($authResponse);
		}
		elseif($qInfoResults['private'] == '1')
		{
			if($key == $qInfoResults['private_key'])
			{
				$authResponse['allowed'] = true;
				$authResponse['reason'] = "Status: Private Key Match - Accepting Stream Playback";
				echo returnJSONEncode($authResponse);
			}
			else
			{
				$authResponse['allowed'] = false;
				$authResponse['reason'] = "Status: Private Key Mismatch - Rejecting Stream Playback";
				echo returnJSONEncode($authResponse);
			}
		}
	}
}
else
{
	$authResponse['allowed'] = false;
	$authResponse['reason'] = "Status: X-OME-Signature Mismatch - Rejecting Payload";
	echo returnJSONEncode($authResponse);
}
?>
