<?php

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");
require_once("./Services/Tracking/interfaces/interface.ilLPStatusPlugin.php");
// Services/Tracking/classes/status/class.ilLPStatusPlugin.php
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilObjNolejGUI.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejConfig.php");

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */
class ilObjNolej extends ilObjectPlugin implements ilLPStatusPluginInterface
{
	/** @var bool */
	protected $online;

	/** @var string */
	protected $documentId;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param int $a_ref_id
	 */
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
		$this->config = ilNolejConfig::getInstance();
	}

	/**
	 * Get type.
	 */
	final function initType()
	{
		$this->setType(ilNolejPlugin::PLUGIN_ID);
	}

	/**
	 * Create object
	 */
	function doCreate()
	{
		global $ilDB;

		$ilDB->manipulateF(
			"INSERT INTO " . ilNolejPlugin::TABLE_DATA . " (id, is_online, document_id) VALUES (%s, %s, NULL)",
			array ("integer", "integer"),
			array($this->getId(), 0)
		);
	}

	/**
	 * Read data from db
	 */
	function doRead()
	{
		global $ilDB;

		$set = $ilDB->queryF(
			"SELECT * FROM " . ilNolejPlugin::TABLE_DATA . " WHERE id = %s;",
			array("integer"),
			array($this->getId())
		);
		while ($row = $ilDB->fetchAssoc($set)) {
			$this->setOnline($row["is_online"]);
			$this->setDocumentId($row["document_id"]);
		}
	}

	/**
	 * Update data
	 */
	function doUpdate()
	{
		global $ilDB;

		$ilDB->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DATA . " SET is_online = %s, document_id = %s WHERE id = %s;",
			array("integer", "text", "integer"),
			array($this->isOnline(), $this->getDocumentId(), $this->getId())
		);
	}

	/**
	 * Delete data from db
	 */
	function doDelete()
	{
		global $ilDB;

		$ilDB->manipulateF(
			"DELETE FROM " . ilNolejPlugin::TABLE_DATA . " WHERE id = %s;",
			array("integer"),
			array($this->getId())
		);
	}

	/**
	 * Set online
	 * @param boolean online
	 */
	function setOnline($a_val)
	{
		$this->online = $a_val;
	}

	/**
	 * Set document_id
	 * @param string documentId
	 */
	function setDocumentId($a_val)
	{
		$this->documentId = $a_val;
	}

	/**
	 * Get online
	 * @return boolean online
	 */
	function isOnline()
	{
		return $this->online;
	}

	/**
	 * Get document_id
	 * @return string documentId
	 */
	function getDocumentId()
	{
		return $this->documentId;
	}

	/** @return int */
	function getDocumentStatus()
	{
		global $ilDB;

		if ($this->getDocumentId() == null) {
			return 0;
		}

		$result = $ilDB->queryF(
			"SELECT `status` FROM " . ilNolejPlugin::TABLE_DOC . " WHERE document_id = %s",
			array("text"),
			array($this->getDocumentId())
		);

		$row = $this->db->fetchAssoc($result);
		return (int) $row["status"];
	}

	/** @return string */
	function getDocumentSource()
	{
		global $ilDB;

		if ($this->getDocumentId() == null) {
			return "";
		}

		$result = $ilDB->queryF(
			"SELECT `doc_url` FROM " . ilNolejPlugin::TABLE_DOC . " WHERE document_id = %s",
			array("text"),
			array($this->getDocumentId())
		);

		$row = $this->db->fetchAssoc($result);
		return $row["doc_url"];
	}

	/** @return string */
	function getDocumentMediaType()
	{
		global $ilDB;

		if ($this->getDocumentId() == null) {
			return "";
		}

		$result = $ilDB->queryF(
			"SELECT `media_type` FROM " . ilNolejPlugin::TABLE_DOC . " WHERE document_id = %s",
			array("text"),
			array($this->getDocumentId())
		);

		$row = $this->db->fetchAssoc($result);
		return $row["media_type"];
	}

	/** @return string */
	function getDocumentLang()
	{
		global $ilDB;

		if ($this->getDocumentId() == null) {
			return "";
		}

		$result = $ilDB->queryF(
			"SELECT `language` FROM " . ilNolejPlugin::TABLE_DOC . " WHERE document_id = %s",
			array("text"),
			array($this->getDocumentId())
		);

		$row = $this->db->fetchAssoc($result);
		return $row["language"];
	}

	/** @return bool */
	function getDocumentAutomaticMode()
	{
		global $ilDB;

		if ($this->getDocumentId() == null) {
			return false;
		}

		$result = $ilDB->queryF(
			"SELECT `automatic_mode` FROM " . ilNolejPlugin::TABLE_DOC . " WHERE document_id = %s",
			array("text"),
			array($this->getDocumentId())
		);

		$row = $this->db->fetchAssoc($result);
		return ilUtil::yn2tf($row["automatic_mode"]);
	}

	public function hasWritePermission()
	{
		global $ilAccess;
		return $ilAccess->checkAccess("write", "", $this->getRefId());
	}

	public function hasReadPermission()
	{
		global $ilAccess;
		return $ilAccess->checkAccess("read", "", $this->getRefId());
	}

	public function lookupDetails()
	{
		// if (!$this->isBound()) {
		// 	return (object) [];
		// }

		// if ($this->details != null) {
		// 	return $this->details;
		// }

		// $result = $this->config->api(array(
		// 	"cmd" => "details",
		// 	"id_partner" => $this->id_partner,
		// 	"id_course" => $this->id_course
		// ));

		// switch ($result) {
		// 	case "err_course_id":
		// 	case "err_partner_id":
		// 	case "err_maintenance":
		// 	case "err_response":
		// 		ilUtil::sendFailure($this->plugin->txt($result), true);
		// 		return $this->details = (object) [];
		// }

		// return $this->details = $result;
	}

	/**
	 * @return int
	 */
	public function getFirstActivity()
	{
		// global $ilDB;

		// $result = $ilDB->queryF(
		// 	"SELECT id_page FROM " . ilNolejPlugin::TABLE_LP . " WHERE id_partner = %s AND id_course = %s ORDER BY id_page ASC LIMIT 1;",
		// 	array("text", "integer"),
		// 	array($this->id_partner, $this->id_course)
		// );

		// if (!$result || $ilDB->numRows($result) != 1) {
		// 	return 0;
		// }

		// $rec = $ilDB->fetchAssoc($result);
		// return $rec["id_page"];

		// TODO
		return 0;
	}

	/**
	 * @return int
	 */
	public function getLastVisitedActivity()
	{
		// global $ilDB, $ilUser;

		// $result = $ilDB->queryF(
		// 	"SELECT id_page FROM " . ilNolejPlugin::TABLE_LP . " WHERE id_partner = %s AND id_course = %s AND user_id = %s ORDER BY last_change DESC, id_page ASC LIMIT 1;",
		// 	array("text", "integer", "integer"),
		// 	array($this->id_partner, $this->id_course, $ilUser->getId())
		// );

		// if (!$result || $ilDB->numRows($result) != 1) {
		// 	return 0;
		// }

		// $rec = $ilDB->fetchAssoc($result);
		// return $rec["id_page"];

		// TODO
		return 0;
	}

	// public function lookupPagesStatus()
	// {
	// 	global $ilDB, $ilUser;

	// 	if ($this->pagesStatus != null) {
	// 		return $this->pagesStatus;
	// 	}

	// 	$result = $ilDB->queryF(
	// 		"SELECT id_page, status FROM " . ilNolejPlugin::TABLE_LP . " WHERE id_partner = %s AND id_course = %s AND user_id = %s;",
	// 		array("text", "integer", "integer"),
	// 		array($this->id_partner, $this->id_course, $ilUser->getId())
	// 	);

	// 	if (!$result) {
	// 		return false;
	// 	}

	// 	$pagesStatus = [];
	// 	while ($row = $ilDB->fetchAssoc($result)) {
	// 		$pagesStatus[$row["id_page"]] = $row["status"];
	// 	}

	// 	return $this->pagesStatus = $pagesStatus;
	// }

	// public function getPageStatus($idPage)
	// {
	// 	$pagesStatus = $this->lookupPagesStatus();
	// 	if (!$pagesStatus) {
	// 		return 0;
	// 	}

	// 	if (!isset($pagesStatus[$idPage])) {
	// 		return 0;
	// 	}

	// 	return $pagesStatus[$idPage];
	// }

	/**
	 * @param array $user_ids
	 */
	public function resetLPOfUsers($user_ids)
	{
		for ($i = 0, $n = count($user_ids); $i < $n; $i++) {
			self::resetLPOfUser($user_ids[$i]);
		}
	}

	/**
	 * @param int $user_id
	 */
	public function resetLPOfUser($user_id)
	{
		// TODO

		// global $ilDB, $ilUser;

		// $ilDB->manipulateF(
		// 	"DELETE FROM " . ilNolejPlugin::TABLE_LP . " WHERE activity_id = %s AND document_id = %s AND user_id = %s;",
		// 	array("integer", "text", "integer"),
		// 	array($this->activity_id, $this->getDocumentId(), $user_id)
		// );

		// $now = strtotime("now");
		// for ($i = 0, $n = count($details->structure); $i < $n; $i++) {
		// 	for ($j = 0, $m = count($details->structure[$i]->pages); $j < $m; $j++) {
		// 		$id_page = $details->structure[$i]->pages[$j]->id_page;
		// 		$ilDB->manipulateF(
		// 			"INSERT INTO " . ilNolejPlugin::TABLE_LP . " (id_partner, id_course, id_page, user_id, status, last_change) VALUES (%s, %s, %s, %s, 0, %s);",
		// 			array("text", "integer", "integer", "integer", "integer"),
		// 			array($this->id_partner, $this->id_course, $id_page, $user_id, $now)
		// 		);
		// 	}
		// }
	}

	public function resetLP()
	{
		global $ilUser;
		self::resetLPOfUser($ilUser->getId());
	}

	/**
	* Get all user ids with LP status completed
	* @return array
	*/
	public function getLPCompleted()
	{
		global $ilDB;

		// if (!$this->isBound()) {
		// 	return [];
		// }

		// $result = $ilDB->queryF(
		// 	"SELECT user_id"
		// 	. " FROM " . ilNolejPlugin::TABLE_LP
		// 	. " WHERE id_partner = %s"
		// 	. " AND id_course = %s"
		// 	. " GROUP BY user_id"
		// 	. " HAVING (0.5 * SUM(status) / COUNT(status)) = 1",
		// 	array("text", "integer"),
		// 	array($this->getIdPartner(), $this->getIdCourse())
		// );

		// if (!$result) {
		// 	return [];
		// }

		// $lp = $ilDB->fetchAll($result, ilDBConstants::FETCHMODE_ASSOC);
		// return array_column($lp, "user_id");
		return [];
	}

	/**
	 * Get all user ids with LP status not attempted
	 * @return array
	 */
	public function getLPNotAttempted()
	{
		// global $ilDB;

		// if (!$this->isBound()) {
		// 	return [];
		// }

		// $result = $ilDB->queryF(
		// 	"SELECT user_id"
		// 	. " FROM " . ilNolejPlugin::TABLE_LP
		// 	. " WHERE id_partner = %s"
		// 	. " AND id_course = %s"
		// 	. " GROUP BY user_id"
		// 	. " HAVING (0.5 * SUM(status) / COUNT(status)) = 0",
		// 	array("text", "integer"),
		// 	array($this->getIdPartner(), $this->getIdCourse())
		// );

		// if (!$result) {
		// 	return [];
		// }

		// $lp = $ilDB->fetchAll($result, ilDBConstants::FETCHMODE_ASSOC);
		// return array_column($lp, "user_id");
		return [];
	}

	/**
	 * Get all user ids with LP status failed
	 * @return array
	 */
	public function getLPFailed()
	{
		// Nolej modules do not have a "fail" condition (yet)
		return [];
	}

	/**
	 * Get all user ids with LP status in progress
	 * @return array
	 */
	public function getLPInProgress()
	{
		// global $ilDB;

		// if (!$this->isBound()) {
		// 	return [];
		// }

		// $result = $ilDB->queryF(
		// 	"SELECT user_id"
		// 	. " FROM " . ilNolejPlugin::TABLE_LP
		// 	. " WHERE id_partner = %s"
		// 	. " AND id_course = %s"
		// 	. " GROUP BY user_id"
		// 	. " HAVING (0.5 * SUM(status) / COUNT(status)) > 0"
		// 	. " AND (0.5 * SUM(status) / COUNT(status)) < 1",
		// 	array("text", "integer"),
		// 	array($this->getIdPartner(), $this->getIdCourse())
		// );

		// if (!$result) {
		// 	return [];
		// }

		// $lp = $ilDB->fetchAll($result, ilDBConstants::FETCHMODE_ASSOC);
		// return array_column($lp, "user_id");
		return [];
	}

	/**
	 * Get current status for given user
	 * @param int $a_user_id
	 * @return int
	 */
	public function getLPStatusForUser($a_user_id)
	{
		// global $ilDB;

		// if (!$this->isBound()) {
		// 	return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
		// }

		// $result = $ilDB->queryF(
		// 	"SELECT (0.5 * SUM(status) / COUNT(status)) AS percentage"
		// 	. " FROM " . ilNolejPlugin::TABLE_LP
		// 	. " WHERE id_partner = %s"
		// 	. " AND id_course = %s"
		// 	. " AND user_id = %s"
		// 	. " GROUP BY user_id",
		// 	array("text", "integer", "integer"),
		// 	array($this->getIdPartner(), $this->getIdCourse(), $a_user_id)
		// );

		// if (!$result || $ilDB->numRows($result) != 1) {
		// 	return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
		// }

		// $row = $ilDB->fetchAssoc($result);
		// switch ($row["percentage"]) {
		// 	case 0:
		// 		return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
		// 	case 1:
		// 		return ilLPStatus::LP_STATUS_COMPLETED_NUM;
		// 	default:
		// 		return ilLPStatus::LP_STATUS_IN_PROGRESS_NUM;
		// }
		return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
	}

	/**
	 * Get current percentage for given user
	 * @param int $a_user_id
	 * @return int
	 */
	public function getPercentageForUser($a_user_id)
	{
		// global $ilDB;

		// if (!$this->isBound()) {
		// 	return 0;
		// }

		// $result = $ilDB->queryF(
		// 	"SELECT (0.5 * SUM(status) / COUNT(status)) AS percentage"
		// 	. " FROM " . ilNolejPlugin::TABLE_LP
		// 	. " WHERE id_partner = %s"
		// 	. " AND id_course = %s"
		// 	. " AND user_id = %s"
		// 	. " GROUP BY user_id",
		// 	array("text", "integer", "integer"),
		// 	array($this->getIdPartner(), $this->getIdCourse(), $a_user_id)
		// );

		// if (!$result || $ilDB->numRows($result) != 1) {
		// 	return 0;
		// }

		// $row = $ilDB->fetchAssoc($result);
		// return (int) $row["percentage"] * 100;
		return 0;
	}

	public function updateStatus($idPage)
	{
		// TODO
		
		// global $ilUser, $ilDB;

		// $user_id = $ilUser->getId();
		// $old_status = $this->getLPStatusForUser($user_id);

		// // Set page status as completed after the first visit (2 = completed)
		// $res = $ilDB->manipulateF(
		// 	"REPLACE INTO " . ilNolejPlugin::TABLE_LP
		// 	. " (user_id, id_partner, id_course, id_page, status, last_change)"
		// 	. " VALUES (%s, %s, %s, %s, 2, %s);",
		// 	array("integer", "text", "integer", "integer", "integer"),
		// 	array($user_id, $this->getIdPartner(), $this->getIdCourse(), $idPage, strtotime("now"))
		// );

		// ilLearningProgress::_tracProgress(
		// 	$user_id,
		// 	$this->getId(),
		// 	$this->getRefId(),
		// 	ilNolejPlugin::PLUGIN_ID
		// );

		// require_once "Services/Tracking/classes/class.ilChangeEvent.php";
		// ilChangeEvent::_recordReadEvent($this->getType(), $this->getRefId(), $this->getId(), $user_id);

		// $new_status = $this->getLPStatusForUser($user_id);
		// $percentage = $this->getPercentageForUser($user_id);
		// ilLPStatus::writeStatus($this->getId(), $user_id, $new_status, $percentage, $a_force_per = false, $old_status);
	}
}
