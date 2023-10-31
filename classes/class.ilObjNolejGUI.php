<?php

/**
 * This file is part of Nolej Repository Object Plugin for ILIAS,
 * developed by OC Open Consulting to integrate ILIAS with Nolej
 * software by Neuronys.
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * @copyright 2023 OC Open Consulting SB Srl
 */

require_once("./Services/Tracking/classes/class.ilLearningProgress.php");
require_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
require_once("./Services/Tracking/classes/status/class.ilLPStatusPlugin.php");

// require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejPlugin.php");
// require_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejActivityManagementGUI.php");
// require_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejConfig.php");

// use srag\DIC\H5P\DICTrait;
// use srag\Plugins\H5P\Content\Content;
// use srag\Plugins\H5P\Content\Editor\EditContentFormGUI;
// use srag\Plugins\H5P\Content\Editor\ImportContentFormGUI;
// use srag\Plugins\H5P\Utils\H5PTrait;

use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;

/**
 * @ilCtrl_isCalledBy ilObjNolejGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjNolejGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 * @ilCtrl_Calls ilObjNolejGUI: ilNolejActivityManagementGUI
 * @ilCtrl_Calls ilObjNolejGUI: ilUIPluginRouterGUI
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

    const TAB_PROPERTIES = "properties";
    const TAB_CONTENT = "content";
    const TAB_ACTIVITY_MANAGEMENT = "activity_management";

    const PROP_TITLE = "title";
    const PROP_DESCRIPTION = "description";
    const PROP_ONLINE = "online";

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
        global $DIC, $ilCtrl, $ilTabs, $tpl;
        $this->ctrl = $ilCtrl;
        $this->renderer = $DIC->ui()->renderer();
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
            case "ilnolejactivitymanagementgui":
                $this->checkPermission("write");
                $this->tabs->activateTab(self::TAB_ACTIVITY_MANAGEMENT);
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
                        $this->checkPermission("read");
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
     * @return bool returns true iff the plugin supports import/export
     */
    protected function supportsExport()
    {
        return false;
    }

    /**
     * @return bool returns true iff this plugin object supports cloning
     */
    protected function supportsCloning()
    {
        return false;
    }

    /**
     * Set tabs
     */
    protected function setTabs()
    {
        // tab for the "show content" command
        if ($this->object->hasReadPermission()) {
            $this->tabs->addTab(
                self::TAB_CONTENT,
                $this->txt("tab_" . self::TAB_CONTENT),
                $this->ctrl->getLinkTarget($this, self::CMD_CONTENT_SHOW)
            );
        }

        if ($this->checkPermissionBool("write")) {
            $activityManagement = new ilNolejActivityManagementGUI($this);
            $this->tabs->addTab(
                self::TAB_ACTIVITY_MANAGEMENT,
                $this->txt("tab_" . self::TAB_ACTIVITY_MANAGEMENT),
                $this->ctrl->getLinkTarget($activityManagement, "")
            );
        }

        // standard info screen tab
        $this->addInfoTab();

        // "properties" and "manage licenses" tabs
        if ($this->object->hasWritePermission()) {
            $this->tabs->addTab(
                self::TAB_PROPERTIES,
                $this->txt("tab_" . self::TAB_PROPERTIES),
                $this->ctrl->getLinkTarget($this, self::CMD_PROPERTIES_EDIT)
            );
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
        $this->tabs->activateTab(self::TAB_PROPERTIES);

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
     * Save
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
        $this->tabs->activateTab(self::TAB_CONTENT);

        if ($this->object->getDocumentStatus() != ilNolejActivityManagementGUI::STATUS_COMPLETED) {
            ilUtil::sendInfo($this->plugin->txt("activities_not_yet_generated"));
            return;
        }

        $h5pDir = $this->object->getDataDir() . "/h5p";
        if (!is_dir($h5pDir)) {
            ilUtil::sendInfo($this->plugin->txt("activities_not_downloaded"));
            return;
        }

        // $form = self::h5p()->contents()->editor()->factory()->newImportContentFormInstance($this, "CMD_IMPORT_CONTENT", "CMD_MANAGE_CONTENTS");
        // if ($form->storeForm()) {
        // 	$this->tpl->setContent("error store");
        // 	return;
        // }
        $contentId = $this->object->getContentIdOfType("ibook");

        // Display activities
        $this->tpl->setContent(($contentId != -1)
            ? $this->getH5PHtml($contentId)
            : "Error"
        );
    }

    /**
        * @param int $contentId
        * @return string html
        */
    public static function getH5PHtml($contentId)
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();
        $factory = $DIC->ui()->factory();

        /** ILIAS 6 ? */
        // $h5pContent = self::h5p()->contents()->getContentById($contentId);

        // if ($h5pContent == null) {
        //     ilUtil::sendFailure(ilNolejConfig::txt("err_h5p_content"));
        //     return "";
        // }

        // return self::h5p()->contents()->show()->getH5PContent($h5pContent, true, false);

        if (
            !class_exists("ilH5PPlugin") ||
            !method_exists("ilH5PPlugin", "getInstance")
        ) {
            $error = $factory
                ->messageBox()
                ->failure(ilNolejConfig::txt("err_h5p_plugin"));
            return $renderer->render($error);
        }

        /** @var IContainer */
        $h5p_container = ilH5PPlugin::getInstance()->getContainer();

        /** @var IRepositoryFactory */
        $repositories = $h5p_container->getRepositoryFactory();

        $content = $repositories->content()->getContent((int) $contentId);

        $component = (null === $content)
            ? $factory
                ->messageBox()
                ->failure(ilNolejConfig::txt("err_h5p_content"))
            : $h5p_container
                ->getComponentFactory()
                ->content($content)
                ->withLoadingMessage(
                    ilNolejConfig::txt("content_loading")
                );

        return sprintf(
        	"<div style=\"margin-top: 25px;\">%s</div>",
        	$renderer->render($component)
        );
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

        // $details = $this->object->lookupDetails();
        // if (!$details) {
        // 	return;
        // }

        // $this->lng->loadLanguageModule('crs');
        // $info->addSection($this->lng->txt("crs_general_informations"));

        // $info->addProperty($this->plugin->txt("prop_teacher"), $details->teacher);
        // $info->addProperty(
        // 	"<img style='max-width: 90%;' src='" . $details->image . "'>",
        // 	nl2br($details->description)
        // );
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
    }

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

        include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejGUI.php");
        include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejWebhook.php");

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
            parent::_goto($a_target);
        }
    }
}
