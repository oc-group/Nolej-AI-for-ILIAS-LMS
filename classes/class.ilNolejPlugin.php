<?php

/**
 * This file is part of Nolej Repository Object Plugin for ILIAS,
 * developed by OC Open Consulting to integrate ILIAS with Nolej
 * software by Neuronys.
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * @copyright 2023 OC Open Consulting SB Srl
 */

require_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejGUI.php");

use ILIAS\DI\Container;
use ILIAS\GlobalScreen\Provider\PluginProviderCollection;

/**
 * Plugin main class
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

	const TABLE_CONFIG = "rep_robj_xnlj_config";
	const TABLE_ACTIVITY = "rep_robj_xnlj_activity";
	const TABLE_TIC = "rep_robj_xnlj_tic";
	const TABLE_DOC = "rep_robj_xnlj_doc";
	const TABLE_DATA = "rep_robj_xnlj_data";
	const TABLE_H5P = "rep_robj_xnlj_hfp";
	const TABLE_LP = "rep_robj_xnlj_lp";

	/** @var PluginProviderCollection|null */
	protected static $pluginProviderCollection = null;


	/** @var bool|null */
	static $isAdmin = null;

	/**
	 * Constructor
	 */
	public function __construct(
		\ilDBInterface $db,
        \ilComponentRepositoryWrite $component_repository,
        string $id
	)
	{
		global $DIC;

		parent::__construct();
		$this->provider_collection = $this->getPluginProviderCollection(); // Fix overflow
		// $this->provider_collection = new PluginProviderCollection();
		// $this->provider_collection->setNotificationProvider(new NolejNotificationProvider($DIC, $this));
	}

	/**
	 * @return PluginProviderCollection
	 */
	protected function getPluginProviderCollection()
	{
		global $DIC;

		if (!isset($DIC["global_screen"])) {
			return $this->provider_collection;
		}

		require_once(self::PLUGIN_DIR . "/classes/MainBar/NolejMainBarProvider.php");
		require_once(self::PLUGIN_DIR . "/classes/Notification/NolejNotificationProvider.php");
		if (self::$pluginProviderCollection === null) {
			self::$pluginProviderCollection = new PluginProviderCollection();

			// self::$pluginProviderCollection->setMetaBarProvider(self::helpMe()->metaBar());
			// self::$pluginProviderCollection->setMainBarProvider(new NolejMainBarProvider($DIC, $this));
			self::$pluginProviderCollection->setNotificationProvider(new NolejNotificationProvider($DIC, $this));
		}

		return self::$pluginProviderCollection;
	}

	/**
	 * Must correspond to the plugin subdirectory
	 * @return string
	 */
	public function getPluginName()
	{
		return self::PLUGIN_NAME;
	}

	/**
	 * Returns a list of all repository object types which can be a parent of this type.
	 * @return array
	 */
	public function getParentTypes()
	{
		$par_types = array("root", "cat", "crs", "grp", "fold", "lso", "prg");
		return $par_types;
	}

	/**
	 * @return bool
	 */
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
			in_array(
				SYSTEM_ROLE_ID,
				$rbacreview->assignedRoles($ilUser->getId())
			)
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
			self::TABLE_ACTIVITY,
			self::TABLE_TIC,
			self::TABLE_DATA,
			self::TABLE_DOC,
			self::TABLE_H5P,
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
		// global $ilUser;

		// $user_roles = self::dic()->rbac()->review()->assignedGlobalRoles($ilUser->getId());
		// return true;
		// $config_roles = $this->config()->getValue(FormBuilder::KEY_ROLES);

		// foreach ($user_roles as $user_role) {
		// 	if (in_array($user_role, $config_roles)) {
		// 		return true;
		// 	}
		// }

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

	/** 
	 * @return string
	 */
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

}
