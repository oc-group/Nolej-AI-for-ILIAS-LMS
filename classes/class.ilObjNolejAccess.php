<?php

/**
 * This file is part of Nolej Repository Object Plugin for ILIAS,
 * developed by OC Open Consulting to integrate ILIAS with Nolej
 * software by Neuronys.
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * @copyright 2023 OC Open Consulting SB Srl
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilObjNolej.php");
// require_once("./Services/Conditions/interfaces/interface.ilConditionHandling.php"); //bugfix mantis 24891

/**
 * Please do not create instances of large application classes
 * Write small methods within this class to determine the status.
 */
class ilObjNolejAccess extends ilObjectPluginAccess
{

    /**
     * Checks whether a user may invoke a command or not
     * (this method is called by ilAccessHandler::checkAccess)
     *
     * Please do not check any preconditions handled by
     * ilConditionHandler here. Also don't do usual RBAC checks.
     *
     * @param string $a_cmd command (not permission!)
     * @param string $a_permission permission
     * @param int $a_ref_id reference id
     * @param int $a_obj_id object id
     * @param ?int $a_user_id user id (default is current user)
     * @return bool true, if everything is ok
     */
    public function _checkAccess(
        string $a_cmd,
        string $a_permission,
        int $a_ref_id,
        int $a_obj_id,
        ?int $a_user_id = NULL
    ): bool {
        if ($a_user_id == null) {
            $a_user_id = $this->user->getId();
        }

        if ($a_permission == "") {
            return false;
        }

        if ("visible" === $a_permission || "read" === $a_permission) {
            // if the current user can edit the given object it should also be visible.
            if ($this->access->checkAccessOfUser($a_user_id, "write", "", $a_ref_id)) {
                return true;
            }

            if (!self::_isOffline($a_obj_id)) {
                return $this->access->checkAccessOfUser($a_user_id, $a_permission, "", $a_ref_id);
            }

            return false;
        }

        if ("delete" === $a_permission) {
            return (
                $this->access->checkAccessOfUser($a_user_id, "delete", "", $a_ref_id) ||
                $this->access->checkAccessOfUser($a_user_id, "write", "", $a_ref_id)
            );
        }

        return (bool) $this->access->checkAccessOfUser($a_user_id, $a_permission, "", $a_ref_id);
    }

    /**
     * @param int $a_obj_id
     * @return bool
     */
    public static function _isOffline(int $a_obj_id): bool
    {
        global $ilDB;

        $set = $ilDB->queryF(
            "SELECT is_online FROM " . ilNolejPlugin::TABLE_DATA . " WHERE id = %s;",
            array("integer"),
            array($a_obj_id)
        );
        $rec = $ilDB->fetchAssoc($set);
        return !((boolean) $rec["is_online"]);
    }

    /**
     * Returns an array with valid operators for the specific object type
     * @return array
     */
    public static function getConditionOperators(): array
    {
        include_once './Services/Conditions/classes/class.ilConditionHandler.php'; //bugfix mantis 24891
        return array(
            ilConditionHandler::OPERATOR_FAILED,
            ilConditionHandler::OPERATOR_PASSED
        );
    }

    /**
     * Goto redirection
     * @param string $a_target
     * @return bool
     */
    public static function _checkGoto(string $a_target): bool
    {
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejGUI.php");
        $target = substr($a_target, strlen(ilNolejPlugin::PLUGIN_ID) + 1); // Remove plugin ID

        if (
            $target == "webhook" ||
            $target == "modules"
        ) {
            return true;
        }

        return parent::_checkGoto($a_target);
    }

    /**
     * @inheritDoc
     */
    public function canBeDelivered(ilWACPath $ilWACPath): bool
    {
        return true;
        // $module = $ilWACPath->getModuleIdentifier();

        // var_dump(
        //     [
        //         "module" => $module,
        //         "path" => $ilWACPath->getPath(),
        //         "modulePath" => $ilWACPath->getModulePath()
        //     ]
        // );
        // die();

        // if ("cachedassets" === $module || "libraries" === $module || "editor" === $module) {
        //     return true;
        // }

        // if ("content" !== $module) {
        //     return false;
        // }

        // $content_id = (int) substr($ilWACPath->getPath(), strlen($ilWACPath->getModulePath() . "content/"));
        // $content = $this->content_repository->getContent($content_id);

        // if (null === $content) {
        //     return false;
        // }

        // return $this->h5p_access_handler->checkAccess(
        //     $content->getObjId(),
        //     false,
        //     $content->getParentType(),
        //     $content->isInWorkspace(),
        //     "read"
        // );
    }
}
