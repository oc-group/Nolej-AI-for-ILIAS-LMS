<?php declare(strict_types=1);

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once(ilNolejPlugin::PLUGIN_DIR . "/classes/Notification/NolejActivity.php");
require_once(ilNolejPlugin::PLUGIN_DIR . "/classes/Notification/NolejNotificationPrefRepository.php");

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationPluginProvider;
// use ILIAS\Nolej\Notification\NolejNotificationPrefRepository;
// use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationProvider;
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
		// // global $DIC;
		$lng = $this->dic->language();
		$ui = $this->dic->ui();
		$user = $this->dic->user();
		$ctrl = $this->dic->ctrl();
		$plugin = ilNolejPlugin::getInstance();

		$noti_repo = new NolejNotificationPrefRepository($user);

		// $lng->loadLanguageModule("badge");

		$factory = $this->notification_factory;
		$id = function (string $id) : IdentificationInterface {
            return $this->if->identifier($id);
        };

		$new_activities = NolejActivity::getActivitiesForUser(
			$user->getId(),
			$noti_repo->getLastCheckedTimestamp()
		);

		if (count($new_activities) == 0) {
			return [];
		}

		// Creating a Nolej Notification Item
		$nolej_icon = $ui
			->factory()
			->symbol()
			->icon()
			->custom(
				$plugin->getImagePath("outlined/icon_xnlj.svg"),
				$plugin->txt("plugin_title")
			);

		$group = $factory
			->standardGroup($id('nolej_bucket_group'))
			->withTitle($plugin->txt("plugin_title"))
			->withOpenedCallable(function () {
				// Stuff we do, when the notification is opened
			});

		for ($i = 0, $len = count($new_activities); $i < $len; $i++) {
			$activity = $new_activities[$i];
			$documentTitle = NolejActivity::lookupDocumentTitle($activity["document_id"]);
			$title = $ui->factory()->link()->standard(
				$documentTitle,
				$ctrl->getLinkTargetByClass(["ilDashboardGUI"], "jumpToBadges")
			);
			// $title = $ui->renderer()->render($titleObj);
			$ts = new ilDateTime($activity["tstamp"], IL_CAL_UNIX);

			$nolej_notification_item = $ui
				->factory()
				->item()
				->notification($title, $nolej_icon)
				->withDescription($plugin->txt("action_" . ($activity["action"] ?? "")))
				->withProperties([$lng->txt("time") => ilDatePresentation::formatDate($ts)]);

			$group->addNotification(
				$factory
					->standard($id('nolej_bucket_' . $i))
					->withNotificationItem($nolej_notification_item)
					->withClosedCallable(
						function () use ($noti_repo) {
							// Stuff we do, when the notification is closed
							$noti_repo->updateLastCheckedTimestamp();
						}
					)
					->withNewAmount(1)
			);
		}

		return [$group];
	}
}
