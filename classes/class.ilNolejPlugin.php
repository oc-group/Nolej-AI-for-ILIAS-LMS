<?php
include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejGUI.php");

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */
class ilNolejPlugin extends ilRepositoryObjectPlugin
{
	const PLUGIN_ID = "xnlj";
	const PLUGIN_NAME = "Nolej";
	const PLUGIN_DIR = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej";
	const PERMALINK = "xnlj_modules";
	const CNAME = "Repository";
	const SLOT_ID = "robj";
	const PREFIX = "rep_robj_xnlj";

	const CSS = "Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/templates/default/Nolej.css";

	const TABLE_CONFIG = "rep_robj_xnlj_config";
	const TABLE_TIC = "rep_robj_xnlj_tic";
	const TABLE_DOC = "rep_robj_xnlj_doc";
	const TABLE_DATA = "rep_robj_xnlj_data";
	const TABLE_LP = "rep_robj_xnlj_lp";

	/** @var self|null */
	protected static $instance = null;

	/** @var bool */
	protected static $menuItem = false; // Turn true when the menu item has been created

	/** @var array */
	static $config = [];

	/** @var bool|null */
	static $isAdmin = null;

	/** @var ilLogger */
	public $logger;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->logger = ilLoggerFactory::getLogger(self::PREFIX);
	}

	/**
	 * @return self
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** @return bool */
	protected function is6()
	{
		return (
			version_compare(ILIAS_VERSION_NUMERIC, "6.0") >= 0 &&
			version_compare(ILIAS_VERSION_NUMERIC, "7.0") < 0
		);
	}

	/** @return bool */
	protected function is7() : bool
	{
		return (
			version_compare(ILIAS_VERSION_NUMERIC, "7.0") >= 0 &&
			version_compare(ILIAS_VERSION_NUMERIC, "8.0") < 0
		);
	}

	// protected function isActiveManual() : bool
	// {
	// 	global $DIC;
	// 	return $DIC["ilPluginAdmin"]->isActive(
	// 		IL_COMP_SERVICE,
	// 		self::CNAME,
	// 		self::SLOT_ID,
	// 		self::PLUGIN_NAME
	// 	);
	// }

	/**
	 * Must correspond to the plugin subdirectory
	 * @return string
	 */
	public function getPluginName()
	{
		// if (!$this->isActiveManual()) {
		// 	return self::PLUGIN_NAME;
		// }

		// $this->insertMenuOnce();
		return self::PLUGIN_NAME;
	}

	// public function insertMenuOnce() : void
	// {
	// 	global $tpl;

	// 	// Add the menu item
	// 	try {
	// 		// This is executed one time only
	// 		if ($tpl && !$this->menuItem && $this->lang_initialised === true) {

	// 			// Save that the code below has been executed once
	// 			$this->menuItem = true;

	// 			if (!$this->canAccessModules()) {
	// 				// Display the menu item only if the user is allowed to
	// 				return;
	// 			}

	// 			// Load the menu item template
	// 			if ($this->is6()) {
	// 				$menuItem = new ilTemplate("tpl.menu_item_6.js", true, true, self::PLUGIN_DIR);
	// 			} else if ($this->is7()) {
	// 				$menuItem = new ilTemplate("tpl.menu_item_7.js", true, true, self::PLUGIN_DIR);
	// 			} else {
	// 				// Version not compatible, cannot put menu item
	// 				$this->menuItem = false;
	// 				return;
	// 			}

	// 			$menuItem->setVariable("nlj_LINK", ILIAS_HTTP_PATH . "/goto.php?target=" . self::PERMALINK);
	// 			$menuItem->setVariable("nlj_ICON", $this->_getIcon(self::PLUGIN_ID, ""));
	// 			$menuItem->setVariable("nlj_LABEL", $this->txt("plugin_title"));

	// 			// Print the item template in the HTML
	// 			$tpl->addOnLoadCode($menuItem->get());
	// 		}

	// 	} catch (Exception $e) {
	// 		// Ignore
	// 	}
	// }

	/**
	 * Returns a list of all repository object types which can be a parent of this type.
	 * @return array
	 */
	public function getParentTypes()
	{
		$par_types = array("root", "cat", "crs", "grp", "fold", "lso", "prg");
		return $par_types;
	}

	/** @return bool */
	public function isAdmin()
	{
		global $DIC, $ilUser;

		if (self::$isAdmin != null) {
			return self::$isAdmin;
		}

		if ($ilUser->isAnonymous()) {
			return self::$isAdmin = false;
		}

		$rbacreview = $DIC['rbacreview'];
		if (
			$ilUser->getId() == SYSTEM_USER_ID ||
			in_array(SYSTEM_ROLE_ID, $rbacreview->assignedRoles($ilUser->getId()))
		) {
			return self::$isAdmin = true;
		}

		ilUtil::sendFailure(
			print_r($rbacreview->assignedRoles($ilUser->getId()), true),
			true
		);
		return self::$isAdmin = false;
	}

	protected function afterActivation()
	{
	}

	protected function uninstallCustom()
	{
		$tables = [
			self::TABLE_CONFIG,
			self::TABLE_TIC,
			self::TABLE_DATA,
			self::TABLE_DOC,
			self::TABLE_LP
		];

		for ($i = 0, $len = count($tables); $i < $len; $i++) {
			if($this->db->tableExists($tables[$i])) {
				$this->db->dropTable($tables[$i]);
			}
		}
	}

	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function allowCopy()
	{
		return false;
	}

	/** @return bool */
	public function currentUserHasRole()
	{
		global $ilUser;

		$user_roles = self::dic()->rbac()->review()->assignedGlobalRoles($ilUser->getId());
		return true;
		// $config_roles = $this->config()->getValue(FormBuilder::KEY_ROLES);

		foreach ($user_roles as $user_role) {
			if (in_array($user_role, $config_roles)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param mixed $code
	 */
	public function setPermanentLink($code)
	{
		global $tpl;
		$tpl->setPermanentLink(self::PLUGIN_ID, $code);
	}

	/** @return string */
	public function getConfigurationLink()
	{
		global $DIC;
		include_once(self::PLUGIN_DIR . "/classes/class.ilNolejConfigGUI.php");

		return sprintf(
			"%s&ref_id=31&admin_mode=settings&ctype=Services&cname=%s&slot_id=%s&pname=%s",
			$DIC->ctrl()->getLinkTargetByClass(
				["ilAdministrationGUI", "ilObjComponentSettingsGUI", ilNolejConfigGUI::class],
				ilNolejConfigGUI::CMD_CONFIGURE
			),
			self::CNAME,
			self::SLOT_ID,
			self::PLUGIN_NAME
		);
	}

	/**
	 * @param string $keyword
	 * @param string $defaultValue
	 * @return string
	 */
	public function getConfig($keyword, $defaultValue = "")
	{
		if (isset(self::$config[$keyword])) {
			return self::$config[$keyword];
		}
		$res = $this->db->queryF(
			"SELECT `value` FROM " . self::TABLE_CONFIG . " WHERE keyword = %s;",
			array("text"),
			array($keyword)
		);

		if (!$res || $this->db->numRows($res) <= 0) {
			return $defaultValue;
		}

		$record = $this->db->fetchAssoc($res);
		self::$config[$keyword] = $record["value"];
		return $record["value"];
	}

	/**
	 * @param string $keyword
	 * @param string $defaultValue
	 */
	public function saveConfig($keyword, $value)
	{
		$this->db->manipulateF(
			"REPLACE INTO " . self::TABLE_CONFIG . " (keyword, value) VALUES (%s, %s);",
			array("text", "text"),
			array($keyword, $value)
		);
	}

	// public function canAccessModules() : bool
	// {
	// 	return $this->isAdmin() ||
	// 		(!$this->isAdmin() && ilUtil::yn2tf($this->getConfig("config_menu", "n")));
	// }

	// public function canAccessCart() : bool
	// {
	// 	return $this->isAdmin() ||
	// 		(!$this->isAdmin() && ilUtil::yn2tf($this->getConfig("config_user_purchase", "n")));
	// }
}
