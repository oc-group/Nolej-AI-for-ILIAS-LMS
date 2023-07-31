<?php

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */

require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/vendor/autoload.php";

class ilNolejAPI {
	public const API_URL = "https://api-dev.nolej.io";

	/** @var string */
	private $apikey;

	/**
	 * @param string $apikey
	 */
	public function __construct($apikey)
	{
		$this->apikey = $apikey;
	}

	/**
	 * @param string $path
	 * @param array $data
	 * @param bool $decode
	 */
	public function post($path, $data = array(), $decode = true)
	{
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

	/**
	 * Put to Nolej server
	 * @param string $path
	 * @param mixed $data
	 * @param bool $encode input's data
	 * @param bool $decode output
	 */
	public function put($path, $data = array(), $encode = false, $decode = true)
	{
		$data_json = $encode ? json_encode($data) : $data;
		$url = self::API_URL . $path;

		$client = new GuzzleHttp\Client;
		$response = $client->request("PUT", $url, [
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

	/**
	 * @param string $path
	 * @param mixed $data
	 * @param bool $encode input's data
	 * @param bool $decode
	 * 
	 * @return object|string return the result given by Nolej. If
	 * $decode is true, treat the result as json object and decode it.
	 */
	public function get($path, $data = array(), $encode = false, $decode = true)
	{
		$url = self::API_URL . $path;
		$data_json = $encode ? json_encode($data) : $data;

		$client = new GuzzleHttp\Client;
		$response = $client->request("GET", $url, [
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
