<?php

/**
 * This file is part of Nolej Repository Object Plugin for ILIAS,
 * developed by OC Open Consulting to integrate ILIAS with Nolej
 * software by Neuronys.
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * @copyright 2023 OC Open Consulting SB Srl
 */

/**
 * Nolej notification repository
 * (using user preferences)
 */
class NolejNotificationPrefRepository
{
    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * Constructor
     * 
     * @param ilObjUser|null $user
     */
    public function __construct($user = null)
    {
        global $DIC;

        $this->user = (is_null($user))
            ? $DIC->user()
            : $user;
    }

    /**
     * Set last checked timestamp
     */
    public function updateLastCheckedTimestamp()
    {
        $this->user->writePref("nolej_last_checked", time());
    }

    /**
     * Get last checked timestamp
     *
     * @return int
     */
    public function getLastCheckedTimestamp()
    {
        return (int) $this->user->getPref("nolej_last_checked");
    }
}
