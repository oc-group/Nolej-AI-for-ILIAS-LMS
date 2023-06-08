<?php

require_once(ilNolejPlugin::PLUGIN_DIR . "/classes/Notification/NolejActivity.php");
require_once("./Services/Notifications/classes/class.ilNotificationConfig.php");

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
		}

		$this->data = $data;
		switch ($data["action"]) {
			case "tac":
				$this->checkTac();
				break;
			case "transcription":
				$this->checkTranscription();
				break;
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
			0
		);

		// Notification
		// $ass = new NolejActivity($exchangeId, $exchange["user_id"], "tac");
		// $ass->withStatus("ok")
		// 	->withCode(0)
		// 	->withErrorMessage($this->data["message"])
		// 	->withConsumedCredit(0)
		// 	->store();

		// require_once "Services/Notifications/classes/class.ilNotificationConfig.php";
		// $recipient_id = $exchange["user_id"];
		// $sender_id = SYSTEM_USER_ID;
		// $lang = ilObjUser::_lookupLanguage($recipient_id);
		// $lng = new ilLanguage($lang);
		// $lng->loadLanguageModule(ilNolejPlugin::PREFIX);
		// ilDatePresentation::setUseRelativeDates(false);

		// $notification = new ilNotificationConfig("chat_invitation");
		// $notification->setTitleVar($lng->txt(ilNolejPlugin::PREFIX . "_tac_received"));
		// $notification->setShortDescriptionVar($lng->txt(ilNolejPlugin::PREFIX . "tac_received_info_short"));
		// $notification->setLongDescriptionVar(sprintf(
		// 	$lng->txt(ilNolejPlugin::PREFIX . "_tac_received_info_long"),
		// 	$exchangeId,
		// 	ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX))
		// ));
		// $notification->setAutoDisable(false);
		// $notification->setValidForSeconds(0);
		// $notification->setHandlerParam('mail.sender', $sender_id);
		// $notification->notifyByUsers([$recipient_id]);

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
			"SELECT * FROM " . ilNolejPlugin::TABLE_DOC
			. " WHERE document_id = %s AND status = 1;",
			["text"],
			[$documentId]
		);
		if ($db->numRows($result) != 1) {
			$this->die_message(404, "Document ID not found.");
			return;
		}

		$document = $db->fetchAssoc($result);

		$result = $db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DOC
			. " SET status = 2 WHERE document_id = %s;",
			["text"],
			[$documentId]
		);
		if (!$result) {
			$this->die_message(404, "Document not found.");
		}

		$result = $db->queryF(
			"SELECT user_id FROM " . ilNolejPlugin::TABLE_ACTIVITY
			. " WHERE document_id = %s AND action = 'transcription'",
			array("text"),
			array($documentId)
		);
		if (!$result) {
			$this->die_message(404, "User of activity not found.");
		}

		$this->sendNotification(
			$documentId,
			$result["user_id"],
			"transcription_ready_" . $this->data["status"],
			$this->data["status"],
			$this->data["code"],
			$this->data["error_message"],
			$this->data["consumedCredit"]
		);

		$this->die_message(200, "Transcription received!");
	}

	/**
	 * Send notification to user
	 * 
	 * @param string $documentId
	 * @param int $userId
	 * @param string $action
	 * @param string $status
	 * @param int $code
	 * @param string $errorMessage
	 * @param int $credits
	 */
	public function sendNotification(
		$documentId,
		$userId,
		$action,
		$status,
		$code,
		$errorMessage,
		$credits
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
		$notification->setTitleVar($lng->txt(ilNolejPlugin::PREFIX . "_" . $action));
		$notification->setShortDescriptionVar($lng->txt(ilNolejPlugin::PREFIX . "_" . $action . "_long"));
		// $notification->setLongDescriptionVar(sprintf(
		// 	$lng->txt(ilNolejPlugin::PREFIX . "_tac_received_info_long"),
		// 	$documentId,
		// 	ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX))
		// ));
		$notification->setAutoDisable(false);
		$notification->setValidForSeconds(0);
		$notification->setHandlerParam('mail.sender', SYSTEM_USER_ID);
		$notification->notifyByUsers([$userId]);
	}
}
