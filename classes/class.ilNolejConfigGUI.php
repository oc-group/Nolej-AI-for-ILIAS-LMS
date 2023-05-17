<?php
include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejConfig.php");
include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejAPI.php");

/**
 * Plugin configuration class
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */

class ilNolejConfigGUI extends ilPluginConfigGUI
{
	const CMD_CONFIGURE = "configure";
	const CMD_SAVE = "save";
	const CMD_TIC = "tic";

	const TAB_CONFIGURE = "configuration";

	protected $ctrl;
	protected $cmd;
	protected $tabs;
	protected $db;
	protected $lng;

	protected $plugin;
	protected $config;

	public function __construct()
	{
		global $DIC, $tpl;
		$this->ctrl = $DIC->ctrl();
		$this->tabs = $DIC->tabs();
		$this->db = $DIC->database();
		$this->lng = $DIC->language();

		$this->plugin = ilNolejPlugin::getInstance();
		$this->config = new ilNolejConfig($this);

		$tpl->setTitleIcon(ilNolejPlugin::PLUGIN_DIR . "/templates/images/icon_xnlj.svg");
		$tpl->setTitle($this->plugin->txt("plugin_title"), false);
	}

	/**
	 * Handles all commmands,
	 * $cmd = functionName()
	 */
	public function performCommand($cmd)
	{
		// $next_class = $this->ctrl->getNextClass($this);
		$this->cmd = $this->ctrl->getCmd() ?? self::CMD_CONFIGURE;

		switch ($cmd) {
			case self::CMD_CONFIGURE:
			case self::CMD_SAVE:
			case self::CMD_TIC:
				$this->$cmd();
				break;

			default:
				$this->configure();
		}

		return true;
	}

	/**
	 * Init and activate tabs
	 */
	protected function initTabs($active_tab = null)
	{
		global $tpl;

		$this->tabs->addTab(
			self::TAB_CONFIGURE,
			$this->plugin->txt("tab_" . self::TAB_CONFIGURE),
			$this->ctrl->getLinkTarget($this, self::CMD_CONFIGURE)
		);

		switch ($active_tab) {
			case self::TAB_CONFIGURE:
			default:
				$this->tabs->activateTab(self::TAB_CONFIGURE);
				$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("tab_" . self::TAB_CONFIGURE), false);
		}

		$tpl->setDescription($this->plugin->txt("plugin_description"));
	}

	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigureForm() : ilPropertyFormGUI
	{
		$this->initTabs(self::TAB_CONFIGURE);

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->plugin->txt("configuration_title"));
		$form->addItem($section);

		$api_key = new ilTextInputGUI($this->plugin->txt("api_key"), "api_key");
		$api_key->setMaxLength(100);
		$api_key->setRequired(true);
		$api_key->setInfo($this->plugin->txt("api_key_info"));
		$api_key->setValue($this->plugin->getConfig("api_key", ""));
		$form->addItem($api_key);

		$form->addCommandButton(self::CMD_SAVE, $this->plugin->txt("cmd_save"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}

	/**
	 * register: update values in DB
	 */
	public function save() : void
	{
		global $tpl;

		$form = $this->initConfigureForm();

		if ($form->checkInput()) {
			$api_key = $form->getInput("api_key");
			$this->plugin->saveConfig("api_key", $api_key);
			$this->ctrl->redirect($this, self::CMD_CONFIGURE);

		} else {
			// input not ok, then
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
		}
	}

	/**
	 * Registration screen
	 */
	public function configure() : void
	{
		global $DIC, $tpl;

		$form = $this->initConfigureForm();
		$tpl->setContent($form->getHTML());

		$btnTic = ilLinkButton::getInstance();
		$btnTic->setCaption($this->_plugin()->txt("cmd_tic"), false);
		$btnTic->setUrl($this->ctrl->getLinkTarget($this, ilNolejGUI::CMD_TIC));
		$DIC->toolbar()->addButtonInstance($btnTic);
	}

	public function tic()
	{
		global $DIC;

		$api_key = $this->plugin->getConfig("api_key", "");
		if ($api_key == "") {
			ilUtil::sendFailure($this->plugin->txt("err_api_key_missing"), true);
			$this->configure();
			return;
		}

		$api = new ilNolejAPI($api_key);
		$message = "hello tic";
		$mediaUrl = "http://www.africau.edu/images/default/sample.pdf";
		$webhookUrl = ILIAS_HTTP_PATH . "/goto.php?target=xnlj_webhook";

		$result = $api->post(
			"/tic",
			[
				"message" => $message,
				"s3URL" => $mediaUrl,
				"webhookURL" => $webhookUrl
			],
			true
		);

		if (!is_array($result) || !isset($result["exchangeId"]) || !is_string($result["exchangeId"])) {
			ilUtil::sendFailure($this->plugin->txt("err_tic_response"), true);
			$this->configure();
			return;
		}

		$now = strtotime("now");
		$sql = "INSERT INTO " . ilNolejPlugin::TABLE_TIC . " (exchange_id, user_id, request_on, message, request_url) VALUES (%s, %s, %s, %s, %s);";
		$DIC->database()->manipulateF(
			$sql,
			["text", "integer", "integer", "text", "text"],
			[$result["exchangeId"], $DIC->user()->getId(), $now, $message, $webhookUrl]
		);
		
	}
}
