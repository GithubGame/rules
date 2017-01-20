<?php
define("API_BASE_URL", "https://api.github.com/");
define("API_TOKEN", trim(file_get_contents(__DIR__ . "/token")));

function http($url, $method = "GET", $data = null, $extraHeaderList = []) {
	$url = ltrim($url, "/");
	$headerList = [
		"Accept: application/vnd.github.squirrel-girl-preview",
	];
	$dataString = null;

	if(!is_null($data)) {
		$dataString = json_encode($data);
	}

	switch($method) {
	case "PUT":
	case "POST":
		$headerList []= "Content-type: application/json";
		$headerList []= "Content-length: " . strlen($dataString);
		break;
	}

	$headerList = array_merge($headerList, $extraHeaderList);

	$curlOptions = [
		CURLOPT_URL => API_BASE_URL . $url,
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_HTTPHEADER => $headerList,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERAGENT => "GithubGame PHP cURL",
		CURLOPT_USERPWD => " GithubGameMaster:" . API_TOKEN,
	];

	if(!is_null($dataString)) {
		$curlOptions[CURLOPT_POSTFIELDS] = $dataString;
	}


	$ch = curl_init();
	curl_setopt_array($ch, $curlOptions);

	$response = curl_exec($ch);
	$json = json_decode($response);

	if($json === false) {
		echo "BAD REQUEST!" . PHP_EOL;
		echo $response . PHP_EOL;
		exit(1);
	}

	return $json;
}