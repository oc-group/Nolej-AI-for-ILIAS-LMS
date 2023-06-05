<?php

include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/Notification/NolejActivity.php");

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

		// Notification
		$ass = new NolejActivity($exchangeId, $exchange["user_id"], "tac");
		$ass->withStatus("ok")
			->withCode(0)
			->withErrorMessage($this->data["message"])
			->withConsumedCredit(0)
			->store();

		require_once "Services/Notifications/classes/class.ilNotificationConfig.php";
		$recipient_id = $exchange["user_id"];
		$sender_id = SYSTEM_USER_ID;
		$lang = ilObjUser::_lookupLanguage($recipient_id);
		$lng = new ilLanguage($lang);
		$lng->loadLanguageModule(ilNolejPlugin::PREFIX);
		ilDatePresentation::setUseRelativeDates(false);

		$notification = new ilNotificationConfig("chat_invitation");
		$notification->setTitleVar($lng->txt(ilNolejPlugin::PREFIX . "_tac_received"));
		$notification->setShortDescriptionVar($lng->txt(ilNolejPlugin::PREFIX . "tac_received_info_short"));
		$notification->setLongDescriptionVar(sprintf(
			$lng->txt(ilNolejPlugin::PREFIX . "_tac_received_info_long"),
			$exchangeId,
			ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX))
		));
		$notification->setAutoDisable(false);
		$notification->setValidForSeconds(0);
		$notification->setHandlerParam('mail.sender', $sender_id);
		$notification->notifyByUsers([$recipient_id]);

		$this->die_message(200, "TAC received!");
	}
}
