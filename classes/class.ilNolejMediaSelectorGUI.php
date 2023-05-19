<?php

/**
 * Select media object
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * 
 * @ilCtrl_isCalledBy ilNolejMediaSelectorGUI: ilNolejGUI, ilNolejConfigGUI
 * @ilCtrl_Calls ilNolejMediaSelectorGUI: ilObjMediaObjectGUI
 */

class ilNolejMediaSelectorGUI
{
	const CMD_INSERT = "insert";
	const CMD_CH_OBJ_REF = "changeObjectReference";
	const CMD_INSERT_FROM_POOL = "insertFromPool";
	const CMD_POOL_SELECTION = "poolSelection";
	const CMD_SELECT_POOL = "selectPool";
	const CMD_INSERT_NEW = "insertNew";

	const CMD_APPLY_FILTER = "applyFilter";
	const CMD_RESET_FILTER = "resetFilter";

	const TAB_NEW = "new_media";
	const TAB_INSERT_FROM_POOL = "insert_from_pool";

	protected $guiObj;
	protected ilCtrl $ctrl;
	protected string $cmd;
	protected string $subCmd;
	protected string $pool_view = "";
	protected ilTabsGUI $tabs;
	protected ilDBInterface $db;
	protected ilAccessHandler $access;
	protected \ILIAS\DI\UIServices $ui;
	protected ilToolbarGUI $toolbar;
	protected ilLanguage $lng;

	protected ilNolejPlugin $plugin;

	public function __construct(
		$guiObj
	)
	{
		global $DIC, $tpl;
		$this->guiObj = $guiObj;
		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
		$this->tabs = $DIC->tabs();
		$this->db = $DIC->database();
		$this->access = $DIC->access();
		$this->ui = $DIC->ui();
        $this->toolbar = $DIC->toolbar();

		$this->subCmd = $_GET["subCmd"] ?? "";
		$this->pool_view = "folder";
        $pv = $_GET["pool_view"] ?? "";
        if (in_array($pv, array("folder", "all"))) {
            $this->pool_view = $pv;
        }
		$this->ctrl->saveParameter($this, ["pool_view"]);

		$this->plugin = ilNolejPlugin::getInstance();
	}

	/**
     * @return mixed
     * @throws ilCtrlException
     */
	public function executeCommand()
	{
		global $tpl;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd() ?? self::CMD_INSERT;
		$tpl->setTitleIcon(ilUtil::getImagePath("icon_mob.svg"));

		switch ($next_class) {
            case "ilobjmediaobjectgui":
                // $tpl->setTitle(
				// 	$this->lng->txt("mob") . ": " . $this->content_obj->getMediaObject()->getTitle()
				// );
                $mob_gui = new ilObjMediaObjectGUI(
					"",
					0, //$this->content_obj->getMediaObject()->getId(),
					false,
					false
				);
                $mob_gui->setBackTitle("Back"); // $this->page_back_title);
                $mob_gui->setEnabledMapAreas(false);
                $this->ctrl->forwardCommand($mob_gui);
                break;

			default:
				switch ($cmd) {
					case self::CMD_CH_OBJ_REF:
					case self::CMD_INSERT_FROM_POOL:
					case self::CMD_POOL_SELECTION:
					case self::CMD_SELECT_POOL:
					case self::CMD_INSERT_NEW:
						$this->$cmd();
						break;
					case self::CMD_INSERT:
					default:
						$this->insert();
				}
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
			self::TAB_NEW,
			$this->plugin->txt("tab_" . self::TAB_NEW),
			$this->ctrl->getLinkTarget($this, self::CMD_INSERT_NEW)
		);

		$this->tabs->addTab(
			self::TAB_INSERT_FROM_POOL,
			$this->plugin->txt("tab_" . self::TAB_INSERT_FROM_POOL),
			$this->ctrl->getLinkTarget($this, self::CMD_INSERT_FROM_POOL)
		);

		switch ($active_tab) {
			case self::TAB_INSERT_FROM_POOL:
				$this->tabs->activateTab($active_tab);
				$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("tab_" . $active_tab), false);
				break;

			case self::TAB_NEW:
			default:
				$this->tabs->activateTab(self::TAB_NEW);
				$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("tab_" . self::TAB_NEW), false);
		}

		$tpl->setDescription($this->plugin->txt("plugin_description"));
	}

	public function insert(
		$a_post_cmd = "edpost",
		$a_submit_cmd = "create_mob",
		$a_input_error = false
	): void {
		$subCmd = $this->subCmd;

		// if (in_array($subCmd, ["insertNew", "insertFromPool"])) {
		// 	$this->edit_repo->setSubCmd($subCmd);
		// }

		// if (($subCmd == "") && $this->edit_repo->getSubCmd() != "") {
		// 	$subCmd = $this->edit_repo->getSubCmd();
		// }

		switch ($subCmd) {
			case self::CMD_INSERT_FROM_POOL:
			case self::CMD_POOL_SELECTION:
			case self::CMD_SELECT_POOL:
			case self::CMD_APPLY_FILTER:
			case self::CMD_RESET_FILTER:
				$this->$subCmd();
				break;

			case self::CMD_INSERT_NEW:
			default:
				$this->insertNew();
				break;
		}
	}

	/**
	 * Change object reference
	 */
	public function changeObjectReference(): void
	{
		$subCmd = $this->subCmd;

		// if (in_array($subCmd, ["insertNew", "insertFromPool"])) {
		// 	$this->edit_repo->setSubCmd($subCmd);
		// }

		// if (($subCmd == "") && $this->edit_repo->getSubCmd() != "") {
		// 	$subCmd = $this->edit_repo->getSubCmd();
		// }

		switch ($subCmd) {
			case self::CMD_INSERT_FROM_POOL:
			case self::CMD_POOL_SELECTION:
			case self::CMD_SELECT_POOL:
				$this->$subCmd(true);
				break;

			case self::CMD_INSERT_NEW:
			default:
				$this->insertNew(true);
		}
	}

	/**
	 * Create new media object
	 */
	public function insertNew(
		bool $a_change_obj_ref = false
	) : void
	{
		global $tpl;

		$this->initTabs(self::TAB_NEW);

		$mob_gui = new ilObjMediaObjectGUI("");
		$mob_gui->initForm("create");
		$form = $mob_gui->getForm();
		$form->clearCommandButtons();

		if ($a_change_obj_ref) {
			$this->ctrl->setParameter($this, "subCmd", self::CMD_CH_OBJ_REF);
			$form->setFormAction($this->ctrl->getFormAction($this));
			$form->addCommandButton("createNewObjectReference", $this->plugin->txt("save"));
		} else {
			$form->setFormAction($this->ctrl->getFormAction($this, "create_mob"));
			$form->addCommandButton("create_mob", $this->plugin->txt("save"));
		}

		$form->addCommandButton("cancelCreate", $this->plugin->txt("cancel"));
		$tpl->setContent($form->getHTML());
	}

	/**
	 * Insert media object from pool
	 */
	public function insertFromPool(
		bool $a_change_obj_ref = false
	) : void
	{
		global $tpl;

		$mediaPoolId = (int) $_GET["pool_ref_id"] ?? 0;

		if (
			$mediaPoolId <= 0 ||
			!$this->access->checkAccess("write", "", $mediaPoolId) ||
			ilObject::_lookupType(ilObject::_lookupObjId($mediaPoolId)) != "mep"
		) {
			// Cannot access to the selected mediapool
			$this->poolSelection($a_change_obj_ref);
			return;
		}

		$this->initTabs(self::TAB_INSERT_FROM_POOL);
		$html = "";
		$toolbar = new ilToolbarGUI();

		// button: select pool
		$this->ctrl->setParameter($this, "subCmd", self::CMD_POOL_SELECTION);
		$this->ctrl->setParameter($this, "pool_ref_id", $mediaPoolId);
		if ($a_change_obj_ref) {
			$toolbar->addButton(
				$this->lng->txt("cont_switch_to_media_pool"),
				$this->ctrl->getLinkTarget($this, self::CMD_CH_OBJ_REF)
			);
		} else {
			$toolbar->addButton(
				$this->lng->txt("cont_switch_to_media_pool"),
				$this->ctrl->getLinkTarget($this, self::CMD_INSERT_FROM_POOL)
			);
		}

		$this->ctrl->setParameter($this, "subCmd", "");
		// $this->ctrl->setParameter($this, "pool_ref_id", null);

		// view mode: pool view (folders/all media objects)
		$f = $this->ui->factory();
		$tcmd = ($a_change_obj_ref)
			? self::CMD_CH_OBJ_REF
			: self::CMD_INSERT_FROM_POOL;

		$this->lng->loadLanguageModule("mep");

		$this->ctrl->setParameter($this, "pool_view", "folder");
		$actions[$this->lng->txt("folders")] = $this->ctrl->getLinkTarget($this, $tcmd);

		$this->ctrl->setParameter($this, "pool_view", "all");
		$actions[$this->lng->txt("mep_all_mobs")] = $this->ctrl->getLinkTarget($this, $tcmd);

		$this->ctrl->setParameter($this, "pool_view", $this->pool_view);
		$aria_label = $this->lng->txt("cont_change_pool_view");
		$view_control = $f
			->viewControl()
			->mode($actions, $aria_label)
			->withActive(
				($this->pool_view == "folder")
				? $this->lng->txt("folders")
				: $this->lng->txt("mep_all_mobs")
			);
		$toolbar->addSeparator();
		$toolbar->addComponent($view_control);

		$html = $toolbar->getHTML();

		$pool = new ilObjMediaPool($mediaPoolId);

		$this->ctrl->setParameter($this, "subCmd", self::CMD_INSERT_FROM_POOL);
		$tcmd = ($a_change_obj_ref)
			? self::CMD_CH_OBJ_REF
			: self::CMD_INSERT_FROM_POOL;
		$tmode = ($a_change_obj_ref)
			? ilMediaPoolTableGUI::IL_MEP_SELECT_SINGLE
			: ilMediaPoolTableGUI::IL_MEP_SELECT_SINGLE; // IL_MEP_SELECT;

		// handle table sub commands and get the table
		switch ($this->subCmd) {
			case self::CMD_APPLY_FILTER:
				$mpool_table = new ilMediaPoolTableGUI(
					$this,
					$tcmd,
					$pool,
					"mep_folder",
					$tmode,
					$this->pool_view == "all"
				);
				$mpool_table->resetOffset();
				$mpool_table->writeFilterToSession();
				break;

			case self::CMD_RESET_FILTER:
				$mpool_table = new ilMediaPoolTableGUI(
					$this,
					$tcmd,
					$pool,
					"mep_folder",
					$tmode,
					$this->pool_view == "all"
				);
				$mpool_table->resetOffset();
				$mpool_table->resetFilter();
				break;

			default:
				$mpool_table = new ilMediaPoolTableGUI(
					$this,
					$tcmd,
					$pool,
					"mep_folder",
					$tmode,
					$this->pool_view == "all"
				);
		}

		$html .= $mpool_table->getHTML();
		$tpl->setContent($html);
	}

	/**
	 * Select concrete pool
	 */
	public function selectPool(
		bool $a_change_obj_ref = false
	): void {
		$this->ctrl->setParameter($this, "pool_ref_id", $_GET["pool_ref_id"]);
		$this->ctrl->setParameter($this, "subCmd", self::CMD_INSERT_FROM_POOL);
		if ($a_change_obj_ref) {
			$this->ctrl->redirect($this, self::CMD_CH_OBJ_REF);
		} else {
			$this->ctrl->redirect($this, self::CMD_INSERT);
		}
	}

	/**
	 * Pool Selection
	 */
	public function poolSelection(
		bool $a_change_obj_ref = false
	): void {
		global $tpl;

		$this->initTabs(self::TAB_INSERT_FROM_POOL);

		$this->ctrl->setParameter($this, "subCmd", self::CMD_POOL_SELECTION);
		if ($a_change_obj_ref) {
			$exp = new ilPoolSelectorGUI($this, self::CMD_CH_OBJ_REF, $this, self::CMD_CH_OBJ_REF);
		} else {
			$exp = new ilPoolSelectorGUI($this, self::CMD_INSERT);
		}

		// filter
		$exp->setTypeWhiteList(array("root", "cat", "grp", "fold", "crs", "mep"));
		$exp->setClickableTypes(array("mep"));

		if (!$exp->handleCommand()) {
			$tpl->setContent($exp->getHTML());
		}
	}
}
