<?php

include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejAPI.php");

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * 
 * @ilCtrl_isCalledBy ilNolejActivityManagementGUI: ilObjNolejGUI
 * @ilCtrl_Calls ilNolejActivityManagementGUI: ilFormPropertyDispatchGUI, ilPropertyFormGUI, ilInternalLinkGUI
 */
class ilNolejActivityManagementGUI
{
	const CMD_CREATION = "creation";
	const CMD_CREATE = "create";
	const CMD_ANALYSIS = "analysis";
	const CMD_ANALYZE = "analyze";
	const CMD_REVISION = "revision";
	const CMD_REVIEW = "review";
	const CMD_ACTIVITIES = "activities";
	const CMD_GENERATE = "generate";

	const SUBTAB_CREATION = "creation";
	const SUBTAB_ANALYSIS = "analysis";
	const SUBTAB_REVIEW = "review";
	const SUBTAB_ACTIVITIES = "activities";

	const PROP_TITLE = "title";
	const PROP_MEDIA_SRC = "media_source";
	const PROP_M_WEB = "web";
	const PROP_WEB_SRC = "web_src";
	const PROP_M_URL = "url";
	const PROP_M_CONTENT = "content";
	const PROP_M_AUDIO = "audio";
	const PROP_M_VIDEO = "video";
	const PROP_M_MOB = "mob";
	const PROP_M_FILE = "file";
	const PROP_M_TEXT = "freetext";
	const PROP_M_TEXTAREA = "textarea";
	const PROP_INPUT_MOB = "input_mob";
	const PROP_INPUT_FILE = "input_file";
	const PROP_LANG = "language";
	const PROP_AUTOMATIC = "automatic";

	/** @var ilCtrl */
	protected $ctrl;

	/** @var ilTabsGUI */
	protected $tabs;

	/** @var ilDBInterface */
	protected $db;

	/** @var ilLanguage */
	protected $lng;

	/** @var ilNolejPlugin */
	protected $plugin;

	/** @var ilObjNolejGUI */
	protected $gui_obj;

	/** @var int $status */
	protected $status = 0;

	/** @var string $defaultCmd */
	protected $defaultCmd;

	/** @var string[] $statusIcons */
	protected $statusIcons;

	/** @param ilObjNolejGUI $gui_obj */
	public function __construct($gui_obj)
	{
		global $DIC, $tpl;
		$this->ctrl = $DIC->ctrl();
		$this->tabs = $DIC->tabs();
		$this->db = $DIC->database();
		$this->lng = $DIC->language();

		$this->plugin = ilNolejPlugin::getInstance();
		$this->gui_obj = $gui_obj;
		$this->statusCheck();
	}

	/**
	 * Handles all commmands,
	 * $cmd = functionName()
	 */
	public function executeCommand()
	{
		global $tpl;
		$cmd = $this->ctrl->getCmd();

		$next_class = $this->ctrl->getNextClass($this);
        switch ($next_class) {
			// case "ilpropertyformgui":
			// 	include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
			// 	$this->initFormLink(self::LINK_MOD_EDIT);
			// 	$this->ctrl->forwardCommand($this->form);
			// 	break;

			case "ilinternallinkgui":
				$this->lng->loadLanguageModule("content");
				require_once("./Services/Link/classes/class.ilInternalLinkGUI.php");
				$link_gui = new ilInternalLinkGUI("RepositoryItem");
				$link_gui->filterLinkType("Media_Media");
				$link_gui->setFilterWhiteList(true);
				$this->ctrl->forwardCommand($link_gui);
				break;

			default:
				switch ($cmd) {
					// Need to have permission to access modules
					case self::CMD_CREATION:
					case self::CMD_CREATE:
					case self::CMD_ANALYSIS:
					case self::CMD_ANALYZE:
					case self::CMD_REVISION:
					case self::CMD_REVIEW:
					case self::CMD_ACTIVITIES:
					case self::CMD_GENERATE:
						$this->$cmd();
						break;

					default:
						$cmd = $this->defaultCmd;
						$this->$cmd();
				}
		}

		return true;
	}

	/**
	 * Return status icon and command
	 * @return void
	 */
	protected function statusCheck()
	{
		$this->status = $this->gui_obj->object->getDocumentStatus();
		$this->defaultCmd = self::CMD_CREATION;
		$statusIcons = [
			self::CMD_CREATION => "",
			self::CMD_ANALYSIS => "",
			self::CMD_REVISION => "",
			self::CMD_ACTIVITIES => ""
		];

		$current = $this->glyphicon("hand-right");
		$waiting = $this->glyphicon("time");
		$completed = $this->glyphicon("ok");

		switch ($this->status) {
			case 0:
				$statusIcons[self::CMD_CREATION] = $current;
				break;
			case 1:
				$statusIcons[self::CMD_CREATION] = $waiting;
				break;
			case 2:
				$this->defaultCmd = self::CMD_ANALYSIS;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $current;
				break;
			case 3:
				$this->defaultCmd = self::CMD_ANALYSIS;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $waiting;
				break;
			case 4:
				$this->defaultCmd = self::CMD_REVISION;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $current;
				break;
			case 5:
				$this->defaultCmd = self::CMD_REVISION;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $waiting;
				break;
			case 6:
				$this->defaultCmd = self::CMD_ACTIVITIES;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $completed;
				$statusIcons[self::CMD_ACTIVITIES] = $current;
				break;
			case 7:
				$this->defaultCmd = self::CMD_ACTIVITIES;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $completed;
				$statusIcons[self::CMD_ACTIVITIES] = $waiting;
				break;
			case 8:
				$this->defaultCmd = self::CMD_ACTIVITIES;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $completed;
				$statusIcons[self::CMD_ACTIVITIES] = $completed;
				break;
		}

		$this->statusIcons = $statusIcons;
	}

	/**
	 * @param string $id
	 */
	protected function glyphicon($id)
	{
		return "<span class=\"glyphicon glyphicon-$id \" aria-hidden=\"true\"></span> ";
	}

	/**
	 * Init and activate tabs
	 */
	protected function initSubTabs($active_subtab = null)
	{
		global $tpl;

		// Do nothing link: "javascript:void(0)"

		$this->tabs->addSubTab(
			self::SUBTAB_CREATION,
			$this->statusIcons[self::CMD_CREATION] . $this->plugin->txt("subtab_" . self::SUBTAB_CREATION),
			$this->ctrl->getLinkTarget($this, self::CMD_CREATION)
		);

		$this->tabs->addSubTab(
			self::SUBTAB_ANALYSIS,
			$this->statusIcons[self::CMD_ANALYSIS] . $this->plugin->txt("subtab_" . self::SUBTAB_ANALYSIS),
			$this->ctrl->getLinkTarget($this, self::CMD_ANALYSIS)
		);

		$this->tabs->addSubTab(
			self::SUBTAB_REVIEW,
			$this->statusIcons[self::CMD_REVISION] . $this->plugin->txt("subtab_" . self::SUBTAB_REVIEW),
			$this->ctrl->getLinkTarget($this, self::CMD_REVISION)
		);

		$this->tabs->addSubTab(
			self::SUBTAB_ACTIVITIES,
			$this->statusIcons[self::CMD_ACTIVITIES] . $this->plugin->txt("subtab_" . self::SUBTAB_ACTIVITIES),
			$this->ctrl->getLinkTarget($this, self::CMD_ACTIVITIES)
		);

		switch ($active_subtab) {
			case self::SUBTAB_ANALYSIS:
			case self::SUBTAB_REVIEW:
			case self::SUBTAB_ACTIVITIES:
				$this->tabs->activateSubTab($active_subtab);
				$tpl->setTitle(
					sprintf(
						"%s: %s",
						$this->plugin->txt("plugin_title"),
						$this->plugin->txt("subtab_" . $active_subtab)
					),
					false
				);
				break;

			case self::SUBTAB_CREATION:
			default:
				$this->tabs->activateSubTab(self::SUBTAB_CREATION);
				$tpl->setTitle(
					sprintf(
						"%s: %s",
						$this->plugin->txt("plugin_title"),
						$this->plugin->txt("subtab_" . self::SUBTAB_CREATION)
					),
					false
				);
		}

		$tpl->setDescription($this->plugin->txt("plugin_description"));
	}

	/**
	 * Initialize internal link selector
	 * 
	 * @return string js code that needs to be printed after the form
	*/
	protected function initIntLink()
	{
		global $tpl;

		include_once("./Services/Link/classes/class.ilInternalLinkGUI.php");
		$js = "";
		//  ilInternalLinkGUI::getInitHTML(
		// 	$this->ctrl->getLinkTargetByClass(
		// 		["", "ilinternallinkgui"],
		// 		"",
		// 		false,
		// 		true,
		// 		false
		// 	),
		// 	true
		// );

		$tpl->addJavaScript("Modules/WebResource/js/intLink.js");
		$tpl->addJavascript("Services/Form/js/Form.js");

		// include_once("./Services/YUI/classes/class.ilYuiUtil.php");
		// ilYuiUtil::initConnection();
		// $main_tpl->addJavaScript("./Services/UIComponent/Explorer/js/ilExplorer.js");

		return $js;
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	public function initCreationForm()
	{
		global $ilUser, $tpl;

		// $tpl->addJavaScript('./node_modules/tinymce/tinymce.js');

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("obj_xnlj"));

		$status = $this->status;

		if ($status == 0) {

			/**
			 * Module title
			 * By default is the Object title, it can be changed here.
			 */
			$title = new ilTextInputGUI($this->plugin->txt("prop_" . self::PROP_TITLE), self::PROP_TITLE);
			$title->setInfo($this->plugin->txt("prop_" . self::PROP_TITLE . "_info"));
			$title->setValue($this->gui_obj->object->getTitle());
			$title->setMaxLength(250);
			$form->addItem($title);

			/**
			 * Choose a source to analyze.
			 * - Web (url):
			 *   - Web page content;
			 *   - Audio streaming;
			 *   - Video streaming.
			 * - MediaPool (mob_id)
			 * - Document (file upload)
			 * - Text (textarea)
			 */
			$mediaSource = new ilRadioGroupInputGUI($this->plugin->txt("prop_" . self::PROP_MEDIA_SRC), self::PROP_MEDIA_SRC);
			$mediaSource->setRequired(true);
			$form->addItem($mediaSource);

			/* Source: WEB or Streaming Audio/Video */
			$mediaWeb = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_WEB), self::PROP_M_WEB);
			$mediaWeb->setInfo($this->plugin->txt("prop_" . self::PROP_M_WEB . "_info"));
			$mediaSource->addOption($mediaWeb);
			/* Source URL */
			$url = new ilUriInputGUI($this->plugin->txt("prop_" . self::PROP_M_URL), self::PROP_M_URL);
			$url->setRequired(true);
			$mediaWeb->addSubItem($url);
			/* Web Source Type */
			$mediaSourceType = new ilRadioGroupInputGUI($this->plugin->txt("prop_" . self::PROP_WEB_SRC), self::PROP_WEB_SRC);
			$mediaSourceType->setRequired(true);
			$mediaWeb->addSubItem($mediaSourceType);
			/* Source Web page content */
			$srcContent = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_CONTENT), self::PROP_M_CONTENT);
			$srcContent->setInfo($this->plugin->txt("prop_" . self::PROP_M_CONTENT . "_info"));
			$mediaSourceType->addOption($srcContent);
			/* Source Video: YouTube, Vimeo, Wistia */
			$srcAudio = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_AUDIO), self::PROP_M_AUDIO);
			$srcAudio->setInfo($this->plugin->txt("prop_" . self::PROP_M_AUDIO . "_info"));
			$mediaSourceType->addOption($srcAudio);
			/* Source Video: YouTube, Vimeo, Wistia */
			$srcVideo = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_VIDEO), self::PROP_M_VIDEO);
			$srcVideo->setInfo($this->plugin->txt("prop_" . self::PROP_M_VIDEO . "_info"));
			$mediaSourceType->addOption($srcVideo);

			/* Source: Media from MediaPool */
			$mediaMob = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_MOB), self::PROP_M_MOB);
			$mediaMob->setInfo($this->plugin->txt("prop_" . self::PROP_M_MOB . "_info"));
			$mediaSource->addOption($mediaMob);
			/* Mob ID */
			// $mob = new ilLinkInputGUI("", self::PROP_INPUT_MOB);
			// $mob->setAllowedLinkTypes(ilLinkInputGUI::INT);
			// $mob->setInternalLinkDefault("Media_Media", 0);
			// $mob->setFilterWhiteList(true);
			// $mob->setInternalLinkFilterTypes(["Media_Media"]);
			// $mob->setRequired(true);
			// $mob->setParent($this);
			// $mediaMob->addSubItem($mob);

			/**
			 * Source: File upload
			 * Upload audio/video/documents/text files in the plugin data directory.
			 * The media type is taken from the file extension.
			 */
			$mediaFile = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_FILE), self::PROP_M_FILE);
			$mediaFile->setInfo($this->plugin->txt("prop_" . self::PROP_M_FILE . "_info"));
			$mediaSource->addOption($mediaFile);
			/* File upload */
			$file = new ilFileInputGUI("", self::PROP_INPUT_FILE);
			$file->setRequired(true);
			$file->setSuffixes([
				"mp3", "was", "opus", "ogg", "oga", "m4a", // Audio
				"m4v", "mp4", "ogv", "avi", "webm", // Video
				"pdf", "doc", "docx", "odt", // Documents
				"txt", "htm", "html" // Text
			]);
			$mediaFile->addSubItem($file);

			/**
			 * Source: Text
			 * Write an html text that need to be saved just like uploaded files
			 * (with .html extension).
			 * 
			 * @todo use TinyMCE
			 */
			$mediaText = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_TEXT), self::PROP_M_TEXT);
			$mediaText->setInfo($this->plugin->txt("prop_" . self::PROP_M_TEXT . "_info"));
			$mediaSource->addOption($mediaText);
			/* Text area */
			$txt = new ilTextAreaInputGUI("", self::PROP_M_TEXTAREA);
			if (ilObjAdvancedEditing::_getRichTextEditor() === "tinymce") {
				$txt->setUseRte(true);
				$txt->setRteTags([
					"h1", "h2", "h3", "p",
					"ul", "ol", "li",
					"br", "strong", "u", "i",
				]);
				$txt->usePurifier(true);
				$txt->setRTERootBlockElement("");
				$txt->disableButtons([
					"charmap",
					"justifyright",
					"justifyleft",
					"justifycenter",
					"justifyfull",
					"alignleft",
					"aligncenter",
					"alignright",
					"alignjustify",
					"anchor",
					"pasteword"
				]);
				// $txt->setPurifier(\ilHtmlPurifierFactory::_getInstanceByType('frm_post'));
			}
			$txt->setRequired(true);
			$mediaText->addSubItem($txt);

			/**
			 * Source language
			 */
			$language = new ilSelectInputGUI($this->plugin->txt("prop_" . self::PROP_LANG), self::PROP_LANG);
			$language->setInfo($this->plugin->txt("prop_" . self::PROP_LANG . "_info"));
			$language->setOptions([
				"en" => "English",
				// "fr" => "French", // Soon
				// "it" => "Italian" // Soon
			]);
			$language->setRequired(true);
			$form->addItem($language);

			/**
			 * Automatic mode: skip to the h5p generation,
			 * just check audio/video transcription.
			 * Currently disabled.
			 * 
			 * @todo enable option when all the other steps are done.
			 */
			$automaticMode = new ilCheckboxInputGUI($this->plugin->txt("prop_" . self::PROP_AUTOMATIC), self::PROP_AUTOMATIC);
			$automaticMode->setInfo($this->plugin->txt("prop_" . self::PROP_AUTOMATIC . "_info"));
			$automaticMode->setChecked(false);
			$automaticMode->setDisabled(true);
			$form->addItem($automaticMode);

			$form->addCommandButton(self::CMD_CREATE, $this->plugin->txt("cmd_" . self::CMD_CREATE));
			$form->setFormAction($this->ctrl->getFormAction($this));

		} else {

			$title = new ilNonEditableValueGUI($this->plugin->txt("prop_" . self::PROP_TITLE), self::PROP_TITLE);
			$title->setValue($this->gui_obj->object->getTitle());
			$form->addItem($title);
			$mediaSource = new ilNonEditableValueGUI($this->plugin->txt("prop_" . self::PROP_MEDIA_SRC), self::PROP_MEDIA_SRC);
			$mediaSource->setValue($this->gui_obj->object->getDocumentSource());
			$mediaSource->setInfo($this->plugin->txt("prop_" . $this->gui_obj->object->getDocumentMediaType()));
			$form->addItem($mediaSource);
			$language = new ilNonEditableValueGUI($this->plugin->txt("prop_" . self::PROP_LANG), self::PROP_LANG);
			$language->setValue($this->gui_obj->object->getDocumentLang());
			$form->addItem($language);
			$automaticMode = new ilCheckboxInputGUI($this->plugin->txt("prop_" . self::PROP_AUTOMATIC), self::PROP_AUTOMATIC);
			$automaticMode->setChecked($this->gui_obj->object->getDocumentAutomaticMode());
			$automaticMode->setDisabled(true);
			$form->addItem($automaticMode);
		}

		return $form;
	}

	public function creation()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_CREATION);

		$form = $this->initCreationForm();
		$js = $this->initIntLink();

		// TODO: display info in a better way (maybe on the side)
		ilUtil::sendInfo($this->plugin->txt("prop_file_limits"));

		$tpl->setContent($form->getHTML() . $js);
	}

	public function create()
	{
		global $DIC, $tpl;
		$this->initSubTabs(self::SUBTAB_CREATION);

		$form = $this->initCreationForm();

		$api_key = $this->plugin->getConfig("api_key", "");
		if ($api_key == "") {
			ilUtil::sendFailure($this->plugin->txt("err_api_key_missing"));
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		if (!$form->checkInput()) {
			// input not ok, then
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		$apiTitle = $form->getInput(self::PROP_TITLE);

		/**
		 * Set $apiUrl (signed)
		 * Set $apiFormat
		 * Set $decrementedCredit (text => 1, audio => 2, video => 3)
		 */
		$mediaSrc = $form->getInput(self::PROP_MEDIA_SRC);
		switch ($mediaSrc) {
			case self::PROP_M_WEB:
				/**
				 * No need to sign the url, just check the
				 * source type (content, or audio/video streaming)
				 */
				$apiUrl = $form->getInput(self::PROP_M_URL);
				$format = $form->getInput(self::PROP_WEB_SRC);
				switch ($format) {
					case self::PROP_M_CONTENT:
						$apiFormat = self::PROP_M_WEB;
						$decrementedCredit = 1;
						break;

					case self::PROP_M_AUDIO:
						$apiFormat = $format;
						$decrementedCredit = 2;
						break;

					case self::PROP_M_VIDEO:
						$apiFormat = $format;
						$decrementedCredit = 3;
						break;
				}
				break;

			case self::PROP_M_MOB:
				/**
				 * @todo generate signed url
				 * @todo detect media format
				 * @todo decrement credit
				 */
				$apiUrl = "";
				$apiFormat = "";
				$decrementedCredit = 2;
				break;

			case self::PROP_M_FILE:
				/**
				 * @todo save file to plugin data dir
				 * @todo generate signed url
				 * @todo detect media format
				 * @todo decrement credit
				 */
				$apiUrl = "";
				$apiFormat = "";
				$decrementedCredit = 1;
				break;

			case self::PROP_M_TEXT:
				/**
				 * @todo save as file in the plugin data dir
				 * @todo generate signed url
				 */
				$apiUrl = "";
				$apiFormat = "freetext";
				$decrementedCredit = 1;
				break;
		}

		if (!$apiUrl || $apiUrl == "") {
			ilUtil::sendFailure($this->plugin->txt("err_media_url_empty"), true);
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		if (!$apiFormat || $apiFormat == "") {
			ilUtil::sendFailure($this->plugin->txt("err_media_format_unknown"), true);
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		$apiLanguage = $form->getInput(self::PROP_LANG);
		$apiAutomaticMode = (bool) $form->getInput(self::PROP_AUTOMATIC);

		// Update object title if it differs from the current one.
		if ($apiTitle != "" && $apiTitle != $this->gui_obj->object->getTitle()) {
			$this->gui_obj->object->setTitle($apiTitle);
			$this->gui_obj->object->update();
		}

		$api = new ilNolejAPI($api_key);
		$webhookUrl = ILIAS_HTTP_PATH . "/goto.php?target=xnlj_webhook";

		$result = $api->post(
			"/documents",
			[
				"userID" => $DIC->user()->getId(),
				"organisationID" => ($DIC->settings()->get('short_inst_name') ?? "ILIAS") . " [ILIAS Plugin]",
				"title" => $apiTitle,
				"decrementedCredit" => $decrementedCredit,
				"docURL" => $apiUrl,
				"webhookURL" => $webhookUrl,
				"mediaType" => $apiFormat,
				"automaticMode" => $apiAutomaticMode,
				"language" => $apiLanguage
			],
			true
		);

		if (!is_object($result) || !property_exists($result, "id") || !is_string($result->id)) {
			ilUtil::sendFailure($this->plugin->txt("err_doc_response") . " " . print_r($result, true));
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		$this->db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DATA . " SET"
			. " document_id = %s WHERE id = %s;",
			array("text", "integer"),
			array($result->id, $this->gui_obj->object->getId())
		);

		$this->db->manipulateF(
			"INSERT INTO " . ilNolejPlugin::TABLE_DOC
			. " (status, consumed_credit, doc_url, media_type, automatic_mode, language, document_id)"
			. "VALUES (1, %s, %s, %s, %s, %s, %s);",
			array("integer", "text", "text", "text", "text", "text"),
			array($decrementedCredit, $apiUrl, $apiFormat, ilUtil::tf2yn($apiAutomaticMode), $apiLanguage, $result->id)
		);

		$ass = new NolejActivity($result->id, $DIC->user()->getId(), "transcription");
		$ass->withStatus("ok")
			->withCode(0)
			->withErrorMessage("")
			->withConsumedCredit($decrementedCredit)
			->store();

		ilUtil::sendSuccess($this->plugin->txt("action_transcription"), true);
		$this->ctrl->redirect($this, self::CMD_ANALYSIS);
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	public function initAnalysisForm()
	{
		$dataDir = $this->gui_obj->object->getDataDir();
		$status = $this->status;
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("obj_xnlj"));

		/**
		 * Module title
		 * - $title: Title returned from transcription;
		 * - $objTitle: Current module title.
		 */
		$title = $this->gui_obj->object->getDocumentTitle();
		$objTitle = $this->gui_obj->object->getTitle();
		if ($title != "" && $title != $objTitle) {
			$titleInput = new ilTextInputGUI(
				$this->plugin->txt("prop_" . self::PROP_TITLE),
				self::PROP_TITLE
			);
			$titleInput->setValue($title);
		} else {
			$titleInput = new ilNonEditableValueGUI(
				$this->plugin->txt("prop_" . self::PROP_TITLE),
				self::PROP_TITLE
			);
			$titleInput->setValue($objTitle);
		}
		$form->addItem($titleInput);

		/**
		 * Transcription
		 * 
		 * @todo use TinyMCE
		 */
		$txt = new ilTextAreaInputGUI($this->plugin->txt("prop_" . self::PROP_M_TEXT), self::PROP_M_TEXT);
		$txt->setRequired(true);
		if (ilObjAdvancedEditing::_getRichTextEditor() === "tinymce") {
			$txt->setUseRte(true);
			$txt->setRteTags([
				"h1", "h2", "h3", "p",
				"ul", "ol", "li",
				"br", "strong", "u", "i",
			]);
			$txt->usePurifier(true);
			$txt->setRTERootBlockElement("");
			$txt->disableButtons([
				"charmap",
				"justifyright",
				"justifyleft",
				"justifycenter",
				"justifyfull",
				"alignleft",
				"aligncenter",
				"alignright",
				"alignjustify",
				"anchor",
				"pasteword"
			]);
			// $txt->setPurifier(\ilHtmlPurifierFactory::_getInstanceByType('frm_post'));
		}
		$txt->setValue(file_get_contents($dataDir . "/transcription.htm"));
		$form->addItem($txt);

		if ($status == 2) {
			$form->addCommandButton(self::CMD_ANALYZE, $this->plugin->txt("cmd_" . self::CMD_ANALYZE));
			$form->setFormAction($this->ctrl->getFormAction($this));
		} else {
			$titleInput->setDisabled(true);
			$txt->setDisabled(true);
		}

		return $form;
	}

	/**
	 * Download the transctiption of the analyzed media
	 * 
	 * @return bool
	 */
	protected function downloadTranscription()
	{
		$dataDir = $this->gui_obj->object->getDataDir();
		$documentId = $this->gui_obj->object->getDocumentId();
		$status = $this->status;

		if ($status < 2) {
			// Transctiption is not ready!
			ilUtil::sendFailure($this->plugin->txt("err_transcription_not_ready"));
			return false;
		}

		$api_key = $this->plugin->getConfig("api_key", "");
		$api = new ilNolejAPI($api_key);

		$result = $api->get(
			sprintf("/documents/%s/transcription", $documentId)
		);

		if (
			!is_object($result) ||
			!property_exists($result, "title") ||
			!is_string($result->title) ||
			!property_exists($result, "result") ||
			!is_string($result->result)
		) {
			ilUtil::sendFailure($this->plugin->txt("err_transcription_get") . sprintf($result));
			return false;
		}

		$title = $result->title;
		$this->db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DOC . " SET title = %s WHERE document_id = %s;",
			["text", "text"],
			[$title, $documentId]
		);

		if (!is_dir($dataDir)) {
			mkdir($dataDir, 0777, true);
		}
		$success = file_put_contents(
			$dataDir . "/transcription.htm",
			file_get_contents($result->result)
		);
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_transcription_download") . sprintf($result));
			return false;
		}

		return true;
	}

	public function analysis()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_ANALYSIS);

		$dataDir = $this->gui_obj->object->getDataDir();
		$status = $this->status;

		if ($status < 2) {
			ilUtil::sendInfo($this->plugin->txt("err_transcription_not_ready"));
			return;
		}

		if (!file_exists($dataDir . "/transcription.htm")) {
			$downloadSuccess = $this->downloadTranscription();
			if (!$downloadSuccess) {
				return;
			}
		}

		$form = $this->initAnalysisForm();

		$tpl->setContent($form->getHTML());
	}

	public function analyze()
	{
		global $DIC, $tpl;
		$this->initSubTabs(self::SUBTAB_ANALYSIS);

		$form = $this->initAnalysisForm();

		$api_key = $this->plugin->getConfig("api_key", "");
		if ($api_key == "") {
			ilUtil::sendFailure($this->plugin->txt("err_api_key_missing"));
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		if (!$form->checkInput()) {
			// input not ok, then
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		$documentId = $this->gui_obj->object->getDocumentId();
		$apiAutomaticMode = $this->gui_obj->object->getDocumentAutomaticMode();
		$dataDir = $this->gui_obj->object->getDataDir();
		$api = new ilNolejAPI($api_key);

		/**
		 * May update title
		 */
		$title = $form->getInput(self::PROP_TITLE);
		$objTitle = $this->gui_obj->object->getTitle();
		if ($title != "" && $title != $objTitle) {
			$this->gui_obj->object->setTitle($title);
			$this->gui_obj->object->update();
		}

		$url = ILIAS_HTTP_PATH . substr(ilWACSignedPath::signFile($dataDir . "/transcription.htm"), 1);
		$result = $api->put(
			sprintf("/documents/%s/transcription", $documentId),
			[
				"s3URL" => $url,
				"automaticMode" => $apiAutomaticMode
			],
			true
		);

		if (!is_object($result)) {
			ilUtil::sendFailure("1");
			return;
		}
		if (!property_exists($result, "result")) {
			ilUtil::sendFailure("2");
			return;
		}
		if (!is_string($result->result)) {
			ilUtil::sendFailure("3");
			return;
		}
		if ($result->result != "ok") {
			ilUtil::sendFailure($result->result);
			// return;
		}
		// return;
		// 	ilUtil::sendFailure($this->plugin->txt("err_doc_response") . " " . print_r($result, true));
		// 	$form->setValuesByPost();
		// 	$tpl->setContent($form->getHTML());
		// 	return;
		// // }

		$this->db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DOC
			. " SET status = 3 WHERE document_id = %s;",
			["text"],
			[$documentId]
		);

		$ass = new NolejActivity($documentId, $DIC->user()->getId(), "analysis");
		$ass->withStatus("ok")
			->withCode(0)
			->withErrorMessage("")
			->withConsumedCredit(0)
			->store();

		ilUtil::sendSuccess($this->plugin->txt("action_analysis"), true);
		$this->ctrl->redirect($this, self::CMD_REVISION);
	}

	public function revision()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_REVIEW);

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("obj_xnlj"));

		include_once(ilNolejPlugin::PLUGIN_DIR . "/Form/class.ilMultiSummaryInputGUI.php");
		$input = new ilMultiSummaryInputGUI("test", "test");
		$form->addItem($input);
		$tpl->setContent($form->getHTML());
	}

	public function review()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_REVIEW);

		// TODO
	}

	public function activities()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_ACTIVITIES);

		// TODO
	}

	public function generate()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_ACTIVITIES);

		// TODO
	}
}
