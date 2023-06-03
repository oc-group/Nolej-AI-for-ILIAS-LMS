<?php declare(strict_types=1);

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Nolej\GlobalScreen;

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationProvider;
use ILIAS\GlobalScreen\Scope\Notification\Provider\NotificationProvider;

/**
 * Class NolejNotificationProvider
 * 
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */
class NolejNotificationProvider extends AbstractNotificationProvider implements NotificationProvider
{
	/**
	 * @inheritDoc
	 */
	public function getNotifications() : array
	{
		$lng = $this->dic->language();
		$ui = $this->dic->ui();
		$user = $this->dic->user();
		$ctrl = $this->dic->ctrl();

		$lng->loadLanguageModule("badge");

		$factory = $this->globalScreen()->notifications()->factory();
		$id = function (string $id) : IdentificationInterface {
			return $this->if->identifier($id);
		};

		$new_activities = \ilNolejActivity::getNewCounter($user->getId());
		if ($new_activities == 0) {
			return [];
		}

		// Creating a badge Notification Item
		$badge_icon = $this->dic->ui()->factory()->symbol()->icon()->standard("bdga", $lng->txt("badge_badge"))->withIsOutlined(true);
		$badge_title = $ui->factory()->link()->standard(
			"Test notification", //$lng->txt("mm_badges"),
			$ctrl->getLinkTargetByClass(["ilDashboardGUI"], "jumpToBadges")
		);
		$latest = new \ilDateTime(\ilNolejActivity::getLatestTimestamp($user->getId()), IL_CAL_UNIX);
		$nolej_notification_item = $ui->factory()->item()->notification($badge_title, $badge_icon)
			->withDescription(str_replace("%1", $new_activities, $lng->txt("badge_new_badges")))
			->withProperties([$lng->txt("time") => \ilDatePresentation::formatDate($latest)]);

		$group = $factory->standardGroup($id('badge_bucket_group'))->withTitle($lng->txt('badge_badge'))
			->addNotification(
				$factory->standard($id('badge_bucket'))->withNotificationItem($nolej_notification_item)
				->withClosedCallable(
					function () use ($user) {
						// Stuff we do, when the notification is closed
						$noti_repo = new \ILIAS\Nolej\Notification\NolejNotificationPrefRepository($user);
						$noti_repo->updateLastCheckedTimestamp();
					}
				)
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
