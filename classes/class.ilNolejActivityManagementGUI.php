<?php

include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejAPI.php");
include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejMediaSelectorGUI.php");

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * 
 * @ilCtrl_isCalledBy ilNolejActivityManagementGUI: ilObjNolejGUI
 * @ilCtrl_Calls ilNolejActivityManagementGUI: ilformpropertydispatchgui, ilinternallinkgui
 * @ilCtrl_Calls ilNolejActivityManagementGUI: ilpropertyformgui, ilinternallinkgui
 * @ilCtrl_Calls ilNolejActivityManagementGUI: ilformpropertydispatchgui, illinkinputgui, ilinternallinkgui
 */
class ilNolejActivityManagementGUI
{
	const CMD_CREATION = "creation";
	const CMD_CREATE = "create";
	const CMD_ANALYSIS = "analysis";
	const CMD_ANALYZE = "analyze";
	const CMD_REVISION = "revision";
	const CMD_REVIEW = "review";
	const CMD_SUMMARY = "summary";
	const CMD_SUMMARY_SAVE = "saveSummary";
	const CMD_QUESTIONS = "questions";
	const CMD_QUESTIONS_SAVE = "saveQuestions";
	const CMD_CONCEPTS = "concepts";
	const CMD_CONCEPTS_SAVE = "saveConcepts";
	const CMD_ACTIVITIES = "activities";
	const CMD_GENERATE = "generate";

	const TAB_CREATION = "tab_creation";
	const TAB_ANALYSIS = "tab_analysis";
	const TAB_REVIEW = "tab_review";
	const TAB_ACTIVITIES = "tab_activities";
	const SUBTAB_SUMMARY = "review_summary";
	const SUBTAB_QUESTIONS = "review_questions";
	const SUBTAB_CONCEPTS = "review_concepts";

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

	const TYPE_AUDIO = [
		"mp3", "was", "opus", "ogg", "oga", "m4a"
	];
	const TYPE_VIDEO = [
		"m4v", "mp4", "ogv", "avi", "webm"
	];
	const TYPE_DOC = [
		"pdf", "doc", "docx", "odt"
	];

	const STATUS_CREATION = 0;
	const STATUS_CREATION_PENDING = 1;
	const STATUS_ANALISYS = 2;
	const STATUS_ANALISYS_PENDING = 3;
	const STATUS_REVISION = 4;
	const STATUS_REVISION_PENDING = 5;
	const STATUS_ACTIVITIES = 6;
	const STATUS_ACTIVITIES_PENDING = 7;
	const STATUS_COMPLETED = 8;

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

		$next_class = $this->ctrl->getNextClass();
		$cmd = $this->ctrl->getCmd();

		switch ($next_class) {
			case "ilformpropertydispatchgui":
				$mob = $this->linkInputGUI();
				$form_gui = new ilFormPropertyDispatchGUI();
				$form_gui->setItem($mob);
				$this->ctrl->forwardCommand($form_gui);
				break;

			case "ilinternallinkgui":
				$this->lng->loadLanguageModule("content");
				require_once("./Services/Link/classes/class.ilInternalLinkGUI.php");
				$link_gui = new ilInternalLinkGUI("Media_Media", 0);
				$link_gui->filterLinkType("Media_Media");
				$link_gui->setFilterWhiteList(true);
				$link_gui->setSetLinkTargetScript(
                    $this->ctrl->getLinkTarget(
                        $this,
                        "setInternalLink"
                    )
                );
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
					case self::CMD_SUMMARY:
					case self::CMD_SUMMARY_SAVE:
					case self::CMD_QUESTIONS:
					case self::CMD_QUESTIONS_SAVE:
					case self::CMD_CONCEPTS:
					case self::CMD_CONCEPTS_SAVE:
					case self::CMD_REVIEW:
					case self::CMD_ACTIVITIES:
					case self::CMD_GENERATE:
						$this->$cmd();
						break;

					default:
						ilUtil::sendQuestion("Next class: $next_class; Cmd: $cmd", true);
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
			case self::STATUS_CREATION:
				$statusIcons[self::CMD_CREATION] = $current;
				break;
			case self::STATUS_CREATION_PENDING:
				$statusIcons[self::CMD_CREATION] = $waiting;
				break;
			case self::STATUS_ANALISYS:
				$this->defaultCmd = self::CMD_ANALYSIS;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $current;
				break;
			case self::STATUS_ANALISYS_PENDING:
				$this->defaultCmd = self::CMD_ANALYSIS;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $waiting;
				break;
			case self::STATUS_REVISION:
				$this->defaultCmd = self::CMD_REVISION;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $current;
				break;
			case self::STATUS_REVISION_PENDING:
				$this->defaultCmd = self::CMD_REVISION;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $waiting;
				break;
			case self::STATUS_ACTIVITIES:
				$this->defaultCmd = self::CMD_ACTIVITIES;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $completed;
				$statusIcons[self::CMD_ACTIVITIES] = $current;
				break;
			case self::STATUS_ACTIVITIES_PENDING:
				$this->defaultCmd = self::CMD_ACTIVITIES;
				$statusIcons[self::CMD_CREATION] = $completed;
				$statusIcons[self::CMD_ANALYSIS] = $completed;
				$statusIcons[self::CMD_REVISION] = $completed;
				$statusIcons[self::CMD_ACTIVITIES] = $waiting;
				break;
			case self::STATUS_COMPLETED:
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
	protected function initTabs($active_tab = null)
	{
		global $tpl;

		$this->tabs->clearTargets();
		$this->tabs->setBackTarget(
			$this->plugin->txt("cmd_back_to_content"),
			$this->ctrl->getLinkTarget($this->gui_obj, "")
		);

		$this->tabs->addTab(
			self::TAB_CREATION,
			$this->statusIcons[self::CMD_CREATION] . $this->plugin->txt(self::TAB_CREATION),
			$this->ctrl->getLinkTarget($this, self::CMD_CREATION)
		);

		$this->tabs->addTab(
			self::TAB_ANALYSIS,
			$this->statusIcons[self::CMD_ANALYSIS] . $this->plugin->txt(self::TAB_ANALYSIS),
			$this->ctrl->getLinkTarget($this, self::CMD_ANALYSIS)
		);

		$this->tabs->addTab(
			self::TAB_REVIEW,
			$this->statusIcons[self::CMD_REVISION] . $this->plugin->txt(self::TAB_REVIEW),
			$this->ctrl->getLinkTarget($this, self::CMD_REVISION)
		);

		$this->tabs->addTab(
			self::TAB_ACTIVITIES,
			$this->statusIcons[self::CMD_ACTIVITIES] . $this->plugin->txt(self::TAB_ACTIVITIES),
			$this->ctrl->getLinkTarget($this, self::CMD_ACTIVITIES)
		);

		switch ($active_tab) {
			case self::TAB_ANALYSIS:
			case self::TAB_REVIEW:
			case self::TAB_ACTIVITIES:
				$this->tabs->activateTab($active_tab);
				$tpl->setTitle(
					sprintf(
						"%s: %s",
						$this->plugin->txt("plugin_title"),
						$this->plugin->txt($active_tab)
					),
					false
				);
				break;

			case self::TAB_CREATION:
			default:
				$this->tabs->activateTab(self::TAB_CREATION);
				$tpl->setTitle(
					sprintf(
						"%s: %s",
						$this->plugin->txt("plugin_title"),
						$this->plugin->txt(self::TAB_CREATION)
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
		$js = ilInternalLinkGUI::getInitHTML("");

		// Already added in ilInternalLinkGUI::getInitHTML()
		$tpl->addJavaScript("Modules/WebResource/js/intLink.js");
		$tpl->addJavascript("Services/Form/js/Form.js");

		return $js;
	}

	/**
	 * Form's input to get a media element
	 */
	protected function linkInputGUI()
	{
		$mob = new ilLinkInputGUI("", self::PROP_INPUT_MOB);
		$mob->setAllowedLinkTypes(ilLinkInputGUI::INT);
		$mob->setInternalLinkDefault("Media_Media", 0);
		$mob->setFilterWhiteList(true);
		$mob->setInternalLinkFilterTypes(["Media_Media"]);
		$mob->setRequired(true);
		$mob->setParent($this);
		return $mob;
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

		if ($status == self::STATUS_CREATION) {

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
			$mob = $this->linkInputGUI();
			$mediaMob->addSubItem($mob);

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
				...self::TYPE_AUDIO,
				...self::TYPE_VIDEO,
				...self::TYPE_DOC,
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
			$txt->setRows(50);
			$txt->setMaxNumOfChars(50000);
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
		$this->initTabs(self::TAB_CREATION);

		$form = $this->initCreationForm();
		$js = $this->initIntLink();

		// TODO: display info in a better way (maybe on the side)
		if ($this->status == self::STATUS_CREATION) {
			$contentLimits = new ilInfoScreenGUI($this);

			$contentLimits->addSection($this->plugin->txt("limit_content"));

			$info = new ilInfoScreenGUI($this);
			$info->hideFurtherSections(true);

			$info->addSection("");
			$info->addProperty("", "");
			$info->addSection($this->plugin->txt("limit_audio"));
			$info->addProperty(
				$this->plugin->txt("limit_max_duration"),
				sprintf($this->plugin->txt("limit_minutes"), 50)
			);
			$info->addProperty(
				$this->plugin->txt("limit_min_characters"),
				"500"
			);
			$info->addProperty(
				$this->plugin->txt("limit_max_size"),
				"500 MB"
			);
			$info->addProperty(
				$this->plugin->txt("limit_type"),
				implode(", ", self::TYPE_AUDIO)
			);
			$info->addSection($this->plugin->txt("limit_video"));
			$info->addProperty(
				$this->plugin->txt("limit_max_duration"),
				sprintf($this->plugin->txt("limit_minutes"), 50)
			);
			$info->addProperty(
				$this->plugin->txt("limit_min_characters"),
				"500"
			);
			$info->addProperty(
				$this->plugin->txt("limit_max_size"),
				"500 MB"
			);
			$info->addProperty(
				$this->plugin->txt("limit_type"),
				implode(", ", self::TYPE_VIDEO)
			);
			$info->addSection($this->plugin->txt("limit_doc"));
			$info->addProperty(
				$this->plugin->txt("limit_max_pages"),
				"50"
			);
			$info->addProperty(
				$this->plugin->txt("limit_min_characters"),
				"500"
			);
			$info->addProperty(
				$this->plugin->txt("limit_max_size"),
				"500 MB"
			);
			$info->addProperty(
				$this->plugin->txt("limit_type"),
				implode(", ", self::TYPE_DOC)
			);

			$contentLimits->addProperty(
				$this->plugin->txt("limit_content"),
				$info->getHTML()
			);
		}

		$tpl->setContent((isset($contentLimits) ? $contentLimits->getHTML() : "") . $form->getHTML() . $js);
	}

	public function create()
	{
		global $DIC, $tpl;
		$this->initTabs(self::TAB_CREATION);

		$form = $this->initCreationForm();
		$js = $this->initIntLink();

		$api_key = $this->plugin->getConfig("api_key", "");
		if ($api_key == "") {
			ilUtil::sendFailure($this->plugin->txt("err_api_key_missing"));
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML() . $js);
			return;
		}

		if (!$form->checkInput()) {
			// input not ok, then
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML() . $js);
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
				$mobInput = $form->getInput(self::PROP_INPUT_MOB);
				$array = explode("|", $mobInput);
				if (!$array) {
					break;
				}
				$objId = $array[1];
				$path = ilObjMediaObject::_lookupItemPath($objId);
				$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
				switch ($extension) {
					case "mp3":
					case "was":
					case "opus":
					case "ogg":
					case "oga":
					case "m4a":
						$apiFormat = self::PROP_M_AUDIO;
						$decrementedCredit = 2;
						break;

					case "m4v":
					case "mp4":
					case "ogv":
					case "avi":
					case "webm":
						$apiFormat = self::PROP_M_VIDEO;
						$decrementedCredit = 3;
						break;

					default:
						$apiFormat = "";
						$decrementedCredit = 0;
				}
				$apiUrl = ilNolejMediaSelectorGUI::getSignedUrl($objId, true);
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
			$tpl->setContent($form->getHTML() . $js);
			return;
		}

		if (!$apiFormat || $apiFormat == "") {
			ilUtil::sendFailure($this->plugin->txt("err_media_format_unknown"), true);
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML() . $js);
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
			$tpl->setContent($form->getHTML() . $js);
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
			. "VALUES (%s, %s, %s, %s, %s, %s, %s);",
			array(
				"integer",
				"integer",
				"text",
				"text",
				"text",
				"text",
				"text"
			),
			array(
				self::STATUS_CREATION_PENDING,
				$decrementedCredit,
				$apiUrl,
				$apiFormat,
				ilUtil::tf2yn($apiAutomaticMode),
				$apiLanguage,
				$result->id
			)
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
	 * It returns an editable form if the transcription has
	 * to be validated, otherwise it returns a static info screen.
	 * 
	 * @return ilPropertyFormGUI|ilInfoScreenGUI
	 */
	public function initAnalysisForm()
	{
		$status = $this->status;

		/**
		 * Module title
		 * - $title: Title returned from transcription;
		 * - $objTitle: Current module title.
		 */
		$title = $this->gui_obj->object->getDocumentTitle();
		$objTitle = $this->gui_obj->object->getTitle();

		if ($status == self::STATUS_ANALISYS) {
			$form = new ilPropertyFormGUI();
			$form->setTitle($this->plugin->txt("obj_xnlj"));

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
			 */
			$txt = new ilTextAreaInputGUI($this->plugin->txt("prop_transcription"), self::PROP_M_TEXT);
			$txt->setRequired(true);
			$txt->setRows(50);
			$txt->setMaxNumOfChars(50000);
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
			$txt->setValue($this->readDocumentFile("transcription.htm"));
			$form->addItem($txt);

			$form->addCommandButton(self::CMD_ANALYZE, $this->plugin->txt("cmd_" . self::CMD_ANALYZE));
			$form->setFormAction($this->ctrl->getFormAction($this));
			return $form;
		}

		$info = new ilInfoScreenGUI($this);
		$info->addSection($this->plugin->txt("obj_xnlj"));
		$info->addProperty(
			$this->plugin->txt("prop_" . self::PROP_TITLE),
			"<h1>" . $objTitle . "</h1>"
		);
		$info->addProperty(
			$this->plugin->txt("prop_transcription"),
			$this->readDocumentFile("transcription.htm")
		);
		
		return $info;
	}

	/**
	 * Read a file of the current document, if exists.
	 * 
	 * @param string $filename the name of the file.
	 * @return string|false return the content if the file exists,
	 *   false otherwise.
	 */
	protected function readDocumentFile($filename)
	{
		$dataDir = $this->gui_obj->object->getDataDir();
		return file_get_contents($dataDir . "/" . $filename);
	}

	/**
	 * Get and save the content of a Nolej file
	 * 
	 * @param string $pathname the "id" of Nolej file
	 * @param string $saveAs the name of the file to be saved as
	 * @param bool $forceDownload if false check if the file already exists
	 * 
	 * @return bool returns true on success, false on failure.
	 */
	protected function getNolejContent($pathname, $saveAs, $forceDownload = false)
	{
		$documentId = $this->gui_obj->object->getDocumentId();
		$dataDir = $this->gui_obj->object->getDataDir();
		$filepath = $dataDir . "/" . $saveAs;

		$api_key = $this->plugin->getConfig("api_key", "");
		$api = new ilNolejAPI($api_key);

		if (!$forceDownload && is_file($filepath)) {
			return true;
		}

		$result = $api->get(
			sprintf("/documents/%s/%s", $documentId, $pathname),
			false
		);
		return $this->writeDocumentFile($saveAs, $result);
	}

	/**
	 * Put the content of a file to Nolej
	 * 
	 * @param string $pathname the "id" of Nolej file
	 * @param string $filename the name of the file on disk
	 * 
	 * @return bool true on success, false on failure
	 */
	protected function putNolejContent($pathname, $filename)
	{
		$documentId = $this->gui_obj->object->getDocumentId();
		$content = $this->readDocumentFile($filename);
		if (!$content) {
			return false;
		}

		$api_key = $this->plugin->getConfig("api_key", "");
		$api = new ilNolejAPI($api_key);

		$result = $api->put(
			sprintf("/documents/%s/%s", $documentId, $pathname),
			false
		);
		return true;
	}

	/**
	 * Write a file of the current document, and create the
	 * parent directory if it doesn't exists.
	 * 
	 * @param string $filename the name of the file.
	 * @param string $content the content of the file.
	 * 
	 * @return bool returns true on success, false on failure.
	 */
	protected function writeDocumentFile($filename, $content)
	{
		$dataDir = $this->gui_obj->object->getDataDir();
		if (!is_dir($dataDir)) {
			mkdir($dataDir, 0777, true);
		}

		return file_put_contents(
			$dataDir . "/" . $filename,
			$content
		) !== false;
	}

	/**
	 * Update the status of the document
	 * 
	 * @param int $newStatus
	 * @return void
	 */
	protected function updateDocumentStatus($newStatus)
	{
		$documentId = $this->gui_obj->object->getDocumentId();
		$this->db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DOC
			. " SET status = %s WHERE document_id = %s;",
			["integer", "text"],
			[$newStatus, $documentId]
		);
	}

	/**
	 * Download the transctiption of the analyzed media
	 * 
	 * @return bool
	 */
	protected function downloadTranscription()
	{
		$documentId = $this->gui_obj->object->getDocumentId();
		$status = $this->status;

		if ($status < self::STATUS_ANALISYS) {
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

		$success = $this->writeDocumentFile(
			"transcription.htm",
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
		$this->initTabs(self::TAB_ANALYSIS);

		$dataDir = $this->gui_obj->object->getDataDir();
		$status = $this->status;

		if ($status < self::STATUS_ANALISYS) {
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
		$this->initTabs(self::TAB_ANALYSIS);

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

		if (
			!is_object($result) ||
			!property_exists($result, "result") ||
			!is_string($result->result) ||
			$result->result != "ok"
		) {
			return;
		}

		$this->updateDocumentStatus(self::STATUS_ANALISYS_PENDING);

		$ass = new NolejActivity($documentId, $DIC->user()->getId(), "analysis");
		$ass->withStatus("ok")
			->withCode(0)
			->withErrorMessage("")
			->withConsumedCredit(0)
			->store();

		ilUtil::sendSuccess($this->plugin->txt("action_analysis"), true);
		$this->ctrl->redirect($this, self::CMD_REVISION);
	}

	/**
	 * Init and activate tabs
	 */
	protected function initRevisionSubTabs($active_subtab = null)
	{
		global $tpl;

		$this->initTabs(self::TAB_REVIEW);

		$tpl->setRightContent("hello");

		$this->tabs->addSubTab(
			self::SUBTAB_SUMMARY,
			$this->plugin->txt(self::SUBTAB_SUMMARY),
			$this->ctrl->getLinkTarget($this, self::CMD_SUMMARY)
		);

		$this->tabs->addSubTab(
			self::SUBTAB_QUESTIONS,
			$this->plugin->txt(self::SUBTAB_QUESTIONS),
			$this->ctrl->getLinkTarget($this, self::CMD_QUESTIONS)
		);

		$this->tabs->addSubTab(
			self::SUBTAB_CONCEPTS,
			$this->plugin->txt(self::SUBTAB_CONCEPTS),
			$this->ctrl->getLinkTarget($this, self::CMD_CONCEPTS)
		);

		$tpl->setTitle(
			sprintf(
				"%s: %s",
				$this->plugin->txt("plugin_title"),
				$this->plugin->txt(self::TAB_REVIEW)
			),
			false
		);

		switch ($active_subtab) {
			case self::SUBTAB_QUESTIONS:
			case self::SUBTAB_CONCEPTS:
				$this->tabs->activateSubTab($active_subtab);
				break;

			case self::SUBTAB_SUMMARY:
			default:
				$this->tabs->activateSubTab(self::SUBTAB_SUMMARY);
		}

		// $tpl->setDescription($this->plugin->txt("plugin_description"));
	}

	public function revision()
	{
		$dataDir = $this->gui_obj->object->getDataDir();
		$status = $this->status;

		if ($status < self::STATUS_REVISION) {
			ilUtil::sendInfo($this->plugin->txt("err_transcription_not_ready"));
			return;
		}

		if (!file_exists($dataDir . "/transcription.htm")) {
			$downloadSuccess = $this->downloadTranscription();
			if (!$downloadSuccess) {
				return;
			}
		}

		$this->summary();
	}

	/**
	 * @param bool $a_use_post Set value from POST, if false load summary file
	 * @param bool $a_disabled Set all inputs disabled
	 * 
	 * @return ilPropertyFormGUI
	 */
	protected function initSummaryForm($a_use_post = false, $a_disabled = false)
	{
		$form = new ilPropertyFormGUI();

		$this->getNolejContent("summary", "summary.json");
		$json = $this->readDocumentFile("summary.json");
		if (!$json) {
			ilUtil::sendFailure("err_summary_file");
			return $form;
		}

		$summary = json_decode($json);

		/**
		 * Summary -> summary
		 */
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->plugin->txt("review_summary"));
		$form->addItem($section);
		$length = $a_use_post ? $_POST["summary_count"] : count($summary->summary);
		$length_input = new ilHiddenInputGUI("summary_count");
		$length_input->setValue($length);
		$form->addItem($length_input);
		for($i = 0; $i < $length; $i++) {
			$title = new ilTextInputGUI(
				$this->plugin->txt("prop_" . self::PROP_TITLE),
				sprintf("summary[%d]['title']", $i)
			);
			if ($a_use_post) {
				$title->setValuesByPost();
			} else {
				$title->setValue($summary->summary[$i]->title);
			}
			$form->addItem($title);

			$txt = new ilTextAreaInputGUI(
				$this->plugin->txt("prop_" . self::PROP_M_TEXT),
				sprintf("summary[%d]['text']", $i)
			);
			if ($a_use_post) {
				$txt->setValuesByPost();
			} else {
				$txt->setValue($summary->summary[$i]->text);
			}
			$txt->setRows(6);
			$form->addItem($txt);
		}

		/**
		 * Summary -> abstract
		 */
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->plugin->txt("summary_abstract"));
		$form->addItem($section);
		$txt = new ilTextAreaInputGUI("", "abstract");
		if ($a_use_post) {
			$txt->setValuesByPost();
		} else {
			$txt->setValue($summary->abstract);
		}
		$txt->setRows(10);
		$form->addItem($txt);

		/**
		 * Summary -> keypoints
		 */
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->plugin->txt("summary_keypoints"));
		$form->addItem($section);
		$length = $a_use_post ? $_POST["keypoints_count"] : count($summary->keypoints);
		$length_input = new ilHiddenInputGUI("keypoints_count");
		$length_input->setValue($length);
		$form->addItem($length_input);
		for($i = 0; $i < $length; $i++) {
			$txt = new ilTextAreaInputGUI(
				"",
				sprintf("keypoints[%d]", $i)
			);
			if ($a_use_post) {
				$txt->setValuesByPost();
			} else {
				$txt->setValue($summary->keypoints[$i]);
			}
			$txt->setRows(2);
			$form->addItem($txt);
		}

		$form->addCommandButton(self::CMD_SUMMARY_SAVE, $this->plugin->txt("cmd_save"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}

	public function summary()
	{
		global $tpl;
		$this->initRevisionSubTabs(self::SUBTAB_SUMMARY);
		$form = $this->initSummaryForm();

		$tpl->setContent($form->getHTML());
	}

	public function saveSummary()
	{
		$form = $this->initSummaryForm(true);

	}

	public function questions()
	{
		global $tpl;
		$this->initRevisionSubTabs(self::SUBTAB_QUESTIONS);
		// TODO
	}

	public function saveQuestions()
	{
		//
	}

	public function concepts()
	{
		global $tpl;
		$this->initRevisionSubTabs(self::SUBTAB_CONCEPTS);
		// TODO
	}

	public function saveConcepts()
	{
		//
	}

	public function review()
	{
		global $tpl;
		$this->initTabs(self::TAB_REVIEW);

		// TODO
	}

	public function activities()
	{
		global $tpl;
		$this->initTabs(self::TAB_ACTIVITIES);

		// TODO
	}

	public function generate()
	{
		global $tpl;
		$this->initTabs(self::TAB_ACTIVITIES);

		// TODO
	}
}
