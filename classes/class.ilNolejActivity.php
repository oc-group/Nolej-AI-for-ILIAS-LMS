<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilBadgeAssignment
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */
class ilNolejActivity
{
	/** @var ilDBInterface */
	protected $db;

	/** @var int */
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

	/** @var int */
	protected $pos;

	/** @var bool */
	protected $stored;

	/**
	 * @param int|null $a_doc_id
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
		$noti_repo = new \ILIAS\Nolej\Notification\NolejNotificationPrefRepository($user);

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
	 * Get latest badge
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
			. " ORDER BY pos"
		);
		while ($row = $ilDB->fetchAssoc($set)) {
			$obj = new self();
			$obj->importDBRow($row);
			$res[] = $obj;
		}

		return $res;
	}

	public static function getInstancesByDocumentId($a_doc_id)
	{
		global $DIC;

		$ilDB = $DIC->database();

		$res = array();

		$set = $ilDB->query(
			"SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE badge_id = " . $ilDB->quote($a_doc_id, "integer")
		);
		while ($row = $ilDB->fetchAssoc($set)) {
			$obj = new self();
			$obj->importDBRow($row);
			$res[] = $obj;
		}

		return $res;
	}

	// public static function getInstancesByParentId($a_parent_obj_id)
	// {
	// 	global $DIC;

	// 	$ilDB = $DIC->database();

	// 	$res = array();

	// 	$badge_ids = array();
	// 	foreach (ilBadge::getInstancesByParentId($a_parent_obj_id) as $badge) {
	// 		$badge_ids[] = $badge->getId();
	// 	}
	// 	if (sizeof($badge_ids)) {
	// 		$set = $ilDB->query("SELECT * FROM badge_user_badge" .
	// 		" WHERE " . $ilDB->in("badge_id", $badge_ids, "", "integer"));
	// 		while ($row = $ilDB->fetchAssoc($set)) {
	// 			$obj = new self();
	// 			$obj->importDBRow($row);
	// 			$res[] = $obj;
	// 		}
	// 	}

	// 	return $res;
	// }

	public static function getAssignedUsers($a_doc_id)
	{
		$res = array();

		foreach (self::getInstancesByDocumentId($a_doc_id) as $ass) {
			$res[] = $ass->getUserId();
		}

		return $res;
	}

	public static function exists($a_doc_id, $a_user_id, $a_action)
	{
		$obj = new self($a_doc_id, $a_user_id, $a_action);
		return $obj->stored;
	}

	//
	// setter/getter
	//

	protected function setDocumentId($a_value)
	{
		$this->document_id = (int) $a_value;
	}

	public function getDocumentId()
	{
		return $this->document_id;
	}

	protected function setUserId($a_value)
	{
		$this->user_id = (int) $a_value;
	}

	public function getUserId()
	{
		return $this->user_id;
	}

	protected function setAction($a_value)
	{
		$this->action = $a_value;
	}

	public function getAction()
	{
		return $this->action;
	}

	protected function setTimestamp($a_value)
	{
		$this->tstamp = (int) $a_value;
	}

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

	public function setPosition($a_value)
	{
		if ($a_value !== null) {
			$a_value = (int) $a_value;
		}
		$this->pos = $a_value;
	}

	public function getPosition()
	{
		return $this->pos;
	}

	//
	// crud
	//

	protected function importDBRow(array $a_row)
	{
		$this->stored = true;
		$this->setDocumentId($a_row["doc_id"]);
		$this->setUserId($a_row["user_id"]);
		$this->setAction($a_row["action"]);
		$this->setTimestamp($a_row["tstamp"]);
		$this->status = $a_row["status"];
		$this->code = $a_row["code"];
		$this->error_message = $a_row["error_message"];
		$this->consumed_credit = $a_row["consumed_credit"];
		$this->setPosition($a_row["pos"]);
	}

	protected function read($a_doc_id, $a_user_id, $a_action)
	{
		$ilDB = $this->db;

		$set = $ilDB->queryF(
			"SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE document_id = %s AND user_id = %s",
			array("integer", "integer"),
			array($a_doc_id, $a_user_id)
		);
		$row = $ilDB->fetchAssoc($set);
		if ($row["user_id"]) {
			$this->importDBRow($row);
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
			"pos" => array(
				"integer",
				$this->getPosition()
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
			return;
		}

		$keys = array(
			"doc_id" => array("integer", $this->getDocumentId()),
			"user_id" => array("integer", $this->getUserId()),
			"action" => array("string", $this->getAction())
		);
		$fields = $this->getPropertiesForStorage();

		if (!(bool) $this->stored) {
			$ilDB->insert(ilNolejPlugin::TABLE_ACTIVITY, $fields + $keys);
		} else {
			$ilDB->update(ilNolejPlugin::TABLE_ACTIVITY, $fields, $keys);
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

		$ilDB->manipulate(
			"DELETE FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE document_id = %s AND user_id = %s AND action = %s",
			array("integer", "integer", "text"),
			array($this->getDocumentId(), $this->getUserId(), $this->getAction())
		);
	}

	public static function deleteByUserId($a_user_id)
	{
		foreach (self::getInstancesByUserId($a_user_id) as $ass) {
			$ass->delete();
		}
	}

	public static function deleteByDocumentId($a_doc_id)
	{
		foreach (self::getInstancesByDocumentId($a_doc_id) as $ass) {
			$ass->delete();
		}
	}

	/**
	 * @param int $a_user_id
	 * @param array $a_positions
	 */
	// public static function updatePositions($a_user_id, $a_positions)
	// {
	// 	$existing = array();
	// 	foreach (self::getInstancesByUserId($a_user_id) as $ass) {
	// 		$badge = new ilBadge($ass->getBadgeId());
	// 		$existing[$badge->getId()] = array($badge->getTitle(), $ass);
	// 	}

	// 	$new_pos = 0;
	// 	foreach ($a_positions as $title) {
	// 		foreach ($existing as $id => $item) {
	// 			if ($title == $item[0]) {
	// 				$item[1]->setPosition(++$new_pos);
	// 				$item[1]->store();
	// 				unset($existing[$id]);
	// 			}
	// 		}
	// 	}
	// }

	/**
	 * Get activities for user
	 * @param int $a_user_id
	 * @param int $a_ts_from
	 * @param int $a_ts_to
	 * @return array
	 */
	public static function getActivitiesForUser($a_user_id, $a_ts_from, $a_ts_to)
	{
		global $DIC;

		$db = $DIC->database();

		$set = $db->queryF(
			"SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE user_id = %s AND tstamp >= %s AND tstamp <= %s",
			array("integer","integer","integer"),
			array($a_user_id, $a_ts_from, $a_ts_to)
		);
		$res = [];
		while ($rec = $db->fetchAssoc($set)) {
			$res[] = $rec;
		}
		return $res;
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

	// public function getImagePath(ilBadge $a_badge)
	// {
	// 	$json_path = ilBadgeHandler::getInstance()->getInstancePath($this);
	// 	$baked_path = dirname($json_path);
	// 	$baked_file = array_shift(explode(".", basename($json_path)));

	// 	// get correct suffix from badge image
	// 	$suffix = strtolower(array_pop(explode(".", basename($a_badge->getImagePath()))));
	// 	return $baked_path . "/" . $baked_file . "." . $suffix;
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
