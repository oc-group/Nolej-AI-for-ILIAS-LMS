<?php

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */

require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/vendor/autoload.php";

class ilNolejAPI {
    public const API_URL = "https://api-dev.nolej.io";

    private string $apikey;
    
    public function __construct(string $apikey) {
        $this->apikey = $apikey;
    }

    public function post(string $path, array $data = array(), bool $decode = true) {
        $data_json = json_encode($data);
		$url = self::API_URL . $path;

        $client = new GuzzleHttp\Client;
		$response = $client->request("POST", $url, [
			"headers" => [
				"Authorization" => "X-API-KEY " . $this->apikey,
				"User-Agent" => "ILIAS Plugin",
				"Content-Type" => "application/json"
			],
			"body" => $data_json
		]);

		if (!$decode) {
			return $response->getBody();
		}

		$object = json_decode($response->getBody());
		return $object !== null ? $object : $response->getBody();
    }
}
