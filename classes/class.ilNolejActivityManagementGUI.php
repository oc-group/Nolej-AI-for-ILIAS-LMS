<?php

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * 
 * @ilCtrl_isCalledBy ilNolejActivityManagementGUI: ilObjNolejGUI
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
	const PROP_M_URL = "url";
	const PROP_M_YT = "youtube";
	const PROP_M_MOB = "mob";
	const PROP_M_FILE = "file";
	const PROP_M_TEXT = "freetext";
	const PROP_M_TEXTAREA = "textarea";
	const PROP_INPUT_MOB = "input_mob";
	const PROP_INPUT_YT = "input_youtube";
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
	}

	/**
	 * Handles all commmands,
	 * $cmd = functionName()
	 */
	public function executeCommand()
	{
		global $tpl;
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : self::CMD_CREATION;

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
				// TODO: check status
				$this->creation();
		}

		return true;
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

		// TODO: icons that follow the status
		// $status = $this->gui_obj->object->getDocumentStatus();
		// glyphicon glyphicon-time
		// glyphicon glyphicon-hand-right
		// glyphicon glyphicon-ok

		$this->tabs->addSubTab(
			self::SUBTAB_CREATION,
			$this->glyphicon("hand-right") . $this->plugin->txt("subtab_" . self::SUBTAB_CREATION),
			$this->ctrl->getLinkTarget($this, self::CMD_CREATION)
		);

		$this->tabs->addSubTab(
			self::SUBTAB_ANALYSIS,
			$this->plugin->txt("subtab_" . self::SUBTAB_ANALYSIS),
			$this->ctrl->getLinkTarget($this, self::CMD_ANALYSIS)
		);

		$this->tabs->addSubTab(
			self::SUBTAB_REVIEW,
			$this->plugin->txt("subtab_" . self::SUBTAB_REVIEW),
			$this->ctrl->getLinkTarget($this, self::CMD_REVIEW)
		);

		$this->tabs->addSubTab(
			self::SUBTAB_ACTIVITIES,
			$this->plugin->txt("subtab_" . self::SUBTAB_ACTIVITIES),
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
	 * @return ilPropertyFormGUI
	 */
	public function initCreationForm()
	{
		global $ilUser, $tpl;

		// $tpl->addJavaScript('./node_modules/tinymce/tinymce.js');

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("obj_xnlj"));

		$status = $this->gui_obj->object->getDocumentStatus();
		// ilUtil::sendInfo($status, true);

		if ($status == "idle") {
			$title = new ilTextInputGUI($this->plugin->txt("prop_" . self::PROP_TITLE), self::PROP_TITLE);
			$title->setInfo($this->plugin->txt("prop_" . self::PROP_TITLE . "_info"));
			$title->setValue($this->gui_obj->object->getTitle());
			$form->addItem($title);

			$mediaSource = new ilRadioGroupInputGUI($this->plugin->txt("prop_" . self::PROP_MEDIA_SRC), self::PROP_MEDIA_SRC);
			$mediaSource->setRequired(true);
			$form->addItem($mediaSource);
			// Available: web, audio, video, document, freetext.

			$mediaWeb = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_WEB), self::PROP_M_WEB);
			$mediaWeb->setInfo($this->plugin->txt("prop_" . self::PROP_M_WEB . "_info"));
			$mediaSource->addOption($mediaWeb);

			$url = new ilUriInputGUI($this->plugin->txt("prop_" . self::PROP_M_URL), self::PROP_M_URL);
			$url->setInfo($this->plugin->txt("prop_" . self::PROP_M_URL . "_info"));
			$url->setRequired(true);
			$mediaWeb->addSubItem($url);

			$mediaMob = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_MOB), self::PROP_M_MOB);
			$mediaMob->setInfo($this->plugin->txt("prop_" . self::PROP_M_MOB . "_info"));
			$mediaSource->addOption($mediaMob);

			$mob = new ilFileInputGUI($this->plugin->txt("prop_" . self::PROP_INPUT_MOB), self::PROP_INPUT_MOB);
			$mob->setInfo($this->plugin->txt("prop_" . self::PROP_INPUT_MOB . "_info"));
			$mob->setRequired(true);
			$mediaMob->addSubItem($mob);

			$mediaYT = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_YT), self::PROP_M_YT);
			$mediaYT->setInfo($this->plugin->txt("prop_" . self::PROP_M_YT . "_info"));
			$mediaSource->addOption($mediaYT);

			$url = new ilUriInputGUI($this->plugin->txt("prop_" . self::PROP_M_URL), self::PROP_INPUT_YT);
			$url->setInfo($this->plugin->txt("prop_" . self::PROP_M_URL . "_info"));
			$url->setRequired(true);
			$mediaYT->addSubItem($url);

			$mediaFile = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_FILE), self::PROP_M_FILE);
			$mediaFile->setInfo($this->plugin->txt("prop_" . self::PROP_M_FILE . "_info"));
			$mediaSource->addOption($mediaFile);

			$file = new ilFileInputGUI($this->plugin->txt("prop_" . self::PROP_INPUT_FILE), self::PROP_INPUT_FILE);
			$file->setInfo($this->plugin->txt("prop_" . self::PROP_INPUT_FILE . "_info") . $this->plugin->txt("prop_file_limits"));
			$file->setRequired(true);
			$file->setSuffixes([
				"mp3", "was", "opus", "ogg", "oga", "m4a", // Audio
				"m4v", "mp4", "ogv", "avi", "webm", // Video
				"pdf", "doc", "docx", "odt" // Documents
			]);
			$mediaFile->addSubItem($file);

			$mediaText = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_TEXT), self::PROP_M_TEXT);
			$mediaText->setInfo($this->plugin->txt("prop_" . self::PROP_M_TEXT . "_info"));
			$mediaSource->addOption($mediaText);

			$txt = new ilTextAreaInputGUI($this->plugin->txt("prop_" . self::PROP_M_TEXTAREA), self::PROP_M_TEXTAREA);
			$txt->setInfo($this->plugin->txt("prop_" . self::PROP_M_TEXTAREA . "_info"));
			$txt->setRequired(true);
			// if (ilObjAdvancedEditing::_getRichTextEditor() === "tinymce") {
			// 	$txt->setUseRte(true);
			// 	$txt->setRteTagSet("mini");
			// 	$txt->usePurifier(true);
			// 	$txt->setRTERootBlockElement('');
			// 	$txt->setRTESupport($ilUser->getId(), 'frm~', 'frm_post', 'tpl.tinymce_frm_post.js', false, '5.6.0');
			// 	$txt->disableButtons(array(
			// 		'charmap',
			// 		'undo',
			// 		'redo',
			// 		'alignleft',
			// 		'aligncenter',
			// 		'alignright',
			// 		'alignjustify',
			// 		'anchor',
			// 		'fullscreen',
			// 		'cut',
			// 		'copy',
			// 		'paste',
			// 		'pastetext',
			// 		'formatselect'
			// 	));
			// 	$txt->setPurifier(\ilHtmlPurifierFactory::_getInstanceByType('frm_post'));
			// }
			$txt->setRequired(true);
			$mediaText->addSubItem($txt);

			$language = new ilSelectInputGUI($this->plugin->txt("prop_" . self::PROP_LANG), self::PROP_LANG);
			$language->setInfo($this->plugin->txt("prop_" . self::PROP_LANG . "_info"));
			$language->setOptions([
				// TODO: add language translation
				"en" => "English",
				// "fr" => "French", // Soon
				// "it" => "Italian" // Soon
			]);
			$language->setRequired(true);
			$form->addItem($language);

			$automaticMode = new ilCheckboxInputGUI($this->plugin->txt("prop_" . self::PROP_AUTOMATIC), self::PROP_AUTOMATIC);
			$automaticMode->setInfo($this->plugin->txt("prop_" . self::PROP_AUTOMATIC . "_info"));
			$automaticMode->setChecked(false);
			$automaticMode->setDisabled(true);
			$form->addItem($automaticMode);

			// $mon_num = new ilNumberInputGUI($this->plugin->txt("blog_nav_mode_month_list_num_month"), "nav_list_mon");
			// $mon_num->setInfo($this->plugin->txt("blog_nav_mode_month_list_num_month_info"));
			// $mon_num->setSize(3);
			// $mon_num->setMinValue(1);
			// $opt->addSubItem($mon_num);
		}

		// $course = new ilSelectInputGUI($this->plugin->txt("prop_" . self::PROP_STATUS), self::PROP_STATUS);

		// $options = $this->object->getPurchasedCourses();

		// $course->setRequired(true);
		// $course->setOptions($options);

		// $course->setInfo($this->plugin->txt("prop_" . self::PROP_STATUS . "_info"));
		// $form->addItem($course);

		// if (count($options) == 0) {
		// 	ilUtil::sendQuestion($this->plugin->txt("err_no_purchased_courses"), true);
		// }

		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->addCommandButton(self::CMD_CREATE, $this->plugin->txt("cmd_create"));

		return $form;
	}

	public function creation()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_CREATION);

		$form = $this->initCreationForm();
		$tpl->setContent($form->getHTML());
	}

	public function create()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_CREATION);

		$form = $this->initCreationForm();

		if (!$form->checkInput()) {
			// input not ok, then
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		$title = $form->getInput(self::PROP_TITLE);
		if ($title != "" && $title != $this->gui_obj->object->getTitle()) {
			// Title chosen by the user, update if different from current title
			$this->gui_obj->object->setTitle($title);
			$this->gui_obj->object->update();
		}

		$mediaSrc = $form->getInput(self::PROP_MEDIA_SRC);
		$language = $form->getInput(self::PROP_LANG);
		$automaticMode = $form->getInput(self::PROP_AUTOMATIC);

		switch ($mediaSrc) {
			case self::PROP_M_WEB:
				// TODO
				$mediaUrl = $form->getInput(self::PROP_M_URL);
				$mediaFormat = "web";
				break;

			case self::PROP_M_MOB:
				// TODO
				$mediaUrl = "";
				$mediaFormat = "";
				break;

			case self::PROP_M_YT:
				// TODO
				$mediaUrl = $form->getInput(self::PROP_INPUT_YT);
				$mediaFormat = "youtube";
				break;

			case self::PROP_M_FILE:
				// TODO
				$mediaUrl = "";
				$mediaFormat = "";
				break;

			case self::PROP_M_TEXT:
				// TODO
				$mediaUrl = "";
				$mediaFormat = "freetext";
				break;
		}

		if (!$mediaUrl || $mediaUrl == "") {
			ilUtil::sendFailure($this->plugin->txt("err_media_url_empty"), true);
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		if (!$mediaFormat || $mediaFormat == "") {
			ilUtil::sendFailure($this->plugin->txt("err_media_format_unknown"), true);
			$form->setValuesByPost();
			$tpl->setContent($form->getHTML());
			return;
		}

		// TODO: send api

		// TODO: insert document in db
		$this->db->manipulateF(
			"INSERT INTO " . ilNolejPlugin::TABLE_DOC
		);

		// Go to creation tab and wait for Nolej to send back the transcription
		// TODO: check automatic mode
		ilUtil::sendInfo("Very very soon", true);
		$this->ctrl->redirect($this, self::CMD_CREATION);
	}

	public function analysis()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_ANALYSIS);

		// TODO
		// ilUtil::getWebspaceDir()."/xxco/
	}

	public function analyze()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_ANALYSIS);

		// TODO
	}

	public function revision()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_REVIEW);

		// TODO
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
