<?php

require_once(ilNolejPlugin::PLUGIN_DIR . "/classes/Notification/NolejActivity.php");
require_once(ilNolejPlugin::PLUGIN_DIR . "/classes/Notification/NolejActivity.php");
require_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejActivityManagementGUI.php");

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */

class ilNolejWebhook
{
	/** @var ilNolejPlugin */
	protected $plugin;

	/** @var array */
	protected $data;

	public function __construct()
	{
		$this->plugin = ilNolejPlugin::getInstance();
	}

	/**
	 * @param string $msg
	 */
	public function log($msg)
	{
		$this->plugin->logger->log($msg);
	}

	public function parse()
	{
		header("Content-type: application/json; charset=UTF-8");
		$data = json_decode(file_get_contents("php://input"), true);

		if (
			!is_array($data) ||
			!isset($data["action"]) ||
			!is_string($data["action"])
		) {
			$this->die_message(400, "Request not valid.");
			$this->log("Received invalid request: " . print_r($data, true));
		}

		$this->data = $data;
		switch ($data["action"]) {
			case "tac":
				$this->checkTac();
				break;

			case "transcription":
				$this->log("Received request: " . print_r($data, true));
				$this->checkTranscription();
				break;

			case "analysis":
				$this->log("Received request: " . print_r($data, true));
				$this->checkAnalysis();
				break;

			case "activities":
				$this->log("Received request: " . print_r($data, true));
				$this->checkActivities();
				break;

			default:
				$this->log("Received invalid action: " . print_r($data, true));
		}
		exit;
	}

	/**
	 * Die with status code and a message
	 * @param int $code
	 * @param string $message
	 */
	protected function die_message(
		$code = 400,
		$message = ""
	) {
		http_response_code($code);
		if (!empty($message)) {
			$this->log("Replied to Nolej with message: " . $message);
			echo json_encode(["message" => $message]);
		}
		exit;
	}

	public function checkTac()
	{
		global $DIC;

		if (
			!isset($this->data["exchangeId"], $this->data["message"], $this->data["s3URL"]) ||
			!is_string($this->data["exchangeId"]) ||
			!is_string($this->data["message"]) ||
			!is_string($this->data["s3URL"])
		) {
			$this->die_message(400, "Request not valid.");
			return;
		}

		$db = $DIC->database();
		$exchangeId = $this->data["exchangeId"];

		$result = $db->queryF(
			"SELECT * FROM " . ilNolejPlugin::TABLE_TIC
			. " WHERE exchange_id = %s AND response_on IS NULL;",
			["text"],
			[$exchangeId]
		);
		if ($db->numRows($result) != 1) {
			$this->die_message(404, "Exchange not found.");
			return;
		}

		$exchange = $db->fetchAssoc($result);

		$now = strtotime("now");
		$lang = ilObjUser::_lookupLanguage($exchange["user_id"]);
		ilDatePresentation::setLanguage($lang);

		$result = $db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_TIC
			. " SET response_on = %s, response_url = %s WHERE exchange_id = %s;",
			["integer", "text", "text"],
			[$now, $this->data["s3URL"], $exchangeId]
		);
		if (!$result) {
			$this->die_message(404, "Exchange not found.");
		}

		$this->sendNotification(
			$exchangeId,
			$exchange["user_id"],
			"tac",
			"ok",
			0,
			$this->data["message"],
			0,
			"tac_received_info",
			[
				$exchangeId,
				ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX))
			]
		);

		$this->die_message(200, "TAC received!");
	}

	public function checkTranscription()
	{
		global $DIC;

		if (
			!isset(
				$this->data["documentID"],
				$this->data["status"],
				$this->data["code"],
				$this->data["error_message"],
				$this->data["consumedCredit"]
			) ||
			!is_string($this->data["documentID"]) ||
			!is_string($this->data["status"]) ||
			!is_string($this->data["error_message"]) ||
			!is_integer($this->data["code"]) ||
			!is_integer($this->data["consumedCredit"])
		) {
			$this->die_message(400, "Request not valid.");
			return;
		}

		$db = $DIC->database();
		$documentId = $this->data["documentID"];

		$result = $db->queryF(
			"SELECT a.user_id, d.title"
			. " FROM " . ilNolejPlugin::TABLE_DOC . " d"
			. " INNER JOIN ("
			. "   SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. "   WHERE document_id = %s"
			. "   AND tstamp = ("
			. "     SELECT MAX(tstamp)"
			. "     FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. "     WHERE document_id = %s"
			. "   )"
			. " ) a"
			. " ON a.document_id = d.document_id"
			. " WHERE d.document_id = %s AND d.status = %s;",
			[
				"text",
				"text",
				"text",
				"integer"
			],
			[
				$documentId,
				$documentId,
				$documentId,
				ilNolejActivityManagementGUI::STATUS_CREATION_PENDING
			]
		);
		if ($db->numRows($result) != 1) {
			$this->die_message(404, "Document ID not found.");
			return;
		}

		$document = $db->fetchAssoc($result);

		$result = $db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DOC
			. " SET status = %s, consumed_credit = %s WHERE document_id = %s;",
			[
				"integer",
				"integer",
				"text"
			],
			[
				ilNolejActivityManagementGUI::STATUS_ANALISYS,
				$this->data["consumedCredit"],
				$documentId
			]
		);
		if (!$result) {
			$this->die_message(404, "Document not found.");
		}

		$now = strtotime("now");
		$lang = ilObjUser::_lookupLanguage($document["user_id"]);
		ilDatePresentation::setLanguage($lang);

		$this->sendNotification(
			$documentId,
			$document["user_id"],
			"transcription_" . $this->data["status"],
			$this->data["status"],
			$this->data["code"],
			$this->data["error_message"],
			$this->data["consumedCredit"],
			"transcription_" . $this->data["status"] . "_desc",
			[
				$document["title"],
				ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX)),
				$this->data["error_message"]
			]
		);

		$this->die_message(200, "Transcription received!");
	}

	public function checkAnalysis()
	{
		global $DIC;

		if (
			!isset(
				$this->data["documentID"],
				$this->data["status"],
				$this->data["code"],
				$this->data["error_message"],
				$this->data["consumedCredit"]
			) ||
			!is_string($this->data["documentID"]) ||
			!is_string($this->data["status"]) ||
			!is_string($this->data["error_message"]) ||
			!is_integer($this->data["code"]) ||
			!is_integer($this->data["consumedCredit"])
		) {
			$this->die_message(400, "Request not valid.");
			return;
		}

		$db = $DIC->database();
		$documentId = $this->data["documentID"];

		$result = $db->queryF(
			"SELECT a.user_id, d.title"
			. " FROM " . ilNolejPlugin::TABLE_DOC . " d"
			. " INNER JOIN ("
			. "   SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. "   WHERE document_id = %s"
			. "   AND tstamp = ("
			. "     SELECT MAX(tstamp)"
			. "     FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. "     WHERE document_id = %s"
			. "   )"
			. " ) a"
			. " ON a.document_id = d.document_id"
			. " WHERE d.document_id = %s AND d.status = %s;",
			[
				"text",
				"text",
				"text",
				"integer"
			],
			[
				$documentId,
				$documentId,
				$documentId,
				ilNolejActivityManagementGUI::STATUS_ANALISYS_PENDING
			]
		);
		if ($db->numRows($result) != 1) {
			$this->die_message(404, "Document ID not found.");
			return;
		}

		$document = $db->fetchAssoc($result);

		$result = $db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DOC
			. " SET status = %s, consumed_credit = %s"
			. " WHERE document_id = %s;",
			[
				"integer",
				"integer",
				"text"
			],
			[
				ilNolejActivityManagementGUI::STATUS_REVISION,
				$this->data["consumedCredit"],
				$documentId
			]
		);
		if (!$result) {
			$this->die_message(404, "Document not found.");
		}

		$now = strtotime("now");
		$lang = ilObjUser::_lookupLanguage($document["user_id"]);
		ilDatePresentation::setLanguage($lang);

		$this->sendNotification(
			$documentId,
			$document["user_id"],
			"analysis_" . $this->data["status"],
			$this->data["status"],
			$this->data["code"],
			$this->data["error_message"],
			$this->data["consumedCredit"],
			"analysis_" . $this->data["status"] . "_desc",
			[
				$document["title"],
				ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX)),
				$this->data["error_message"]
			]
		);

		$this->die_message(200, "Analysis received!");
	}

	function checkActivities()
	{
		global $DIC;

		if (
			!isset(
				$this->data["documentID"],
				$this->data["status"],
				$this->data["code"],
				$this->data["error_message"],
				$this->data["consumedCredit"]
			) ||
			!is_string($this->data["documentID"]) ||
			!is_string($this->data["status"]) ||
			!is_string($this->data["error_message"]) ||
			!is_integer($this->data["code"]) ||
			!is_integer($this->data["consumedCredit"])
		) {
			$this->die_message(400, "Request not valid.");
			return;
		}

		$db = $DIC->database();
		$documentId = $this->data["documentID"];

		$result = $db->queryF(
			"SELECT a.user_id, d.title"
			. " FROM " . ilNolejPlugin::TABLE_DOC . " d"
			. " INNER JOIN ("
			. "   SELECT * FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. "   WHERE document_id = %s"
			. "   AND tstamp = ("
			. "     SELECT MAX(tstamp)"
			. "     FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. "     WHERE document_id = %s"
			. "   )"
			. " ) a"
			. " ON a.document_id = d.document_id"
			. " WHERE d.document_id = %s AND d.status = %s;",
			[
				"text",
				"text",
				"text",
				"integer"
			],
			[
				$documentId,
				$documentId,
				$documentId,
				ilNolejActivityManagementGUI::STATUS_ACTIVITIES_PENDING
			]
		);
		if ($db->numRows($result) != 1) {
			$this->die_message(404, "Document ID not found.");
			return;
		}

		$document = $db->fetchAssoc($result);
		$now = strtotime("now");
		$lang = ilObjUser::_lookupLanguage($document["user_id"]);
		ilDatePresentation::setLanguage($lang);

		if (
			$this->data["status"] != "\"ok\"" &&
			$this->data["status"] != "ok"
		) {
			$this->log("Result: ko");
			$this->sendNotification(
				$documentId,
				$document["user_id"],
				"activities_ko",
				$this->data["status"],
				$this->data["code"],
				$this->data["error_message"],
				$this->data["consumedCredit"],
				"action_activities_ko_desc",
				[
					$document["title"],
					ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX)),
					$this->data["error_message"]
				]
			);
			return;
		}

		$result = $db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DOC
			. " SET status = %s, consumed_credit = %s"
			. " WHERE document_id = %s;",
			[
				"integer",
				"integer",
				"text"
			],
			[
				ilNolejActivityManagementGUI::STATUS_COMPLETED,
				$this->data["consumedCredit"],
				$documentId
			]
		);
		if (!$result) {
			$this->die_message(404, "Document not found.");
		}

		$activityManagement = new ilNolejActivityManagementGUI(null, $documentId);
		$success = $activityManagement->downloadActivities();
		if (!$success) {
			$this->log("Failed to download activities.");
			$this->sendNotification(
				$documentId,
				$document["user_id"],
				"activities_ko",
				$this->data["status"],
				$this->data["code"],
				$this->data["error_message"],
				$this->data["consumedCredit"],
				"err_activities_get"
			);
	
			$this->die_message(200, "Activities received, but something went wrong while retrieving them.");
			return;
		}

		$this->sendNotification(
			$documentId,
			$document["user_id"],
			"activities_ok",
			$this->data["status"],
			$this->data["code"],
			$this->data["error_message"],
			$this->data["consumedCredit"],
			"action_activities_ok_desc",
			[
				$document["title"],
				ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX))
			]
		);
		$this->die_message(200, "Activities received!");
	}

	/**
	 * Send notification to user
	 * 
	 * @param string $documentId
	 * @param int $userId
	 * @param string $action used as language key for title
	 * @param string $status
	 * @param int $code
	 * @param string $errorMessage
	 * @param int $credits
	 * @param string $bodyVar language variable to use in mail body
	 * @param string[] $vars parameters to use in $bodyVar's sprintf
	 */
	public function sendNotification(
		$documentId,
		$userId,
		$action,
		$status,
		$code,
		$errorMessage,
		$credits,
		$bodyVar,
		$vars = array()
	) {
		// Send Notification
		$ass = new NolejActivity($documentId, $userId, $action);
		$ass->withStatus($status)
			->withCode($code)
			->withErrorMessage($errorMessage)
			->withConsumedCredit($credits)
			->store();

		// Send Email
		$lang = ilObjUser::_lookupLanguage($userId);
		$lng = new ilLanguage($lang);
		$lng->loadLanguageModule(ilNolejPlugin::PREFIX);
		ilDatePresentation::setUseRelativeDates(false);

		$notification = new ilNotificationConfig("chat_invitation");
		$notification->setTitleVar(
			$lng->txt(
				sprintf(
					"%s_action_%s",
					ilNolejPlugin::PREFIX,
					$action
				)
			)
		);
		$descriptionVar = sprintf(
			$lng->txt(ilNolejPlugin::PREFIX . "_" . $bodyVar),
			...$vars
		);
		$notification->setShortDescriptionVar($descriptionVar);
		$notification->setLongDescriptionVar($descriptionVar);
		$notification->setAutoDisable(false);
		$notification->setValidForSeconds(0);
		$notification->setHandlerParam('mail.sender', SYSTEM_USER_ID);
		$notification->notifyByUsers([$userId]);
	}
}
