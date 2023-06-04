<?php declare(strict_types=1);

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Nolej\Provider;

// require_once("./src/GlobalScreen/Scope/Notification/Provider/AbstractNotificationPluginProvider.php");

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationPluginProvider;
use ILIAS\Nolej\Notification\NolejNotificationPrefRepository;
use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationProvider;
// use ILIAS\GlobalScreen\Scope\Notification\Provider\NotificationProvider;

/**
 * Class NolejNotificationProvider
 * 
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */
class NolejNotificationProvider extends AbstractNotificationPluginProvider
{

	/**
	 * @inheritDoc
	 */
	public function getNotifications() : array
	{
		// global $DIC;
		$lng = $this->dic->language();
		$ui = $this->dic->ui();
		$user = $this->dic->user();
		$ctrl = $this->dic->ctrl();

		// $lng->loadLanguageModule("badge");

		$factory = $this->notification_factory;
		// $id = $this->id;

		$new_activities = \ilNolejActivity::getNewCounter($user->getId());
		if ($new_activities == 0) {
			return [];
		}

		// Creating a Nolej Notification Item
		$nolej_icon = $ui->factory()->symbol()->icon()->standard("bdga", "NOLEJ")->withIsOutlined(true);
		$nolej_title = $ui->factory()->link()->standard(
			"Test notification", //$lng->txt("mm_badges"),
			$ctrl->getLinkTargetByClass(["ilDashboardGUI"], "jumpToBadges")
		);
		$latest = new \ilDateTime(\ilNolejActivity::getLatestTimestamp($user->getId()), IL_CAL_UNIX);
		$nolej_notification_item = $ui
			->factory()
			->item()
			->notification($nolej_title, $nolej_icon)
			->withDescription(str_replace("%1", $new_activities, "New Nolej Activity"))
			->withProperties([$lng->txt("time") => \ilDatePresentation::formatDate($latest)]);

		$group = $factory
			->standardGroup($this->id('nolej_bucket_group'))
			->withTitle("Nolej activities")
			->addNotification(
				$factory->standard($this->id('nolej_bucket'))
				->withNotificationItem($nolej_notification_item)
				// ->withClosedCallable(
				// 	function () use ($user) {
				// 		// Stuff we do, when the notification is closed
				// 		$noti_repo = new NolejNotificationPrefRepository($user);
				// 		$noti_repo->updateLastCheckedTimestamp();
				// 	}
				// )
				->withNewAmount($new_activities)
			)
			->withOpenedCallable(function () {
				// Stuff we do, when the notification is opened
			});

		return [
			$group,
		];
	}
}
