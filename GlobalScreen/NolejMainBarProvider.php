<?php namespace ILIAS\Nolej\Provider;

use ilNolejHandler;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticMainMenuProvider;
use ILIAS\MainMenu\Provider\StandardTopItemsProvider;

/**
 * Class NolejMainBarProvider
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class NolejMainBarProvider extends AbstractStaticMainMenuProvider
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
		$title = "Test Menu"; // $this->dic->language()->txt("mm_badges");
		$icon = $this->dic->ui()->factory()->symbol()->icon()->standard("bdga", $title)->withIsOutlined(true);

		return [
			$this->mainmenu->link($this->if->identifier('mm_pd_badges'))
				->withTitle($title)
				->withAction("ilias.php?baseClass=ilDashboardGUI&cmd=jumpToBadges")
				->withPosition(40)
				->withParent(StandardTopItemsProvider::getInstance()->getAchievementsIdentification())
				->withSymbol($icon)
				->withNonAvailableReason($this->dic->ui()->factory()->legacy("{$this->dic->language()->txt('component_not_active')}"))
				->withAvailableCallable(
					function () {
						return (bool) (ilBadgeHandler::getInstance()->isActive());
					}
				),
		];
	}
}
