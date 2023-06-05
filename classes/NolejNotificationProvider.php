<?php declare(strict_types=1);

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Nolej\Provider;

// require_once("./src/GlobalScreen/Scope/Notification/Provider/AbstractNotificationPluginProvider.php");

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationPluginProvider;
use ILIAS\Nolej\Notification\NolejNotificationPrefRepository;
use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationProvider;
use ILIAS\GlobalScreen\Scope\Notification\Provider\NotificationProvider;

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
		// $lng = $this->dic->language();
		// $ui = $this->dic->ui();
		// $user = $this->dic->user();
		// $ctrl = $this->dic->ctrl();

		// $lng->loadLanguageModule("badge");

		// $factory = $this->notification_factory;
		// $id = $this->id;

		// $new_activities = \ilNolejActivity::getNewCounter($user->getId());
		// if ($new_activities == 0) {
		// 	return [];
		// }

		// // Creating a Nolej Notification Item
		// $nolej_icon = $ui->factory()->symbol()->icon()->standard("bdga", "NOLEJ")->withIsOutlined(true);
		// $nolej_title = $ui->factory()->link()->standard(
		// 	"Test notification", //$lng->txt("mm_badges"),
		// 	$ctrl->getLinkTargetByClass(["ilDashboardGUI"], "jumpToBadges")
		// );
		// $latest = new \ilDateTime(\ilNolejActivity::getLatestTimestamp($user->getId()), IL_CAL_UNIX);
		// $nolej_notification_item = $ui
		// 	->factory()
		// 	->item()
		// 	->notification($nolej_title, $nolej_icon)
		// 	->withDescription("New Nolej Activity")
		// 	->withProperties([$lng->txt("time") => \ilDatePresentation::formatDate($latest)]);

		// $group = $factory
		// 	->standardGroup($this->id('nolej_bucket_group'))
		// 	->withTitle("Nolej activities")
		// 	->addNotification(
		// 		$factory->standard($this->id('nolej_bucket'))
		// 		->withNotificationItem($nolej_notification_item)
		// 		// ->withClosedCallable(
		// 		// 	function () use ($user) {
		// 		// 		// Stuff we do, when the notification is closed
		// 		// 		$noti_repo = new NolejNotificationPrefRepository($user);
		// 		// 		$noti_repo->updateLastCheckedTimestamp();
		// 		// 	}
		// 		// )
		// 		->withNewAmount($new_activities)
		// 	)
		// 	->withOpenedCallable(function () {
		// 		// Stuff we do, when the notification is opened
		// 	});

		// return [
		// 	$group,
		// ];

		$factory = $this->globalScreen()->notifications()->factory();
        $id = function (string $id) : IdentificationInterface {
            return $this->if->identifier($id);
        };

		$new_mails = 2;
		
        //Creating a mail Notification Item
        $mail_icon = $this->dic->ui()->factory()->symbol()->icon()->standard("mail","mail");
        $mail_title = $this->dic->ui()->factory()->link()->standard("Inbox", 'ilias.php?baseClass=ilMailGUI');
        $mail_notification_item = $this->dic->ui()->factory()->item()->notification($mail_title,$mail_icon)
                                                   ->withDescription("You have $new_mails Mails.")
                                                   ->withProperties(["Time" => "3 days ago"]);

        $group = $factory->standardGroup($id('mail_bucket_group'))->withTitle($this->dic->language()->txt('mail'))
            ->addNotification($factory->standard($id('mail_bucket'))->withNotificationItem($mail_notification_item)                                                      
                ->withClosedCallable(
                    function(){
                        //@Todo: Memories, that those notifications have been closed.
                        var_dump("Mail Notifications received closed event.");
                    })
                ->withNewAmount($new_mails)
            )
            ->withOpenedCallable(function(){
                //@Todo: Memories, that those notifications have been seen.
                var_dump("Mail Notifications received opened event.");
            });

        return [
            $group,
        ];
	}
}
