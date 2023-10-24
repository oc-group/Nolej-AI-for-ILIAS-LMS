<?php

/**
 * This file is part of Nolej Repository Object Plugin for ILIAS,
 * developed by OC Open Consulting to integrate ILIAS with Nolej
 * software by Neuronys.
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * @copyright 2023 OC Open Consulting SB Srl
 */

include_once "./Services/Repository/classes/class.ilObjectPluginListGUI.php";

/**
 * Handles the presentation in container items (categories, courses, ...)
 * together with the corresponding ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 */
class ilObjNolejListGUI extends ilObjectPluginListGUI
{

	/**
	 * Init type
	 */
	function initType()
	{
		$this->setType(ilNolejPlugin::PLUGIN_ID);
	}

	/**
	 * Get name of gui class handling the commands
	 * @return string
	 */
	function getGuiClass()
	{
		return "ilObjNolejGUI";
	}

	/**
	 * Get commands
	 * @return array
	 */
	function initCommands()
	{
		// Cannot override init() method; adding here CSS to display the icon.
		global $tpl;
		// $tpl->addCss(ilNolejPlugin::CSS);

		return array(
			array(
				"permission" => "read",
				"cmd" => ilObjNolejGUI::CMD_CONTENT_SHOW,
				"default" => true
			),
			array(
				"permission" => "write",
				"cmd" => ilObjNolejGUI::CMD_PROPERTIES_EDIT,
				"txt" => $this->txt("cmd_edit"),
				"default" => false
			)
		);
	}

	/**
	 * Get item properties
	 *
	 * @return array array of property arrays:
	 * "alert" (boolean) => display as an alert property (usually in red)
	 * "property" (string) => property name
	 * "value" (string) => property value
	 */
	function getProperties(): array
	{
		global $lng, $ilUser, $ilAccess;

		$props = array();

		$this->plugin->includeClass("class.ilObjNolejAccess.php");
		// $object = ilObjectFactory::getInstanceByObjId($this->obj_id, false);

		if (!ilObjNolejAccess::checkOnline($this->obj_id)) {
			$props[] = array(
				"alert" => true,
				"property" => $this->txt("prop_status"),
				"value" => $this->txt("prop_offline")
			);
		}

		return $props;
	}
}
