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

	/** @var string */
	protected $documentId;

	/** @var string */
	protected $dataDir;

	/**
	 * @param ilObjNolejGUI|null $gui_obj
	 * @param string|null $documentId
	 */
	public function __construct($gui_obj = null, $documentId = null)
	{
		global $DIC, $tpl;
		$this->ctrl = $DIC->ctrl();
		$this->tabs = $DIC->tabs();
		$this->db = $DIC->database();
		$this->lng = $DIC->language();

		$this->plugin = ilNolejPlugin::getInstance();
		$this->gui_obj = $gui_obj;
		$this->documentId = $gui_obj != null
			? $this->gui_obj->object->getDocumentId()
			: $documentId;
		$this->dataDir = $this->plugin->getPluginDataDir() . $this->documentId;
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
						if ($this->gui_obj != null) {
							$this->$cmd();
						}
						break;

					default:
						// ilUtil::sendQuestion("Next class: $next_class; Cmd: $cmd", true);
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
		 * Set $decrementedCredit (all to 1)
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
						$decrementedCredit = 1;
						break;

					case self::PROP_M_VIDEO:
						$apiFormat = $format;
						$decrementedCredit = 1;
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
						$decrementedCredit = 1;
						break;

					case "m4v":
					case "mp4":
					case "ogv":
					case "avi":
					case "webm":
						$apiFormat = self::PROP_M_VIDEO;
						$decrementedCredit = 1;
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
	public function readDocumentFile($filename)
	{
		return file_get_contents($this->dataDir . "/" . $filename);
	}

	/**
	 * Get and save the content of a Nolej file
	 * 
	 * @param string $pathname the "id" of Nolej file
	 * @param string|null $saveAs the name of the file to be saved as
	 * @param bool $forceDownload if false check if the file already exists
	 * @param mixed $withData
	 * @param bool $encode input's data
	 * 
	 * @return bool|string return true on success, false on failure. If $saveAs
	 * is null, then the content is returned as string.
	 */
	public function getNolejContent(
		$pathname,
		$saveAs = null,
		$forceDownload = false,
		$withData = array(),
		$encode = false
	) {
		$filepath = $this->dataDir . "/" . $saveAs;

		$api_key = $this->plugin->getConfig("api_key", "");
		$api = new ilNolejAPI($api_key);

		if (
			$saveAs != null &&
			!$forceDownload &&
			is_file($filepath)
		) {
			return true;
		}

		$result = $api->get(
			sprintf("/documents/%s/%s", $this->documentId, $pathname),
			$withData,
			$encode,
			false
		);

		return $saveAs == null
			? $result
			: $this->writeDocumentFile($saveAs, $result);
	}

	/**
	 * Put the content of a file to Nolej
	 * 
	 * @param string $pathname the "id" of Nolej file
	 * @param string $filename the name of the file on disk
	 * 
	 * @return bool true on success, false on failure
	 */
	public function putNolejContent($pathname, $filename)
	{
		$content = $this->readDocumentFile($filename);
		if (!$content) {
			return false;
		}

		$api_key = $this->plugin->getConfig("api_key", "");
		$api = new ilNolejAPI($api_key);

		$result = $api->put(
			sprintf("/documents/%s/%s", $this->documentId, $pathname),
			$content
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
	public function writeDocumentFile($filename, $content)
	{
		if (!is_dir($this->dataDir)) {
			mkdir($this->dataDir, 0777, true);
		}

		return file_put_contents(
			$this->dataDir . "/" . $filename,
			$content
		) !== false;
	}

	/**
	 * Update the status of the document
	 * 
	 * @param int $newStatus
	 * @return void
	 */
	public function updateDocumentStatus($newStatus)
	{
		$this->db->manipulateF(
			"UPDATE " . ilNolejPlugin::TABLE_DOC
			. " SET status = %s WHERE document_id = %s;",
			["integer", "text"],
			[$newStatus, $this->documentId]
		);
	}

	/**
	 * Download the transctiption of the analyzed media
	 * 
	 * @return bool
	 */
	protected function downloadTranscription()
	{
		$status = $this->status;

		if ($status < self::STATUS_ANALISYS) {
			// Transctiption is not ready!
			ilUtil::sendFailure($this->plugin->txt("err_transcription_not_ready"));
			return false;
		}

		$api_key = $this->plugin->getConfig("api_key", "");
		$api = new ilNolejAPI($api_key);

		$result = $api->get(
			sprintf("/documents/%s/transcription", $this->documentId)
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
			[$title, $this->documentId]
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

		$status = $this->status;

		if ($status < self::STATUS_ANALISYS) {
			ilUtil::sendInfo($this->plugin->txt("err_transcription_not_ready"));
			return;
		}

		if (!file_exists($this->dataDir . "/transcription.htm")) {
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

		$apiAutomaticMode = $this->gui_obj->object->getDocumentAutomaticMode();
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

		$url = ILIAS_HTTP_PATH . substr(ilWACSignedPath::signFile($this->dataDir . "/transcription.htm"), 1);
		$result = $api->put(
			sprintf("/documents/%s/transcription", $this->documentId),
			[
				"s3URL" => $url,
				"automaticMode" => $apiAutomaticMode
			],
			true,
			true
		);

		if (
			!is_object($result) ||
			!property_exists($result, "result") ||
			!is_string($result->result) ||
			!(
				$result->result == "\"ok\"" ||
				$result->result == "ok"
			)
		) {
			ilUtil::sendFailure("An error occurred: " . print_r($result, true));
			return;
		}

		$this->updateDocumentStatus(self::STATUS_ANALISYS_PENDING);

		$ass = new NolejActivity($this->documentId, $DIC->user()->getId(), "analysis");
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

		if ($this->status == self::STATUS_REVISION) {
			$toolbar = new ilToolbarGUI();

			$toolbar->addText($this->plugin->txt("cmd_review_info"));
			$toolbar->addButton(
				$this->plugin->txt("cmd_review"),
				$this->ctrl->getLinkTarget($this, self::CMD_REVIEW)
			);

			$tpl->setRightContent($toolbar->getHTML());
		}

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
		$status = $this->status;

		$this->initTabs(self::TAB_REVIEW);

		if ($status < self::STATUS_ANALISYS) {
			ilUtil::sendInfo($this->plugin->txt("err_transcription_not_ready"));
			return;
		}

		if ($status < self::STATUS_REVISION) {
			ilUtil::sendInfo($this->plugin->txt("err_analysis_not_ready"));
			return;
		}

		if (!file_exists($this->dataDir . "/transcription.htm")) {
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
		$length = count($summary->summary);
		$length_input = new ilHiddenInputGUI("summary_count");
		$length_input->setValue($length);
		$form->addItem($length_input);
		for($i = 0; $i < $length; $i++) {
			$title = new ilTextInputGUI(
				$this->plugin->txt("prop_" . self::PROP_TITLE),
				sprintf("summary_%d_title", $i)
			);
			$form->addItem($title);

			$txt = new ilTextAreaInputGUI(
				$this->plugin->txt("prop_" . self::PROP_M_TEXT),
				sprintf("summary_%d_text", $i)
			);
			$txt->setRows(6);
			$form->addItem($txt);
			
			if ($a_use_post) {
				$txt->setValueByArray($_POST);
				$title->setValueByArray($_POST);
			} else {
				$txt->setValue($summary->summary[$i]->text);
				$title->setValue($summary->summary[$i]->title);
			}
			
		}

		/**
		 * Summary -> abstract
		 */
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->plugin->txt("summary_abstract"));
		$form->addItem($section);
		$txt = new ilTextAreaInputGUI("", "abstract");
		if ($a_use_post) {
			$txt->setValueByArray($_POST);
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
		$length = count($summary->keypoints);
		$length_input = new ilHiddenInputGUI("keypoints_count");
		$length_input->setValue($length);
		$form->addItem($length_input);
		for($i = 0; $i < $length; $i++) {
			$txt = new ilTextAreaInputGUI(
				"",
				sprintf("keypoints_%d", $i)
			);
			if ($a_use_post) {
				$txt->setValueByArray($_POST);
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
		global $tpl;
		$form = $this->initSummaryForm(true);
		if (!$form->checkInput()) {
			// input not ok, then
			$this->initRevisionSubTabs(self::SUBTAB_SUMMARY);
			$tpl->setContent($form->getHTML());
			return;
		}

		$summary = [
			"summary" => [],
			"abstract" => "",
			"keypoints" => []
		];

		$length = $form->getInput("summary_count");
		for ($i = 0; $i < $length; $i++) {
			$title = $form->getInput(sprintf("summary_%d_title", $i));
			$txt = $form->getInput(sprintf("summary_%d_text", $i));
			if (!empty($title) && !empty($txt)) {
				$summary["summary"][] = [
					"title" => $title,
					"text" => $txt
				];
			}
		}

		$summary["abstract"] = $form->getInput("abstract");

		$length = $form->getInput("keypoints_count");
		for ($i = 0; $i < $length; $i++) {
			$txt = $form->getInput(sprintf("keypoints_%d", $i));
			if (!empty($txt)) {
				$summary["keypoints"][] = $txt;
			}
		}

		$success = $this->writeDocumentFile("summary.json", json_encode($summary));
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_summary_save"));
			$this->summary();
			return;
		}

		$success = $this->putNolejContent("summary", "summary.json");
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_summary_put"));
		} else {
			ilUtil::sendSuccess($this->plugin->txt("summary_saved"));
		}
		$this->summary();
	}

	/**
	 * @param bool $a_use_post Set value from POST, if false load questions file
	 * @param bool $a_disabled Set all inputs disabled
	 * 
	 * @return ilPropertyFormGUI
	 */
	protected function initQuestionsForm($a_use_post = false, $a_disabled = false)
	{
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("review_questions"));

		$this->getNolejContent("questions", "questions.json");
		$json = $this->readDocumentFile("questions.json");
		if (!$json) {
			ilUtil::sendFailure("err_questions_file");
			return $form;
		}

		$questions = json_decode($json);
		$questions = $questions->questions;

		$length = count($questions);
		$length_input = new ilHiddenInputGUI("questions_count");
		$length_input->setValue($length);
		$form->addItem($length_input);
		for($i = 0; $i < $length; $i++) {
			$section = new ilFormSectionHeaderGUI();
			$section->setTitle(sprintf($this->plugin->txt("questions_n"), $i + 1));
			$form->addItem($section);

			$id = new ilHiddenInputGUI(sprintf("question_%d_id", $i));
			$id->setValue($questions[$i]->id);
			$form->addItem($id);

			$question = new ilTextAreaInputGUI(
				$this->plugin->txt("questions_question"),
				sprintf("question_%d_question", $i)
			);
			$question->setRows(3);
			$form->addItem($question);

			$questionType = new ilHiddenInputGUI(sprintf("question_%d_type", $i));
			$questionType->setValue($questions[$i]->question_type);
			$form->addItem($questionType);

			$questionTypeLabel = new ilNonEditableValueGUI(
				$this->plugin->txt("questions_question_type"),
				sprintf("question_%d_type_label", $i)
			);
			$questionTypeLabel->setValue(
				$this->plugin->txt("questions_type_" . $questions[$i]->question_type)
			);
			$form->addItem($questionTypeLabel);

			$enable = new ilCheckBoxInputGUI(
				$this->plugin->txt("questions_enable"),
				sprintf("question_%d_enable", $i)
			);
			$form->addItem($enable);

			$answer = new ilTextAreaInputGUI(
				$this->plugin->txt("questions_answer"),
				sprintf("question_%d_answer", $i)
			);
			$answer->setRows(3);
			$enable->addSubItem($answer);

			$distractorsLength = count($questions[$i]->distractors);
			$distractors = new ilHiddenInputGUI(sprintf("question_%d_distractors", $i));
			$distractors->setValue($distractorsLength);
			$enable->addSubItem($distractors);
			for ($j = 0; $j < $distractorsLength; $j++) {
				$distractor = new ilTextAreaInputGUI(
					$j == 0 ? $this->plugin->txt("questions_distractors") : "",
					sprintf("question_%d_distractor_%d", $i, $j)
				);
				$enable->addSubItem($distractor);
				if ($a_use_post) {
					$distractor->setValueByArray($_POST);
				} else {
					$distractor->setValue($questions[$i]->distractors[$j]);
				}
			}

			$useForGrading = new ilCheckBoxInputGUI(
				$this->plugin->txt("questions_use_for_grading"),
				sprintf("question_%d_ufg", $i)
			);
			$enable->addSubItem($useForGrading);

			if ($a_use_post) {
				$enable->setValueByArray($_POST);
				$answer->setValueByArray($_POST);
				$useForGrading->setValueByArray($_POST);
				$question->setValueByArray($_POST);
			} else {
				$enable->setChecked($questions[$i]->enable);
				$answer->setValue($questions[$i]->answer);
				$useForGrading->setChecked($questions[$i]->use_for_grading);
				$question->setValue($questions[$i]->question);
			}
		}

		$form->addCommandButton(self::CMD_QUESTIONS_SAVE, $this->plugin->txt("cmd_save"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}

	public function questions()
	{
		global $tpl;
		$this->initRevisionSubTabs(self::SUBTAB_QUESTIONS);
		$form = $this->initQuestionsForm();

		$tpl->setContent($form->getHTML());
	}

	public function saveQuestions()
	{
		global $tpl;
		$form = $this->initQuestionsForm(true);
		if (!$form->checkInput()) {
			// input not ok, then
			$this->initRevisionSubTabs(self::SUBTAB_QUESTIONS);
			$tpl->setContent($form->getHTML());
			return;
		}

		$questions = [];

		$length = $form->getInput("questions_count");
		for ($i = 0; $i < $length; $i++) {
			$id = $form->getInput(sprintf("question_%d_id", $i));
			$enable = (bool) $form->getInput(sprintf("question_%d_enable", $i));
			$answer = $form->getInput(sprintf("question_%d_answer", $i));
			$useForGrading = (bool) $form->getInput(sprintf("question_%d_ufg", $i));
			$question = $form->getInput(sprintf("question_%d_question", $i));
			$questionType = $form->getInput(sprintf("question_%d_type", $i));
			$distractorsLength = $form->getInput(sprintf("question_%d_distractors", $i));
			$distractors = [];
			for ($j = 0; $j < $distractorsLength; $j++) {
				$distractor = $form->getInput(sprintf("question_%d_distractor_%d", $i, $j));
				if (!empty($distractor)) {
					$distractors[] = $distractor;
				}
			}
			$selectedDistractor = "";
			if (!empty($id)) {
				$questions[] = [
					"id" => $id,
					"explanation" => false,
					"enable" => $enable,
					"answer" => $answer,
					"use_for_grading" => $useForGrading,
					"question" => $question,
					"question_type" => $questionType,
					"distractors" => $distractors,
					"selected_distractor" => $selectedDistractor
				];
			}
		}

		$success = $this->writeDocumentFile(
			"questions.json",
			json_encode(["questions" => $questions])
		);
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_questions_save"));
			$this->questions();
			return;
		}

		$success = $this->putNolejContent("questions", "questions.json");
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_questions_put"));
		} else {
			ilUtil::sendSuccess($this->plugin->txt("questions_saved"));
		}
		$this->questions();
	}

	/**
	 * @param bool $a_use_post Set value from POST, if false load concepts file
	 * @param bool $a_disabled Set all inputs disabled
	 * 
	 * @return ilPropertyFormGUI
	 */
	protected function initConceptsForm($a_use_post = false, $a_disabled = false)
	{
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("review_concepts"));

		$this->getNolejContent("concepts", "concepts.json");
		$json = $this->readDocumentFile("concepts.json");
		if (!$json) {
			ilUtil::sendFailure("err_concepts_file");
			return $form;
		}

		$concepts = json_decode($json);
		$concepts = $concepts->concepts;

		$length = count($concepts);
		$length_input = new ilHiddenInputGUI("concepts_count");
		$length_input->setValue($length);
		$form->addItem($length_input);
		for($i = 0; $i < $length; $i++) {
			$section = new ilFormSectionHeaderGUI();
			$section->setTitle(sprintf($this->plugin->txt("concepts_n"), $i + 1));
			$form->addItem($section);

			$id = new ilHiddenInputGUI(sprintf("concept_%d_id", $i));
			$id->setValue($concepts[$i]->id);
			$form->addItem($id);

			$label = new ilNonEditableValueGUI(
				$this->plugin->txt("concepts_label"),
				sprintf("concept_%d_label", $i)
			);
			$label->setValue($concepts[$i]->concept->label);
			$form->addItem($label);

			$enable = new ilCheckBoxInputGUI(
				$this->plugin->txt("concepts_enable"),
				sprintf("concept_%d_enable", $i)
			);
			$form->addItem($enable);

			$definition = new ilTextAreaInputGUI(
				$this->plugin->txt("concepts_definition"),
				sprintf("concept_%d_definition", $i)
			);
			$definition->setRows(4);
			$enable->addSubItem($definition);

			$availableGames = $concepts[$i]->concept->available_games;
			$useForGaming = new ilCheckBoxInputGUI(
				$this->plugin->txt("concepts_use_for_gaming"),
				sprintf("concept_%d_gaming", $i)
			);

			$useForCW = new ilCheckBoxInputGUI(
				$this->plugin->txt("concepts_use_for_cw"),
				sprintf("concept_%d_cw", $i)
			);

			$useForDTW = new ilCheckBoxInputGUI(
				$this->plugin->txt("concepts_use_for_dtw"),
				sprintf("concept_%d_dtw", $i)
			);

			$useForFTW = new ilCheckBoxInputGUI(
				$this->plugin->txt("concepts_use_for_ftw"),
				sprintf("concept_%d_ftw", $i)
			);

			if ($availableGames != null && is_array($availableGames) && count($availableGames) > 0) {
				$enable->addSubItem($useForGaming);

				if (in_array("cw", $concepts[$i]->concept->available_games)) {
					$useForGaming->addSubItem($useForCW);
				}

				if (in_array("dtw", $concepts[$i]->concept->available_games)) {
					$useForGaming->addSubItem($useForDTW);
				}

				if (in_array("ftw", $concepts[$i]->concept->available_games)) {
					$useForGaming->addSubItem($useForFTW);
				}
			}

			$useForPractice = new ilCheckBoxInputGUI(
				$this->plugin->txt("concepts_use_for_practice"),
				sprintf("concept_%d_practice", $i)
			);
			$enable->addSubItem($useForPractice);

			// TODO: remove this
			$useForAssessment = new ilCheckBoxInputGUI(
				$this->plugin->txt("concepts_use_for_assessment"),
				sprintf("concept_%d_assessment", $i)
			);
			$enable->addSubItem($useForAssessment);

			$language = new ilNonEditableValueGUI(
				$this->plugin->txt("concepts_language"),
				sprintf("concept_%d_language", $i)
			);
			$language->setValue($concepts[$i]->concept->language);
			$enable->addSubItem($language);

			$games = new ilHiddenInputGUI(
				sprintf("concept_%d_games", $i)
			);
			$games->setValue(json_encode($concepts[$i]->concept->available_games));
			$enable->addSubItem($games);

			if ($a_use_post) {
				$enable->setValueByArray($_POST);
				$useForCW->setValueByArray($_POST);
				$useForDTW->setValueByArray($_POST);
				$useForFTW->setValueByArray($_POST);
				$useForGaming->setValueByArray($_POST);
				$useForPractice->setValueByArray($_POST);
				$useForAssessment->setValueByArray($_POST);
				$definition->setValueByArray($_POST);
			} else {
				$enable->setChecked($concepts[$i]->enable);
				$useForCW->setChecked($concepts[$i]->use_for_cw);
				$useForDTW->setChecked($concepts[$i]->use_for_dtw);
				$useForFTW->setChecked($concepts[$i]->use_for_ftw);
				$useForGaming->setChecked($concepts[$i]->use_for_gaming);
				$useForPractice->setChecked($concepts[$i]->use_for_practice);
				$useForAssessment->setChecked($concepts[$i]->use_for_assessment);
				$definition->setValue($concepts[$i]->concept->definition);
			}
		}

		$form->addCommandButton(self::CMD_CONCEPTS_SAVE, $this->plugin->txt("cmd_save"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}

	public function concepts()
	{
		global $tpl;
		$this->initRevisionSubTabs(self::SUBTAB_CONCEPTS);
		$form = $this->initConceptsForm();

		$tpl->setContent($form->getHTML());
	}

	public function saveConcepts()
	{
		global $tpl;
		$form = $this->initConceptsForm(true);
		if (!$form->checkInput()) {
			// input not ok, then
			$this->initRevisionSubTabs(self::SUBTAB_CONCEPTS);
			$tpl->setContent($form->getHTML());
			return;
		}

		$concepts = [];

		$length = $form->getInput("concepts_count");
		for ($i = 0; $i < $length; $i++) {
			$id = $form->getInput(sprintf("concept_%d_id", $i));
			$enable = (bool) $form->getInput(sprintf("concept_%d_enable", $i));
			$useForCW = (bool) $form->getInput(sprintf("concept_%d_cw", $i)) ?? false;
			$useForDTW = (bool) $form->getInput(sprintf("concept_%d_dtw", $i)) ?? false;
			$useForFTW = (bool) $form->getInput(sprintf("concept_%d_ftw", $i)) ?? false;
			$useForGaming = (bool) $form->getInput(sprintf("concept_%d_gaming", $i)) ?? false;
			$useForPractice = (bool) $form->getInput(sprintf("concept_%d_practice", $i)) ?? false;
			$useForAssessment = (bool) $form->getInput(sprintf("concept_%d_assessment", $i)) ?? false;
			$label = $form->getInput(sprintf("concept_%d_label", $i));
			$language = $form->getInput(sprintf("concept_%d_language", $i));
			$definition = $form->getInput(sprintf("concept_%d_definition", $i));
			$games = json_decode($form->getInput(sprintf("concept_%d_games", $i)));

			if (!empty($id)) {
				$concepts[] = [
					"id" => $id,
					"enable" => $enable,
					"use_for_cw" => $useForCW,
					"use_for_dtw" => $useForDTW,
					"use_for_ftw" => $useForFTW,
					"use_for_gaming" => $useForGaming,
					"use_for_practice" => $useForPractice,
					"use_for_assessment" => $useForAssessment,
					"concept" => [
						"label" => $label,
						"language" => $language,
						"definition" => $definition,
						"available_games" => $games
					]
				];
			}
		}

		$success = $this->writeDocumentFile(
			"concepts.json",
			json_encode(["concepts" => $concepts])
		);
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_concepts_save"));
			$this->concepts();
			return;
		}

		$success = $this->putNolejContent("concepts", "concepts.json");
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_concepts_put"));
		} else {
			ilUtil::sendSuccess($this->plugin->txt("concepts_saved"));
		}
		$this->concepts();
	}

	public function review()
	{
		$this->updateDocumentStatus(self::STATUS_ACTIVITIES);

		// Go to activities
		$this->activities();
	}

	/**
	 * @param bool $a_use_post Set value from POST, if false load activities file
	 * @param bool $a_disabled Set all inputs disabled
	 * 
	 * @return ilPropertyFormGUI
	 */
	protected function initActivitiesForm($a_use_post = false, $a_disabled = false)
	{
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("activities_settings"));

		$this->getNolejContent("settings", "settings.json");
		$json = $this->readDocumentFile("settings.json");
		if (!$json) {
			ilUtil::sendFailure("err_settings_file");
			return $form;
		}

		$settings = json_decode($json);
		$availableActivities = $settings->avaible_packages ?? [];
		$desiredActivities = $settings->desired_packages ?? [];
		$settings = $settings->settings;

		for ($i = 0, $len = count($availableActivities); $i < $len; $i++) {
			$activity = new ilCheckBoxInputGUI(
				$this->plugin->txt("activities_" . $availableActivities[$i]),
				"activity_" . $availableActivities[$i]
			);
			if ($a_use_post) {
				$activity->setValueByArray($_POST);
			} else if (in_array($availableActivities[$i], $desiredActivities)) {
				$activity->setChecked(true);
			}
			
			switch($availableActivities[$i]) {
				case "glossary":
					$ibook = new ilCheckBoxInputGUI(
						$this->plugin->txt("activities_use_in_ibook"),
						"Glossary_include_IB"
					);
					if ($a_use_post) {
						$ibook->setValueByArray($_POST);
					} else {
						$ibook->setChecked($settings->Glossary_include_IB);
					}
					$activity->addSubItem($ibook);
					break;

				case "summary":
					$ibook = new ilCheckBoxInputGUI(
						$this->plugin->txt("activities_use_in_ibook"),
						"Summary_include_IB"
					);
					if ($a_use_post) {
						$ibook->setValueByArray($_POST);
					} else {
						$ibook->setChecked($settings->Summary_include_IB);
					}
					$activity->addSubItem($ibook);
					break;

				case "findtheword":
					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_ftw_words"),
						"FTW_number_word_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(3, true);
					$number->setMaxValue($settings->FTW_number_word_max, true);
					if ($a_use_post) {
						$number->setValueByArray($_POST);
					} else {
						$number->setValue($settings->FTW_number_word_current);
					}
					$activity->addSubItem($number);
					break;

				case "dragtheword":
					$ibook = new ilCheckBoxInputGUI(
						$this->plugin->txt("activities_use_in_ibook"),
						"DTW_include_IB"
					);

					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_dtw_words"),
						"DTW_number_word_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(3, true);
					$number->setMaxValue($settings->DTW_number_word_max, true);
					if ($a_use_post) {
						$ibook->setValueByArray($_POST);
						$number->setValueByArray($_POST);
					} else {
						$ibook->setChecked($settings->DTW_include_IB);
						$number->setValue($settings->DTW_number_word_current);
					}
					$activity->addSubItem($ibook);
					$activity->addSubItem($number);
					break;

				case "crossword":
					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_cw_words"),
						"CW_number_word_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(3, true);
					$number->setMaxValue($settings->CW_number_word_max, true);
					if ($a_use_post) {
						$number->setValueByArray($_POST);
					} else {
						$number->setValue($settings->CW_number_word_current);
					}
					$activity->addSubItem($number);
					break;

				case "practice":
					$ibook = new ilCheckBoxInputGUI(
						$this->plugin->txt("activities_use_in_ibook"),
						"Practice_include_IB"
					);

					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_practice_flashcards"),
						"Practice_number_flashcard_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(0, true);
					$number->setMaxValue($settings->Practice_number_flashcard_max, true);
					if ($a_use_post) {
						$ibook->setValueByArray($_POST);
						$number->setValueByArray($_POST);
					} else {
						$ibook->setChecked($settings->Practice_include_IB);
						$number->setValue($settings->Practice_number_flashcard_current);
					}
					$activity->addSubItem($ibook);
					$activity->addSubItem($number);
					break;

				case "practiceq":
					$ibook = new ilCheckBoxInputGUI(
						$this->plugin->txt("activities_use_in_ibook"),
						"PracticeQ_include_IB"
					);

					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_practiceq_flashcards"),
						"PracticeQ_number_flashcard_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(0, true);
					$number->setMaxValue($settings->PracticeQ_number_flashcard_max, true);
					if ($a_use_post) {
						$ibook->setValueByArray($_POST);
						$number->setValueByArray($_POST);
					} else {
						$ibook->setChecked($settings->PracticeQ_include_IB);
						$number->setValue($settings->PracticeQ_number_flashcard_current);
					}
					$activity->addSubItem($ibook);
					$activity->addSubItem($number);
					break;

				case "grade":
					$ibook = new ilCheckBoxInputGUI(
						$this->plugin->txt("activities_use_in_ibook"),
						"Grade_include_IB"
					);

					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_grade_questions"),
						"Grade_number_question_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(0, true);
					$number->setMaxValue($settings->Grade_number_question_max, true);
					if ($a_use_post) {
						$ibook->setValueByArray($_POST);
						$number->setValueByArray($_POST);
					} else {
						$ibook->setChecked($settings->Grade_include_IB);
						$number->setValue($settings->Grade_number_question_current);
					}
					$activity->addSubItem($ibook);
					$activity->addSubItem($number);
					break;

				case "gradeq":
					$ibook = new ilCheckBoxInputGUI(
						$this->plugin->txt("activities_use_in_ibook"),
						"GradeQ_include_IB"
					);

					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_gradeq_questions"),
						"GradeQ_number_question_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(0, true);
					$number->setMaxValue($settings->GradeQ_number_question_max, true);
					if ($a_use_post) {
						$ibook->setValueByArray($_POST);
						$number->setValueByArray($_POST);
					} else {
						$ibook->setChecked($settings->GradeQ_include_IB);
						$number->setValue($settings->GradeQ_number_question_current);
					}
					$activity->addSubItem($ibook);
					$activity->addSubItem($number);
					break;

				case "flashcards":
					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_flashcards_flashcards"),
						"Flashcards_number_flashcard_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(0, true);
					$number->setMaxValue($settings->Flashcards_number_flashcard_max, true);
					if ($a_use_post) {
						$number->setValueByArray($_POST);
					} else {
						$number->setValue($settings->Flashcards_number_flashcard_current);
					}
					$activity->addSubItem($number);
					break;

				case "ivideo":
					$number = new ilNumberInputGUI(
						$this->plugin->txt("activities_ivideo_questions"),
						"IV_number_question_perset_current"
					);
					$number->allowDecimals(false);
					$number->setMinValue(0, true);
					$number->setMaxValue($settings->IV_number_question_perset_max, true);
					if ($a_use_post) {
						$number->setValueByArray($_POST);
					} else {
						$number->setValue($settings->IV_number_question_perset_current);
					}
					$activity->addSubItem($number);
					break;
			}

			$form->addItem($activity);
		}

		$form->addCommandButton(self::CMD_GENERATE, $this->plugin->txt("cmd_generate"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}

	public function generate()
	{
		global $tpl;
		$form = $this->initActivitiesForm(true);
		if (!$form->checkInput()) {
			// input not ok, then
			$this->initTabs(self::TAB_ACTIVITIES);
			$tpl->setContent($form->getHTML());
			return;
		}

		$this->initTabs(self::TAB_ACTIVITIES);

		$json = $this->readDocumentFile("settings.json");
		if (!$json) {
			ilUtil::sendFailure("err_settings_file");
			return $form;
		}
		$settings = json_decode($json, true);
		$availableActivities = $settings["avaible_packages"] ?? [];

		$settingsToSave = [
			"settings" => $settings["settings"],
			"avaible_packages" => $availableActivities,
			"desired_packages" => []
		];

		for ($i = 0, $len = count($availableActivities); $i < $len; $i++) {
			$useActivity = (bool) $form->getInput("activity_" . $availableActivities[$i]);
			if (!$useActivity) {
				continue;
			}

			$settingsToSave["desired_packages"][] = $availableActivities[$i];

			switch($availableActivities[$i]) {
				case "glossary":
					$ibook = (bool) $form->getInput("Glossary_include_IB");
					$settingsToSave["settings"]["Glossary_include_IB"] = $ibook;
					break;

				case "summary":
					$ibook = (bool) $form->getInput("Summary_include_IB");
					$settingsToSave["settings"]["Summary_include_IB"] = $ibook;
					break;

				case "findtheword":
					$number = (int) $form->getInput("FTW_number_word_current");
					$settingsToSave["settings"]["FTW_number_word_current"] = $number;
					break;

				case "dragtheword":
					$ibook = (bool) $form->getInput("DTW_include_IB");
					$settingsToSave["settings"]["DTW_include_IB"] = $ibook;
					$number = (int) $form->getInput("DTW_number_word_current");
					$settingsToSave["settings"]["DTW_number_word_current"] = $number;
					break;

				case "crossword":
					$number = (int) $form->getInput("CW_number_word_current");
					$settingsToSave["settings"]["CW_number_word_current"] = $number;
					break;

				case "practice":
					$ibook = (bool) $form->getInput("Practice_include_IB");
					$settingsToSave["settings"]["Practice_include_IB"] = $ibook;
					$number = (int) $form->getInput("Practice_number_flashcard_current");
					$settingsToSave["settings"]["Practice_number_flashcard_current"] = $number;
					break;

				case "practiceq":
					$ibook = (bool) $form->getInput("PracticeQ_include_IB");
					$settingsToSave["settings"]["PracticeQ_include_IB"] = $ibook;
					$number = (int) $form->getInput("PracticeQ_number_flashcard_current");
					$settingsToSave["settings"]["PracticeQ_number_flashcard_current"] = $number;
					break;

				case "grade":
					$ibook = (bool) $form->getInput("Grade_include_IB");
					$settingsToSave["settings"]["Grade_include_IB"] = $ibook;
					$number = (int) $form->getInput("Grade_number_question_current");
					$settingsToSave["settings"]["Grade_number_question_current"] = $number;
					break;

				case "gradeq":
					$ibook = (bool) $form->getInput("GradeQ_include_IB");
					$settingsToSave["settings"]["GradeQ_include_IB"] = $ibook;
					$number = (int) $form->getInput("GradeQ_number_question_current");
					$settingsToSave["settings"]["GradeQ_number_question_current"] = $number;
					break;

				case "flashcards":
					$number = (int) $form->getInput("Flashcards_number_flashcard_current");
					$settingsToSave["settings"]["Flashcards_number_flashcard_current"] = $number;
					break;

				case "ivideo":
					$number = (int) $form->getInput("IV_number_question_perset_current");
					$settingsToSave["settings"]["IV_number_question_perset_current"] = $number;
					break;
			}
		}

		$success = $this->writeDocumentFile(
			"settings.json",
			json_encode($settingsToSave)
		);
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_settings_save"));
			$this->activities();
			return;
		}

		$this->updateDocumentStatus(self::STATUS_ACTIVITIES_PENDING);

		$success = $this->putNolejContent("settings", "settings.json");
		if (!$success) {
			ilUtil::sendFailure($this->plugin->txt("err_settings_put"));
			$this->activities();
			return;
		}

		ilUtil::sendSuccess($this->plugin->txt("activities_generation_start"));
		$this->activities(true);
	}

	/**
	 * @return bool success.
	 */
	public function downloadActivities()
	{
		$h5pDir = $this->dataDir . "/h5p";
		if (!is_dir($h5pDir)) {
			mkdir($h5pDir, 0777, true);
		}

		// Delete previouses h5p files
		$dirIterator = new DirectoryIterator($h5pDir);
		foreach($dirIterator as $item) {
			if (!$item->isDot() && $item->isFile()) {
				unlink($item->getPathname());
			}
		}

		$json = $this->getNolejContent(
			"activities",
			null,
			true,
			["format" => "h5p"],
			true
		);
		if (!$json) {
			return false;
		}
		$activities = json_decode($json, true);
		$activities = $activities->activities;

		foreach ($activities as $activity) {
			// Download activity
			file_put_contents(
				sprintf("%s/%s.h5p", $h5pDir, $activity->activity_name),
				file_get_contents($activity->url)
			);
		}

		return true;
	}

	/**
	 * @param bool $hideInfo if false and the activities are in generation,
	 * show an info box with the appropriate message.
	 */
	public function activities($hideInfo = false)
	{
		global $tpl;
		$status = $this->status;

		$this->initTabs(self::TAB_ACTIVITIES);

		if ($status < self::STATUS_ANALISYS) {
			ilUtil::sendInfo($this->plugin->txt("err_transcription_not_ready"));
			return;
		}

		if ($status < self::STATUS_REVISION) {
			ilUtil::sendInfo($this->plugin->txt("err_analysis_not_ready"));
			return;
		}

		if ($status < self::STATUS_ACTIVITIES) {
			ilUtil::sendInfo($this->plugin->txt("err_review_not_ready"));
			return;
		}

		if (!$hideInfo && $status == self::STATUS_ACTIVITIES_PENDING) {
			ilUtil::sendInfo($this->plugin->txt("activities_generation_start"));
		}

		$form = $this->initActivitiesForm();
		$tpl->setContent($form->getHTML());
	}

}
