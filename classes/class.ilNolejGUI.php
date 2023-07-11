<?php
include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejConfig.php");

/**
 * Plugin GUI class
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * 
 * @ilCtrl_isCalledBy ilNolejGUI: ilUIPluginRouterGUI
 */
class ilNolejGUI
{
	const CMD_SHOW_MODULES = "showModules";
	// const CMD_CART_SHOW = "showCart";
	// const CMD_CART_ADD = "addToCart";
	// const CMD_CART_REMOVE = "removeFromCart";
	// const CMD_CART_EMPTY = "emptyCart";
	// const CMD_PURCHASE = "purchase";
	// const CMD_PURCHASE_CHECK = "checkPurchase";
	// const CMD_PURCHASE_LIST = "listPurchased";
	// const CMD_PURCHASE_FILTER_APPLY = "purchasedApplyFilter";
	// const CMD_PURCHASE_FILTER_RESET = "purchasedResetFilter";
	// const CMD_PARTNERS = "showPartners";
	const CMD_FILTER_APPLY = "applyFilter";
	const CMD_FILTER_RESET = "resetFilter";

	const TAB_MODULES = "modules";
	// const TAB_CART = "cart";
	// const TAB_PURCHASED = "purchased";
	// const TAB_PARTNERS = "partners";

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

	public function __construct()
	{
		global $DIC, $tpl;
		$this->ctrl = $DIC->ctrl();
		$this->tabs = $DIC->tabs();
		$this->db = $DIC->database();
		$this->lng = $DIC->language();

		$this->plugin = ilNolejPlugin::getInstance();
	}

	/**
	 * Handles all commmands,
	 * $cmd = functionName()
	 */
	public function executeCommand()
	{
		global $tpl;
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : self::CMD_SHOW_MODULES;

		$tpl->loadStandardTemplate();
		$tpl->setTitleIcon(ilNolejPlugin::PLUGIN_DIR . "/templates/images/icon_xnlj.svg");
		$tpl->setTitle($this->plugin->txt("plugin_title"), false);

		if (!$this->plugin->canAccessModules()) {
			ilUtil::sendFailure($this->plugin->txt("err_modules_denied"), true);
			ilUtil::redirect(ILIAS_HTTP_PATH);
			return false;
		}

		switch ($cmd) {
			// Need to have permission to access modules
			case self::CMD_SHOW_MODULES:
			// case self::CMD_PARTNERS:
			case self::CMD_FILTER_APPLY:
			case self::CMD_FILTER_RESET:
				$this->$cmd();
				break;

			// Need to be registered AND to have permission to access cart
			// case self::CMD_CART_SHOW:
			// case self::CMD_CART_ADD:
			// case self::CMD_CART_REMOVE:
			// case self::CMD_CART_EMPTY:
			// case self::CMD_PURCHASE:
			// case self::CMD_PURCHASE_LIST:
			// case self::CMD_PURCHASE_CHECK:
			// case self::CMD_PURCHASE_FILTER_APPLY:
			// case self::CMD_PURCHASE_FILTER_RESET:
			// 	if ($this->config->isRegistered() && $this->plugin->canAccessCart()) {
			// 		$this->$cmd();
			// 	} else {
			// 		$this->showModules();
			// 	}
			// 	break;

			default:
				$this->showModules();
		}

		$tpl->printToStdout();

		return true;
	}

	/**
	 * Init and activate tabs
	 */
	protected function initTabs($active_tab = null)
	{
		global $tpl;

		$this->tabs->addTab(
			self::TAB_MODULES,
			$this->plugin->txt("tab_" . self::TAB_MODULES),
			$this->ctrl->getLinkTarget($this, self::CMD_SHOW_MODULES)
		);

		// if ($this->config->isRegistered() && $this->plugin->canAccessCart()) {
		// 	$count = $this->cart->count();

		// 	if ($count > 0) {
		// 		$this->tabs->addTab(
		// 			self::TAB_CART,
		// 			sprintf($this->plugin->txt("tab_" . self::TAB_CART), $count),
		// 			$this->ctrl->getLinkTarget($this, self::CMD_CART_SHOW)
		// 		);
		// 	}

		// 	$this->tabs->addTab(
		// 		self::TAB_PURCHASED,
		// 		$this->plugin->txt("tab_" . self::TAB_PURCHASED),
		// 		$this->ctrl->getLinkTarget($this, self::CMD_PURCHASE_LIST)
		// 	);
		// }

		// Direct link to plugin configuration
		// if ($this->plugin->isAdmin()) {
		// 	include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejConfigGUI.php");
		// 	$this->tabs->addTab(
		// 		ilNolejConfigGUI::TAB_CONFIGURE,
		// 		($this->config->isRegistered() ? "" : "<i class='glyphicon glyphicon-alert'></i> ") // Add a warning if not yet registered
		// 		. $this->plugin->txt("tab_" . ilNolejConfigGUI::TAB_CONFIGURE),
		// 		$this->plugin->getConfigurationLink()
		// 	);
		// }

		// $this->tabs->addTab(
		//	self::TAB_PARTNERS,
		//	$this->plugin->txt("tab_" . self::TAB_PARTNERS),
		//	$this->ctrl->getLinkTarget($this, self::CMD_PARTNERS)
		// );

		switch ($active_tab) {
			case self::TAB_MODULES:
			// case self::TAB_PURCHASED:
			// case self::TAB_PARTNERS:
				$this->tabs->activateTab($active_tab);
				$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("tab_" . $active_tab), false);
				break;

			// case self::TAB_CART:
			// 	$this->tabs->activateTab($active_tab);
			// 	$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("cart"), false);
			// 	break;

			default:
				$this->tabs->activateTab(self::TAB_MODULES);
				$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("tab_" . self::TAB_MODULES), false);
		}

		$tpl->setDescription($this->plugin->txt("plugin_description"));
	}

	protected function getPermalinkGUI($idPartner, $idCourse)
	{
		$tpl = new ilTemplate(
			"tpl.permanent_link.html",
			true,
			true,
			"Services/PermanentLink"
		);

		include_once('./Services/Link/classes/class.ilLink.php');
		$href = $this->config->getPermalink($idPartner, $idCourse);

		$tpl->setVariable("LINK", $href);
		$tpl->setVariable("ALIGN", "left");

		return $tpl->get();
	}

	/**
	 * @return string
	 */
	public function buildIcon($id, $alt = "")
	{
		return '<img border="0" align="middle"'
		. ' src="' . ilUtil::getImagePath($id . ".svg") . '"'
		. ' alt="' . ($alt == "" ? "" : $this->lng->txt($alt)) . '" /> ';
	}

	/**
	 * Apply filter
	 */
	public function applyFilter()
	{
		$this->config->applyFilter();
		$this->showModules();
	}

	/**
	 * Reset filter
	 */
	public function resetFilter()
	{
		$this->config->resetFilter();
		$this->showModules();
	}

	public function addUserAutoComplete()
	{
		// include_once './Services/User/classes/class.ilUserAutoComplete.php';
		// $auto = new ilUserAutoComplete();
		// $auto->addUserAccessFilterCallable([$this, 'filterUserIdsByRbacOrPositionOfCurrentUser']);
		// $auto->setSearchFields(array(
		// 	'login',
		// 	'firstname',
		// 	'lastname',
		// 	'email')); // , 'second_email'
		// $auto->enableFieldSearchableCheck(false);
		// $auto->setMoreLinkAvailable(true);
		//
		// if (($_REQUEST['fetchall'])) {
		// 	$auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
		// }
		//
		// echo $auto->getList($_REQUEST['term']);
		// exit();
	}

	/**
	 * @param int[] $user_ids
	 */
	public function filterUserIdsByRbacOrPositionOfCurrentUser($user_ids = [])
	{
		// global $DIC;
		// $access = $DIC->access();
		//
		// return $access->filterUserIdsByRbacOrPositionOfCurrentUser(
		// 	'read_users',
		// 	\ilObjUserFolder::ORG_OP_EDIT_USER_ACCOUNTS,
		// 	USER_FOLDER_ID,
		// 	$user_ids
		// );
	}
}
