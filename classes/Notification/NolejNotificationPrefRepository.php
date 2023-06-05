<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Nolej notification repository
 * (using user preferences)
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
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
