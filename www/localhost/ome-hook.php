<?php
/* 
    Copyright (C) 2021 Katie < https://steamcommunity.com/id/darth_revan/ >

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

function returnJSON($data) {
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
	echo returnJSON($authResponse);
}

if((empty($_POST['client'])) && (empty($_POST['request']))) {
	$authResponse['allowed'] = false;
	$authResponse['reason'] = "Status: Empty CLIENT and REQUEST format - Rejecting Stream";
	echo returnJSON($authResponse);
}

if((empty($_POST['client'])) && (empty($_POST['request']))) {
	$authResponse['allowed'] = false;
	$authResponse['reason'] = "Status: Empty CLIENT and REQUEST format - Rejecting Stream";
	echo returnJSON($authResponse);
}

$omesig = $_SERVER['HTTP_X_OME_SIGNATURE'];
if ($omesig == base64url_encode(hash_hmac('sha1', $payload, $omesecret, true)))
{
	if($_POST['request']['direction'] == "incoming")
	{
		$stream_scheme = preg_replace("/:\/\/.*/", "", $_POST['request']['url']);
		
		$stream_host = preg_replace("/.*\/\//", "", $_POST['request']['url']);
		$stream_host = preg_replace("/:.*/", "", $stream_host);

		$stream_port = preg_replace("/.*:/", "", $_POST['request']['url']);
		$stream_port = preg_replace("/\/".$application.".*/", "", $stream_port);
		
		$stream_name = preg_replace("/.*".$application."\//", "", $_POST['request']['url']);
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
			echo returnJSON($authResponse);
		}
		
		if ($stream == $qInfoKeyResults['stream_key']) {
			$authResponse['new_url'] = $stream_scheme."://".$stream_host.":".$stream_port."/".$application."/".$qInfoKeyResults['username'];
			$authResponse['allowed'] = true;
			echo returnJSON($authResponse);
		} else {
			$authResponse['allowed'] = false;
			$authResponse['reason'] = "Status: Stream Key Mismatch - Rejecting Stream";
			echo returnJSON($authResponse);
		}
	}
	
	if($_POST['request']['direction'] == "outgoing")
	{
		$stream_name = preg_replace("/.*".$application."\//", "", $_POST['request']['url']);
		$stream_name = preg_replace("/\/.*/", "", $stream_name);
		$stream_name = preg_replace("/\?.*/", "", $stream_name);
		
		$stream = filter_var($stream_name, FILTER_SANITIZE_STRING);
		
		$stream_key = preg_replace("/.*key=/", "", $_POST['request']['url']);
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
			echo returnJSON($authResponse);
		}
		
		if($qInfoResults['private'] == '0')
		{
			$authResponse['allowed'] = true;
			$authResponse['reason'] = "Status: Non-Private - Accepting Stream Playback";
			echo returnJSON($authResponse);
		}
		elseif($qInfoResults['private'] == '1')
		{
			if($key == $qInfoResults['private_key'])
			{
				$authResponse['allowed'] = true;
				$authResponse['reason'] = "Status: Private Key Match - Accepting Stream Playback";
				echo returnJSON($authResponse);
			}
			else
			{
				$authResponse['allowed'] = false;
				$authResponse['reason'] = "Status: Private Key Mismatch - Rejecting Stream Playback";
				echo returnJSON($authResponse);
			}
		}
	}
}
else
{
	$authResponse['allowed'] = false;
	$authResponse['reason'] = "Status: X-OME-Signature Mismatch - Rejecting Payload";
	echo returnJSON($authResponse);
}
?>
