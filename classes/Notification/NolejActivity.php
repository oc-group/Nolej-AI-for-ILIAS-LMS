<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/Notification/NolejNotificationPrefRepository.php");

/**
 * Class NolejActivity
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */
class NolejActivity
{
	/** @var ilDBInterface */
	protected $db;

	/** @var string */
	protected $document_id;

	/** @var int */
	protected $user_id;

	/** @var string */
	protected $action;

	/** @var int */
	protected $tstamp;

	/** @var string */
	protected $status;

	/** @var int */
	protected $code;

	/** @var string */
	protected $error_message;

	/** @var int */
	protected $consumed_credit;

	/** @var string y|n */
	protected $notified = "n";

	/** @var bool */
	protected $stored;

	/** @var int|null */
	protected $objId = null;

	/** @var int|null */
	protected $refId = null;

	/** @var ilNolejPlugin */
	protected $plugin;

	/**
	 * @param string|null $a_doc_id
	 * @param int|null $a_user_id
	 * @param string|null $a_action
	 */
	public function __construct(
		$a_doc_id = null,
		$a_user_id = null,
		$a_action = null
	) {
		global $DIC;

		$this->db = $DIC->database();
		if ($a_doc_id && $a_user_id && $a_action) {
			$this->setDocumentId($a_doc_id);
			$this->setUserId($a_user_id);
			$this->setAction($a_action);

			$this->read($a_doc_id, $a_user_id, $a_action);
		}

		$this->plugin = ilNolejPlugin::getInstance();
	}

	/**
	 * Get new counter
	 *
	 * @param int $a_user_id
	 * @return int
	 */
	public static function getNewCounter($a_user_id)
	{
		global $DIC;

		$db = $DIC->database();

		$user = new ilObjUser($a_user_id);
		$noti_repo = new NolejNotificationPrefRepository($user);

		$last = $noti_repo->getLastCheckedTimestamp();

		// If no last check exists, we use last 24 hours
		if ($last == 0) {
			$last = time() - (24 * 60 * 60);
		}

		if ($last > 0) {
			$set = $db->queryF(
				"SELECT count(*) cnt FROM " . ilNolejPlugin::TABLE_ACTIVITY
				. " WHERE user_id = %s AND tstamp >= %s",
				["integer", "integer"],
				[$a_user_id, $last]
			);
			$rec = $db->fetchAssoc($set);
			return (int) $rec["cnt"];
		}

		return 0;
	}

	/**
	 * Get latest activity timestamp
	 *
	 * @param int $a_user_id
	 * @return int
	 */
	public static function getLatestTimestamp($a_user_id)
	{
		global $DIC;

		$db = $DIC->database();

		$set = $db->queryF(
			"SELECT max(tstamp) maxts FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE user_id = %s",
			["integer"],
			[$a_user_id]
		);
		$rec = $db->fetchAssoc($set);
		return (int) $rec["maxts"];
	}

	public static function getInstancesByUserId($a_user_id)
	{
		global $DIC;

		$ilDB = $DIC->database();

		$res = array();

		$set = $ilDB->query(
			"SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE user_id = " . $ilDB->quote($a_user_id, "integer")
			. " ORDER BY tstamp DESC;"
		);
		while ($row = $ilDB->fetchAssoc($set)) {
			$obj = new self();
			$obj->importDBRow($row);
			$res[] = $obj;
		}

		return $res;
	}

	/**
	 * @param string $a_doc_id
	 */
	public static function getInstancesByDocumentId($a_doc_id)
	{
		global $DIC;

		$ilDB = $DIC->database();

		$res = array();

		$set = $ilDB->queryF(
			"SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE document_id = %s",
			array("text"),
			array($a_doc_id)
		);
		while ($row = $ilDB->fetchAssoc($set)) {
			$obj = new self();
			$obj->importDBRow($row);
			$res[] = $obj;
		}

		return $res;
	}

	/**
	 * @param string $a_doc_id
	 */
	public static function getAssignedUsers($a_doc_id)
	{
		$res = array();

		foreach (self::getInstancesByDocumentId($a_doc_id) as $ass) {
			$res[] = $ass->getUserId();
		}

		return $res;
	}

	/**
	 * @param string $a_doc_id
	 * @param int $a_user_id
	 * @param string $a_action
	 */
	public static function exists($a_doc_id, $a_user_id, $a_action)
	{
		$obj = new self($a_doc_id, $a_user_id, $a_action);
		return $obj->stored;
	}

	//
	// setter/getter
	//

	/**
	 * @param string $a_value
	 */
	protected function setDocumentId($a_value)
	{
		$this->document_id = $a_value;
	}

	/**
	 * @return string|null
	 */
	public function getDocumentId()
	{
		return $this->document_id;
	}

	/**
	 * @param int $a_value
	 */
	protected function setUserId($a_value)
	{
		$this->user_id = (int) $a_value;
	}

	/**
	 * @return int|null
	 */
	public function getUserId()
	{
		return $this->user_id;
	}

	/**
	 * @param string $a_value
	 */
	protected function setAction($a_value)
	{
		$this->action = $a_value;
	}

	/**
	 * @return string|null
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param int $a_value
	 */
	protected function setTimestamp($a_value)
	{
		$this->tstamp = (int) $a_value;
	}

	/**
	 * @return int|null
	 */
	public function getTimestamp()
	{
		return $this->tstamp;
	}

	/**
	 * @param $a_status
	 * @return self
	 */
	public function withStatus($a_status)
	{
		$this->status = $a_status;
		return $this;
	}

	/**
	 * @param $a_code
	 * @return self
	 */

	 public function withCode($a_code)
	{
		$this->code = $a_code;
		return $this;
	}

	/**
	 * @param $a_error_message
	 * @return self
	 */
	public function withErrorMessage($a_error_message)
	{
		$this->error_message = $a_error_message;
		return $this;
	}

	/**
	 * @param $a_consumed_credit
	 * @return self
	 */
	public function withConsumedCredit($a_consumed_credit)
	{
		$this->consumed_credit = $a_consumed_credit;
		return $this;
	}

	//
	// crud
	//

	protected function importDBRow(array $a_row)
	{
		$this->stored = true;
		$this->setDocumentId($a_row["document_id"]);
		$this->setUserId($a_row["user_id"]);
		$this->setAction($a_row["action"]);
		$this->setTimestamp($a_row["tstamp"]);
		$this->status = $a_row["status"];
		$this->code = $a_row["code"];
		$this->error_message = $a_row["error_message"];
		$this->consumed_credit = $a_row["consumed_credit"];
		$this->notified = $a_row["notified"];
	}

	/**
	 * @param string $a_doc_id
	 * @param int $a_user_id
	 * @param string $a_action
	 */
	protected function read($a_doc_id, $a_user_id, $a_action)
	{
		$ilDB = $this->db;

		$set = $ilDB->queryF(
			"SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE document_id = %s AND user_id = %s AND action = %s",
			array("text", "integer", "text"),
			array($a_doc_id, $a_user_id, $a_action)
		);
		$row = $ilDB->fetchAssoc($set);
		if ($row["user_id"]) {
			$this->importDBRow($row);
		} else {
			$this->setTimestamp(time());
		}
	}

	protected function getPropertiesForStorage()
	{
		return array(
			"tstamp" => array(
				"integer",
				(bool) $this->stored ? $this->getTimestamp() : time()
			),
			"status" => array(
				"text",
				$this->status
			),
			"code" => array(
				"integer",
				$this->code
			),
			"error_message" => array(
				"text",
				$this->error_message
			),
			"consumed_credit" => array(
				"integer",
				$this->consumed_credit
			),
			"notified" => array(
				"text",
				$this->notified
			)
		);
	}

	public function store()
	{
		$ilDB = $this->db;

		if (!$this->getDocumentId() ||
			!$this->getUserId() ||
			!$this->getAction()
		) {
			ilUtil::sendFailure("Some value null", true);
			$this->plugin->logger->log("Notification: some values are null: " . print_r([
				"documentId" => $this->getDocumentId(),
				"userId" => $this->getUserId(),
				"action" => $this->getAction()
			], true));
			return;
		}

		$keys = array(
			"document_id" => array("text", $this->getDocumentId()),
			"user_id" => array("integer", $this->getUserId()),
			"action" => array("text", $this->getAction())
		);
		$fields = $this->getPropertiesForStorage();

		if (!(bool) $this->stored) {
			$res = $ilDB->insert(ilNolejPlugin::TABLE_ACTIVITY, $fields + $keys);
		} else {
			$res = $ilDB->update(ilNolejPlugin::TABLE_ACTIVITY, $fields, $keys);
		}
	}

	public function delete()
	{
		$ilDB = $this->db;

		if (!$this->getDocumentId() ||
			!$this->getUserId() ||
			!$this->getAction()
		) {
			return;
		}

		$ilDB->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_ACTIVITY
			. " SET notified = 'y' WHERE document_id = %s AND user_id = %s AND action = %s",
			array("integer", "integer", "text"),
			array($this->getDocumentId(), $this->getUserId(), $this->getAction())
		);
	}

	/**
	 * @param int $a_user_id
	 */
	public static function deleteByUserId($a_user_id)
	{
		foreach (self::getInstancesByUserId($a_user_id) as $ass) {
			$ass->delete();
		}
	}

	/**
	 * @param string $a_doc_id
	 */
	public static function deleteByDocumentId($a_doc_id)
	{
		foreach (self::getInstancesByDocumentId($a_doc_id) as $ass) {
			$ass->delete();
		}
	}

	/**
	 * Get activities for user
	 * @param int $a_user_id
	 * @param int $a_ts_from
	 * @param int|null $a_ts_to
	 * @return array
	 */
	public static function getActivitiesForUser($a_user_id, $a_ts_from, $a_ts_to = null)
	{
		global $DIC;
		$db = $DIC->database();

		$a_ts_to = $a_ts_to == null ? time() : $a_ts_to;

		$set = $db->queryF(
			"SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE user_id = %s"
			. " AND tstamp >= %s AND tstamp <= %s"
			. " AND notified = 'n'"
			. " ORDER BY tstamp DESC;",
			array("integer","integer","integer"),
			array($a_user_id, $a_ts_from, $a_ts_to)
		);
		$res = [];
		while ($rec = $db->fetchAssoc($set)) {
			$obj = new self();
			$obj->importDBRow($rec);
			$res[] = $obj;
		}
		return $res;
	}

	/**
	 * @return int|null
	 */
	public function lookupObjId()
	{
		global $DIC;

		if ($this->objId != null) {
			return $this->objId;
		}

		$db = $DIC->database();

		$res = $db->queryF(
			"SELECT id FROM " . ilNolejPlugin::TABLE_DATA
			. " WHERE document_id = %s;",
			array("text"),
			array($this->getDocumentId())
		);
		$row = $db->fetchAssoc($res);
		$this->objId = (int) $row["id"];
		return $this->objId;
	}

	/**
	 * @return string|null
	 */
	public function lookupDocumentTitle()
	{
		$objId = $this->lookupObjId();
		return $objId == null ? null : ilObject::_lookupTitle($objId);
	}

	/**
	 * @return int|null
	 */
	public function lookupRefId()
	{
		if ($this->refId != null) {
			return $this->refId;
		}
		$objId = $this->lookupObjId();
		if ($objId == null) {
			return null;
		}
		$refs = ilObject::_getAllReferences($objId);
		if (is_array($refs) && count($refs) > 0) {
			return array_values($refs)[0];
		}
		return null;
	}

	//
	// PUBLISHING
	//

	// protected function prepareJson($a_url)
	// {
	// 	$verify = new stdClass();
	// 	$verify->type = "hosted";
	// 	$verify->url = $a_url;

	// 	$recipient = new stdClass();
	// 	$recipient->type = "email";
	// 	$recipient->hashed = true;
	// 	$recipient->salt = ilBadgeHandler::getInstance()->getObiSalt();

	// 	// https://github.com/mozilla/openbadges-backpack/wiki/How-to-hash-&-salt-in-various-languages.
	// 	$user = new ilObjUser($this->getUserId());
	// 	$mail = $user->getPref(ilBadgeProfileGUI::BACKPACK_EMAIL);
	// 	if (!$mail) {
	// 		$mail = $user->getEmail();
	// 	}
	// 	$recipient->identity = 'sha256$' . hash('sha256', $mail . $recipient->salt);

	// 	// spec: should be locally unique
	// 	$unique_id = md5($this->getBadgeId() . "-" . $this->getUserId());

	// 	$json = new stdClass();
	// 	$json->{"@context"} = "https://w3id.org/openbadges/v1";
	// 	$json->type = "Assertion";
	// 	$json->id = $a_url;
	// 	$json->uid = $unique_id;
	// 	$json->recipient = $recipient;

	// 	$badge = new ilBadge($this->getBadgeId());
	// 	$badge_url = $badge->getStaticUrl();

	// 	// created baked image
	// 	$baked_image = $this->getImagePath($badge);
	// 	if ($this->bakeImage($baked_image, $badge->getImagePath(), $a_url)) {
	// 		// path to url
	// 		$parts = explode("/", $a_url);
	// 		array_pop($parts);
	// 		$parts[] = basename($baked_image);
	// 		$json->image = implode("/", $parts);
	// 	}

	// 	$json->issuedOn = $this->getTimestamp();
	// 	$json->badge = $badge_url;
	// 	$json->verify = $verify;

	// 	return $json;
	// }

	// protected function bakeImage($a_baked_image_path, $a_badge_image_path, $a_assertion_url)
	// {
	// 	$suffix = strtolower(array_pop(explode(".", basename($a_badge_image_path))));
	// 	if ($suffix == "png") {
	// 		// using chamilo baker lib
	// 		include_once "Services/Badge/lib/baker.lib.php";
	// 		$png = new PNGImageBaker(file_get_contents($a_badge_image_path));

	// 		// add payload
	// 		if ($png->checkChunks("tEXt", "openbadges")) {
	// 			$baked = $png->addChunk("tEXt", "openbadges", $a_assertion_url);
	// 		}

	// 		// create baked file
	// 		if (!file_exists($a_baked_image_path)) {
	// 			file_put_contents($a_baked_image_path, $baked);
	// 		}

	// 		// verify file
	// 		$verify = $png->extractBadgeInfo(file_get_contents($a_baked_image_path));
	// 		if (is_array($verify)) {
	// 			return true;
	// 		}
	// 	} elseif ($suffix == "svg") {
	// 		// :TODO: not really sure if this is correct
	// 		$svg = simplexml_load_file($a_badge_image_path);
	// 		$ass = $svg->addChild("openbadges:assertion", "", "http://openbadges.org");
	// 		$ass->addAttribute("verify", $a_assertion_url);
	// 		$baked = $svg->asXML();

	// 		// create baked file
	// 		if (!file_exists($a_baked_image_path)) {
	// 			file_put_contents($a_baked_image_path, $baked);
	// 		}

	// 		return true;
	// 	}

	// 	return false;
	// }

	// public function getStaticUrl()
	// {
	// 	$path = ilBadgeHandler::getInstance()->getInstancePath($this);
		
	// 	$url = ILIAS_HTTP_PATH . substr($path, 1);

	// 	if (!file_exists($path)) {
	// 		$json = json_encode($this->prepareJson($url));
	// 		file_put_contents($path, $json);
	// 	}

	// 	return $url;
	// }

	// public function deleteStaticFiles()
	// {
	// 	// remove instance files
	// 	$path = ilBadgeHandler::getInstance()->getInstancePath($this);
	// 	$path = str_replace(".json", ".*", $path);
	// 	array_map("unlink", glob($path));
	// }

	// public static function clearBadgeCache($a_user_id)
	// {
	// 	foreach (self::getInstancesByUserId($a_user_id) as $ass) {
	// 		$ass->deleteStaticFiles();
	// 	}
	// }
}
