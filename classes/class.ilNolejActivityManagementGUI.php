<?php

/**
 * Plugin configuration class
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
	const PROP_MEDIA_TYPE = "media_type";
	const PROP_M_WEB = "web";
	const PROP_M_URL = "url";
	const PROP_M_MOB = "mob";
	const PROP_M_AUDIO = "audio";
	const PROP_M_VIDEO = "video";
	const PROP_M_DOC = "document";
	const PROP_M_TEXT = "freetext";
	const PROP_M_TEXTAREA = "textarea";
	const PROP_UP_MOB = "upload_mob";
	const PROP_UP_AUDIO = "upload_audio";
	const PROP_UP_VIDEO = "upload_video";
	const PROP_UP_DOC = "upload_document";

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

		$tpl->printToStdout();

		return true;
	}

	/**
	 * Init and activate tabs
	 */
	protected function initSubTabs($active_subtab = null)
	{
		global $tpl;

		// TODO: icons that follow the status

		$this->tabs->addSubTab(
			self::SUBTAB_CREATION,
			$this->plugin->txt("subtab_" . self::SUBTAB_CREATION),
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
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("obj_xnlj"));

		$status = $this->gui_obj->object->getDocumentStatus();
		switch ($status) {
			case "idle":
				$title = new ilTextInputGUI($this->plugin->txt("prop_" . self::PROP_TITLE), self::PROP_TITLE);
				$title->setRequired(true);
				$form->addItem($title);

				$mediaType = new ilRadioGroupInputGUI($this->plugin->txt("prop_" . self::PROP_MEDIA_TYPE), self::PROP_MEDIA_TYPE);
				$mediaType->setRequired(true);
				$form->addItem($mediaType);
				// Available: web, audio, video, document, freetext.

				$opt = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_WEB), self::PROP_M_WEB);
				$opt->setInfo($this->plugin->txt("prop_" . self::PROP_M_WEB . "_info"));
				$mediaType->addOption($opt);

				$url = new ilUriInputGUI($this->plugin->txt("prop_" . self::PROP_M_URL), self::PROP_M_URL);
				$url->setInfo($this->plugin->txt("prop_" . self::PROP_M_URL . "_info"));
				$url->setRequired(true);
				$opt->addSubItem($url);

				$opt = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_MOB), self::PROP_M_MOB);
				$opt->setInfo($this->plugin->txt("prop_" . self::PROP_M_MOB . "_info"));
				$mediaType->addOption($opt);

				$file = new ilFileInputGUI($this->plugin->txt("prop_" . self::PROP_UP_MOB), self::PROP_UP_MOB);
				$file->setInfo($this->plugin->txt("prop_" . self::PROP_UP_MOB . "_info"));
				$opt->addSubItem($file);

				$opt = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_AUDIO), self::PROP_M_AUDIO);
				$opt->setInfo($this->plugin->txt("prop_" . self::PROP_M_AUDIO . "_info"));
				$mediaType->addOption($opt);

				$file = new ilFileInputGUI($this->plugin->txt("prop_" . self::PROP_UP_AUDIO), self::PROP_UP_AUDIO);
				$file->setInfo($this->plugin->txt("prop_" . self::PROP_UP_AUDIO . "_info"));
				$opt->addSubItem($file);

				$opt = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_VIDEO), self::PROP_M_VIDEO);
				$opt->setInfo($this->plugin->txt("prop_" . self::PROP_M_VIDEO . "_info"));
				$mediaType->addOption($opt);

				$file = new ilFileInputGUI($this->plugin->txt("prop_" . self::PROP_UP_VIDEO), self::PROP_UP_VIDEO);
				$file->setInfo($this->plugin->txt("prop_" . self::PROP_UP_VIDEO . "_info"));
				$opt->addSubItem($file);

				$opt = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_DOC), self::PROP_M_DOC);
				$opt->setInfo($this->plugin->txt("prop_" . self::PROP_M_DOC . "_info"));
				$mediaType->addOption($opt);

				$file = new ilFileInputGUI($this->plugin->txt("prop_" . self::PROP_UP_DOC), self::PROP_UP_DOC);
				$file->setInfo($this->plugin->txt("prop_" . self::PROP_UP_DOC . "_info"));
				$opt->addSubItem($file);

				$opt = new ilRadioOption($this->plugin->txt("prop_" . self::PROP_M_TEXT), self::PROP_M_TEXT);
				$opt->setInfo($this->plugin->txt("prop_" . self::PROP_M_TEXT . "_info"));
				$mediaType->addOption($opt);

				$txt = new ilTextAreaInputGUI($this->plugin->txt("prop_" . self::PROP_M_TEXTAREA), self::PROP_M_TEXTAREA);
				$txt->setInfo($this->plugin->txt("prop_" . self::PROP_M_TEXTAREA . "_info"));
				if (ilObjAdvancedEditing::_getRichTextEditor() === "tinymce") {
					$txt->setUseRte(true);
					$txt->setRteTagSet("mini");
					$txt->usePurifier(true);
					$txt->setRTERootBlockElement('');
					$txt->setRTESupport($this->user->getId(), 'frm~', 'frm_post', 'tpl.tinymce_frm_post.js', false, '5.6.0');
					$txt->disableButtons(array(
						'charmap',
						'undo',
						'redo',
						'alignleft',
						'aligncenter',
						'alignright',
						'alignjustify',
						'anchor',
						'fullscreen',
						'cut',
						'copy',
						'paste',
						'pastetext',
						'formatselect'
					));
					$txt->setPurifier(\ilHtmlPurifierFactory::_getInstanceByType('frm_post'));
				}
				$txt->setRequired(true);
				$opt->addSubItem($txt);

				// $mon_num = new ilNumberInputGUI($this->plugin->txt("blog_nav_mode_month_list_num_month"), "nav_list_mon");
				// $mon_num->setInfo($this->plugin->txt("blog_nav_mode_month_list_num_month_info"));
				// $mon_num->setSize(3);
				// $mon_num->setMinValue(1);
				// $opt->addSubItem($mon_num);
				
				break;
			
			case "transcription":
			case "transcription_ready":
			case "analysis":
			case "analysis_ready":
				// TODO
				break;
			
			default:
				// TODO
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

		// TODO
	}

	public function analysis()
	{
		global $tpl;
		$this->initSubTabs(self::SUBTAB_ANALYSIS);

		// TODO
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
