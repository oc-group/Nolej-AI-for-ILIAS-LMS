<?php declare(strict_types=1);

namespace ILIAS\Nolej\Provider;

// use ilNolejHandler;
// use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticMainMenuProvider;
use ILIAS\MainMenu\Provider\StandardTopItemsProvider;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticPluginMainMenuProvider;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\StaticMainMenuProvider;

/**
 * Class NolejMainBarProvider
 *
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */
class NolejMainBarProvider extends AbstractStaticPluginMainMenuProvider
{

	/**
	 * @inheritDoc
	 */
	public function getStaticTopItems() : array
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getStaticSubItems() : array
	{
		global $DIC;
		$title = "Test Menu"; // $DIC->language()->txt("mm_badges");
		$icon = $DIC->ui()->factory()->symbol()->icon()->standard("bdga", $title)->withIsOutlined(true);

		return [
			$this->mainmenu->link($this->if->identifier('mm_pd_badges'))
				->withTitle($title)
				->withAction("ilias.php?baseClass=ilDashboardGUI&cmd=jumpToBadges")
				->withPosition(40)
				->withParent(StandardTopItemsProvider::getInstance()->getAchievementsIdentification())
				->withSymbol($icon)
				->withNonAvailableReason($DIC->ui()->factory()->legacy("{$DIC->language()->txt('component_not_active')}"))
				->withAvailableCallable(
					function () {
						return true; // (bool) (ilBadgeHandler::getInstance()->isActive());
					}
				),
		];
	}
}
