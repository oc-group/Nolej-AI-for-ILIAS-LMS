<?php

require_once("./Services/Tracking/classes/class.ilLearningProgress.php");
require_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
require_once("./Services/Tracking/classes/status/class.ilLPStatusPlugin.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejPlugin.php");

use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;

/**
 * @ilCtrl_isCalledBy ilObjNolejGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjNolejGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 * @ilCtrl_Calls ilObjNolejGUI: ilNolejActivityManagementGUI
 * @ilCtrl_Calls ilObjNolejGUI: ilUIPluginRouterGUI
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */
class ilObjNolejGUI extends ilObjectPluginGUI
{
	use Hasher;

	const LP_SESSION_ID = 'xnlj_lp_session_state';

	const CMD_PROPERTIES_EDIT = "editProperties";
	const CMD_PROPERTIES_UPDATE = "updateProperties";
	const CMD_PROPERTIES_SAVE = "saveProperties";
	const CMD_CONTENT_SHOW = "showContent";
	const CMD_STATUS_COMPLETED = "setStatusToCompleted";
	const CMD_STATUS_FAILED = "setStatusToFailed";
	const CMD_STATUS_IN_PROGRESS = "setStatusToInProgress";
	const CMD_STATUS_NOT_ATTEMPTED = "setStatusToNotAttempted";
	const CMD_FILTER_APPLY = "applyFilter";
	const CMD_FILTER_RESET = "resetFilter";
	const CMD_FILTER_USER = "addUserAutoComplete";

	const TAB_PROJECT = "project";
	const TAB_CONTENT = "content";

	const PROP_TITLE = "title";
	const PROP_DESCRIPTION = "description";
	const PROP_STATUS = "status";

	/** @var ilCtrl */
	protected $ctrl;

	/** @var ilTabsGUI */
	protected $tabs;

	/** @var ilTemplate */
	public $tpl;

	/**
	 * Initialisation
	 */
	protected function afterConstructor()
	{
		global $ilCtrl, $ilTabs, $tpl;
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->tpl = $tpl;
	}

	/**
	 * Get type.
	 * @return string
	 */
	final function getType()
	{
		return ilNolejPlugin::PLUGIN_ID;
	}

	/**
	 * Handles all commmands of this class, centralizes permission checks
	 * @param string $cmd
	 */
	function performCommand($cmd)
	{
		global $DIC;

		$nextClass = $this->ctrl->getNextClass();
		switch ($nextClass) {
			case "ilNolejActivityManagementGUI":
				$this->checkPermission("write");
				$activityManagement = new ilNolejActivityManagementGUI($this);
				$this->ctrl->forwardCommand($activityManagement);
				break;

			default:
				switch ($cmd) {
					// Need write permission
					case self::CMD_PROPERTIES_EDIT:
					case self::CMD_PROPERTIES_UPDATE:
					case self::CMD_PROPERTIES_SAVE:
						$this->checkPermission("write");
						$this->$cmd();
						break;
		
					// Need a bound course
					case self::CMD_FILTER_APPLY:
					case self::CMD_FILTER_RESET:
					case self::CMD_FILTER_USER:
						$this->checkPermission("write");
						// if ($this->object->isBound()) {
						// 	$this->$cmd();
						// 	break;
						// }
						$this->showContent();
						break;
		
					// Need read permission
					case self::CMD_CONTENT_SHOW:
					case self::CMD_STATUS_COMPLETED:
					case self::CMD_STATUS_FAILED:
					case self::CMD_STATUS_IN_PROGRESS:
					case self::CMD_STATUS_NOT_ATTEMPTED:
						$this->checkPermission("read");
						$this->$cmd();
						break;
					default:
						$this->showContent();
				}
		}
	}

	/**
	 * After object has been created -> jump to this command
	 * @return string
	 */
	function getAfterCreationCmd()
	{
		return self::CMD_PROPERTIES_EDIT;
	}

	/**
	 * Get standard command
	 * @return string
	 */
	function getStandardCmd()
	{
		return self::CMD_CONTENT_SHOW;
	}

	public function afterSave(ilObject $a_new_object)
	{
		$parent_data = $this->tree->getParentNodeData($a_new_object->getRefId());
		$a_new_object->setPermissions($parent_data["ref_id"]);
		parent::afterSave($a_new_object);
	}

	/**
	 * @return bool
	 */
	protected function supportsExport()
	{
		// Disable import / export for this type of object
		return false;
	}

	/**
	 * @return bool returns true iff this plugin object supports cloning
	 */
	protected function supportsCloning()
	{
		// Disable cloning for this type of object
		return false;
	}

	/**
	 * Set tabs
	 */
	function setTabs()
	{
		global $ilCtrl;

		// tab for the "show content" command
		if ($this->object->hasReadPermission()) {
			$this->tabs->addTab(
				self::TAB_CONTENT,
				$this->txt("tab_" . self::TAB_CONTENT),
				$ilCtrl->getLinkTarget($this, self::CMD_CONTENT_SHOW)
			);
		}

		// standard info screen tab
		$this->addInfoTab();

		// "properties" and "manage licenses" tabs
		if ($this->object->hasWritePermission()) {
			$this->tabs->addTab(
				self::TAB_PROJECT,
				$this->txt("tab_" . self::TAB_PROJECT),
				$ilCtrl->getLinkTarget($this, self::CMD_PROPERTIES_EDIT)
			);

			// if ($this->object->isBound()) {
			// 	$this->tabs->addTab(self::TAB_LICENSES, $this->txt("tab_" . self::TAB_LICENSES), $ilCtrl->getLinkTarget($this, self::CMD_LICENSE_EDIT));
			// }
		}

		// standard permission tab
		$this->addPermissionTab();
		// $this->activateTab();
	}

	/**
	 * Edit Properties. This commands uses the form class to display an input form.
	 */
	protected function editProperties()
	{
		$form = $this->initPropertiesForm();
		$this->addValuesToForm($form);
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function initPropertiesForm() : ilPropertyFormGUI
	{
		$this->tabs->activateTab(self::TAB_PROJECT);

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("obj_xnlj"));

		$title = new ilTextInputGUI($this->plugin->txt("prop_" . self::PROP_TITLE), self::PROP_TITLE);
		$title->setRequired(true);
		$form->addItem($title);

		$description = new ilTextInputGUI($this->plugin->txt("prop_" . self::PROP_DESCRIPTION), self::PROP_DESCRIPTION);
		$form->addItem($description);

		$online = new ilCheckboxInputGUI($this->plugin->txt("prop_" . self::PROP_ONLINE), self::PROP_ONLINE);
		$form->addItem($online);

		$form->setFormAction($this->ctrl->getFormAction($this, self::CMD_PROPERTIES_SAVE));
		$form->addCommandButton(self::CMD_PROPERTIES_SAVE, $this->plugin->txt("cmd_update"));

		return $form;
	}

	/**
	 * @param $form ilPropertyFormGUI
	 */
	protected function addValuesToForm(&$form) : void
	{
		$form->setValuesByArray(array(
			self::PROP_TITLE => $this->object->getTitle(),
			self::PROP_DESCRIPTION => $this->object->getDescription(),
			self::PROP_ONLINE => $this->object->isOnline(),
			// self::PROP_COURSE => $this->object->bound()
		));
	}

	/**
	 *
	 */
	protected function saveProperties() : void
	{
		$form = $this->initPropertiesForm();
		$form->setValuesByPost();
		if($form->checkInput()) {
			$this->fillObject($this->object, $form);
			$this->object->update();
			ilUtil::sendSuccess($this->plugin->txt("update_successful"), true);
			$this->ctrl->redirect($this, self::CMD_PROPERTIES_EDIT);
		}
		$this->tpl->setContent($form->getHTML());
	}

	protected function showContent() : void
	{
		// global $DIC, $ilCtrl, $ilUser;

		// $this->tabs->activateTab(self::TAB_CONTENT);

		// if (!$this->object->isBound()) {
		// 	// This module is not available yet
		// 	if ($this->object->hasWritePermission()) {
		// 		ilUtil::sendFailure($this->plugin->txt("err_not_bound"), true);
		// 	} else {
		// 		ilUtil::sendFailure($this->plugin->txt("err_access_denied"), true);
		// 	}
		// 	return;
		// }

		// if (!$this->object->isLicenseAssignedToUser($ilUser->getId())) {
		// 	ilUtil::sendFailure($this->plugin->txt("err_access_denied"), true);
		// 	return;
		// }

		// $idPage = $this->object->config->getParameterPositive("id_page", $this->object->getLastVisitedPage());
		// $result = $this->object->config->anonymousApi(array(
		// 	"cmd" => "content",
		// 	"id_partner" => $this->getIdPartner(),
		// 	"id_course" => $this->getIdCourse(),
		// 	"id_page" => $idPage
		// ));

		// if (!$result) {
		// 	ilUtil::sendFailure($this->plugin->txt("err_access_denied"), true);
		// 	return;
		// }

		// switch ($result) {
		// 	case "err_response":
		// 	case "err_maintenance":
		// 	case "err_partner_id":
		// 	case "err_course_id":
		// 	case "err_page_id":
		// 	case "err_forbidden":
		// 		ilUtil::sendFailure($this->plugin->txt($result), true);
		// 		return;
		// }

		// if (!$result->url) {
		// 	ilUtil::sendFailure($this->plugin->txt("err_access_denied"), true);
		// }

		// $this->object->updateStatus($idPage);
		// $this->tpl->setContent($this->renderContentAndStructure($result->url, $idPage));

		// // Show TOC
		// $DIC->globalScreen()->tool()->context()->claim()->repository();
		// $DIC->globalScreen()->tool()->context()->current()->addAdditionalData(
		// 	ilLMGSToolProvider::SHOW_TOC_TOOL,
		// 	true
		// );
	}

	public function renderContentAndStructure($url, $currentIdPage)
	{
		$tpl = new ilTemplate("tpl.content.html", true, true, ilNolejPlugin::PLUGIN_DIR);
		$this->tpl->addCss("Services/COPage/css/content.css");
		$this->tpl->addCss(ilObjStyleSheet::getSyntaxStylePath());

		$course = $this->object->lookupDetails();
		if (!$course) {
			$tpl->setVariable("URL", $url);
			return $tpl->get();
		}

		$tpl->setVariable("REF_ID", $this->object->getRefId());

		for ($i = 0, $n = count($course->structure); $i < $n; $i++) {
			// Sections
			$tpl->setCurrentBlock("list_section");
			$tpl->setVariable("STRUCTURE_ID", $i);
			$tpl->setVariable("SECTION_HREF", $this->ctrl->getLinkTargetByClass(
				self::class,
				self::CMD_CONTENT_SHOW
			) . "&id_page=" . $course->structure[$i]->id_page);
			$tpl->setVariable("SECTION_CONTENT", ($i + 1) . " " . $course->structure[$i]->title);

			$completed = 0;
			for ($j = 0, $m = count($course->structure[$i]->pages); $j < $m; $j++) {
				// Pages
				$idPage = $course->structure[$i]->pages[$j]->id_page;
				$title = $course->structure[$i]->pages[$j]->title;
				$tpl->setCurrentBlock("list_page");
				$tpl->setVariable("STRUCTURE_PAGE_ID", $i . "_" . $j);
				$tpl->setVariable("HREF", $this->ctrl->getLinkTargetByClass(
					self::class,
					self::CMD_CONTENT_SHOW
				) . "&id_page=" . $idPage);

				$status = $this->object->getPageStatus($idPage);

				if ($idPage == $currentIdPage) {
					$tpl->setVariable("ICON", $this->buildIcon("scorm/running"));
					$tpl->touchBlock("hl");
					$tpl->setCurrentBlock("list_page");

					// Breadcrumb
					$this->locator->addItem(
						$title,
						$this->ctrl->getLinkTargetByClass(
							self::class,
							self::CMD_CONTENT_SHOW
						) . "&id_page=" . $idPage,
						"",
						$this->object->getRefId(),
						""
					);

					if ($status == 2) {
						$completed++;
					}

					// Page left
					$leftPage = null;
					if ($j > 0) {
						$leftPage = [$i, $j - 1];
					} else if ($i > 0) {
						$leftPage = [$i - 1, count($course->structure[$i]->pages) - 1];
					}
					if ($leftPage) {
						$leftHref = $this->ctrl->getLinkTargetByClass(
							self::class,
							self::CMD_CONTENT_SHOW
						) . "&id_page=" . $course->structure[$leftPage[0]]->pages[$leftPage[1]]->id_page;
						$leftTitle = sprintf(
							"%d %s (%d/%d)",
							$leftPage[0] + 1,
							$course->structure[$leftPage[0]]->pages[$leftPage[1]]->title,
							$leftPage[1] + 1,
							count($course->structure[$leftPage[0]]->pages)
						);

						$tpl->setCurrentBlock("left_top_page");
						$tpl->setVariable("LEFT_HREF", $leftHref);
						$tpl->setVariable("LEFT_TITLE", $leftTitle);
						$tpl->parseCurrentBlock();

						$tpl->setCurrentBlock("left_bottom_page");
						$tpl->setVariable("LEFT_HREF", $leftHref);
						$tpl->setVariable("LEFT_TITLE", $leftTitle);
						$tpl->parseCurrentBlock();
					}

					// Page right
					$rightPage = null;
					if ($j < $m - 1) {
						$rightPage = [$i, $j + 1];
					} else if ($i < $n - 2) {
						$rightPage = [$i + 2, 0];
					}
					if ($rightPage) {
						$rightHref = $this->ctrl->getLinkTargetByClass(
							self::class,
							self::CMD_CONTENT_SHOW
						) . "&id_page=" . $course->structure[$rightPage[0]]->pages[$rightPage[1]]->id_page;
						$rightTitle = sprintf(
							"%d %s (%d/%d)",
							$rightPage[0] + 1,
							$course->structure[$rightPage[0]]->pages[$rightPage[1]]->title,
							$rightPage[1] + 1,
							count($course->structure[$rightPage[0]]->pages)
						);

						$tpl->setCurrentBlock("right_top_page");
						$tpl->setVariable("RIGHT_HREF", $rightHref);
						$tpl->setVariable("RIGHT_TITLE", $rightTitle);
						$tpl->parseCurrentBlock();

						$tpl->setCurrentBlock("right_bottom_page");
						$tpl->setVariable("RIGHT_HREF", $rightHref);
						$tpl->setVariable("RIGHT_TITLE", $rightTitle);
						$tpl->parseCurrentBlock();
					}

					$tpl->setCurrentBlock("list_page");
				} else {
					switch ($status) {
						case 1:
							$tpl->setVariable("ICON", $this->buildIcon("scorm/incomplete", "incomplete"));
							break;

						case 2:
							$tpl->setVariable("ICON", $this->buildIcon("scorm/completed", "completed"));
							$completed++;
							break;

						case 0:
						default:
							$tpl->setVariable("ICON", $this->buildIcon("scorm/not_attempted", "not_attempted"));
							break;
					}
				}

				$tpl->setVariable("CONTENT", $title);

				if ($completed == $i) {
					$tpl->setVariable("SECTION_ICON", $this->buildIcon("scorm/completed", "completed"));
				} else if ($completed == 0) {
					$tpl->setVariable("SECTION_ICON", $this->buildIcon("scorm/not_attempted", "not_attempted"));
				} else {
					$tpl->setVariable("SECTION_ICON", $this->buildIcon("scorm/incomplete", "incomplete"));
				}
				$tpl->parseCurrentBlock();
			}

			$tpl->setCurrentBlock("list_section");
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("URL", $url);
		return $tpl->get();
	}

	/**
	 * @return string
	 */
	public function buildIcon($id, $alt = "")
	{
		return sprintf(
			'<img border="0" align="middle" src="%s" alt="%s" /> ',
			ilUtil::getImagePath($id . ".svg"),
			empty($alt) ? "" : $this->lng->txt($alt)
		);
	}

	/**
	 * Add items to info screen
	 * @param ilInfoScreenGUI $info
	 */
	public function addInfoItems($info)
	{
		global $tpl;

		// if (!$this->object->isBound()) {
		// 	return;
		// }

		$tpl->addCss(ilNolejPlugin::CSS);

		$details = $this->object->lookupDetails();
		if (!$details) {
			return;
		}

		$this->lng->loadLanguageModule('crs');
		$info->addSection($this->lng->txt("crs_general_informations"));

		$info->addProperty($this->plugin->txt("prop_teacher"), $details->teacher);
		$info->addProperty(
			"<img style='max-width: 90%;' src='" . $details->image . "'>",
			nl2br($details->description)
		);
	}

	/**
	 * @param $object ilObjNolej
	 * @param $form ilPropertyFormGUI
	 */
	private function fillObject($object, $form)
	{
		$object->setTitle($form->getInput(self::PROP_TITLE));
		$object->setDescription($form->getInput(self::PROP_DESCRIPTION));
		$object->setOnline($form->getInput(self::PROP_ONLINE));
		// $object->bind($form->getInput(self::PROP_STATUS));
	}

	// public function getIdPartner()
	// {
	// 	return $this->object->getIdPartner();
	// }

	// public function getIdCourse()
	// {
	// 	return $this->object->getIdCourse();
	// }

	/**
	 * Apply filter
	 */
	public function applyFilter()
	{
		// $table = new ilObjNolejLicenseTableGUI($this, self::CMD_LICENSE_EDIT);
		// $table->resetOffset();
		// $table->writeFilterToSession();
		// $this->editLicenses();
	}

	/**
	 * Reset filter
	 */
	public function resetFilter()
	{
		// $table = new ilObjNolejLicenseTableGUI($this, self::CMD_LICENSE_EDIT);
		// $table->resetOffset();
		// $table->resetFilter();
		// $this->editLicenses();
	}

	public function addUserAutoComplete()
	{
		include_once './Services/User/classes/class.ilUserAutoComplete.php';
		$auto = new ilUserAutoComplete();
		// $auto->addUserAccessFilterCallable([$this, 'filterUserIdsByRbacOrPositionOfCurrentUser']);
		$auto->setSearchFields(array(
			'login',
			'firstname',
			'lastname',
			'email'
		)); // , 'second_email'
		$auto->enableFieldSearchableCheck(true); // false
		$auto->setMoreLinkAvailable(true);

		if (($_REQUEST['fetchall'])) {
			$auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
		}

		echo $auto->getList($_REQUEST['term']);
		exit();
	}

	/**
	 * @param int[] $user_ids
	 */
	public function filterUserIdsByRbacOrPositionOfCurrentUser($user_ids)
	{
		global $DIC;
		$access = $DIC->access();

		return $access->filterUserIdsByRbacOrPositionOfCurrentUser(
			'read_users',
			\ilObjUserFolder::ORG_OP_EDIT_USER_ACCOUNTS,
			USER_FOLDER_ID,
			$user_ids
		);
	}

	/**
	 * We need this method if we can't access the tabs otherwise...
	 */
	private function activateTab()
	{
		$next_class = $this->ctrl->getCmdClass();

		switch($next_class) {
			// case 'ilexportgui':
			// 	$this->tabs->activateTab("export");
			// 	break;
		}

		return;
	}

	/**
	 * Goto redirection
	 * @param array $a_target
	 */
	public static function _goto($a_target)
	{
		global $DIC;
		$ilCtrl = $DIC->ctrl();
		$target = $a_target[0];

		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejGUI.php");
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejWebhook.php");

		if ($target == "webhook") {
			$webhook = new ilNolejWebhook();
			$webhook->parse();
			exit;

		} else if ($target == "modules") {
			$ilCtrl->setTargetScript("ilias.php");
			$ilCtrl->initBaseClass("ilUIPluginRouterGUI");
			$ilCtrl->redirectByClass(array("ilUIPluginRouterGUI", "ilNolejGUI"), ilNolejGUI::CMD_SHOW_MODULES);

		// } else if (preg_match('/course_([a-zA-Z0-9\-]{1,100})_([1-9][0-9]*)/', $target, $matches)) {
		// 	$ilCtrl->setTargetScript("ilias.php");
		// 	$ilCtrl->initBaseClass("ilUIPluginRouterGUI");
		// 	$ilCtrl->redirectByClass(
		// 		array("ilUIPluginRouterGUI", "ilNolejGUI"),
		// 		ilNolejGUI::CMD_SHOW_MODULES . "&id_partner=" . $matches[1] . "&id_course=" . $matches[2]
		// 	);

		// } else if (preg_match('/order_([1-9][0-9]*)/', $target, $matches)) {
		// 	$ilCtrl->setTargetScript("ilias.php");
		// 	$ilCtrl->initBaseClass("ilUIPluginRouterGUI");
		// 	$ilCtrl->redirectByClass(
		// 		array("ilUIPluginRouterGUI", "ilNolejGUI"),
		// 		ilNolejGUI::CMD_PURCHASE_CHECK . "&id_order=" . $matches[1]
		// 	);

		} else {
			ilUtil::sendInfo($a_target[0], true);
			parent::_goto($a_target);
		}
	}
}
