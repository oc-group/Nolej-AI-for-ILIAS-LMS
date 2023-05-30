<?php
include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Nolej/classes/class.ilNolejConfig.php");

/**
 * Plugin configuration class
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 * 
 * @ilCtrl_isCalledBy ilNolejGUI: ilUIPluginRouterGUI
 */
class ilNolejGUI
{
	const CMD_SHOW_MODULES = "showModules";
	// const CMD_CART_SHOW = "showCart";
	// const CMD_CART_ADD = "addToCart";
	// const CMD_CART_REMOVE = "removeFromCart";
	// const CMD_CART_EMPTY = "emptyCart";
	// const CMD_PURCHASE = "purchase";
	// const CMD_PURCHASE_CHECK = "checkPurchase";
	// const CMD_PURCHASE_LIST = "listPurchased";
	// const CMD_PURCHASE_FILTER_APPLY = "purchasedApplyFilter";
	// const CMD_PURCHASE_FILTER_RESET = "purchasedResetFilter";
	// const CMD_PARTNERS = "showPartners";
	const CMD_FILTER_APPLY = "applyFilter";
	const CMD_FILTER_RESET = "resetFilter";

	const TAB_MODULES = "modules";
	// const TAB_CART = "cart";
	// const TAB_PURCHASED = "purchased";
	// const TAB_PARTNERS = "partners";

	/** @var ilCtrl */
	protected $ctrl;

	/** @var ilTabsGUI */
	protected $tabs;

	/** @var ilDBInterface */
	protected $db;

	/** @var ilLanguage */
	protected $lng;

	/** @var ilNolejPlugin */
	protected $plugin;

	public function __construct()
	{
		global $DIC, $tpl;
		$this->ctrl = $DIC->ctrl();
		$this->tabs = $DIC->tabs();
		$this->db = $DIC->database();
		$this->lng = $DIC->language();

		$this->plugin = ilNolejPlugin::getInstance();
	}

	/**
	 * Handles all commmands,
	 * $cmd = functionName()
	 */
	public function executeCommand()
	{
		global $tpl;
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : self::CMD_SHOW_MODULES;

		$tpl->loadStandardTemplate();
		$tpl->setTitleIcon(ilNolejPlugin::PLUGIN_DIR . "/templates/images/icon_xnlj.svg");
		$tpl->setTitle($this->plugin->txt("plugin_title"), false);

		if (!$this->plugin->canAccessModules()) {
			ilUtil::sendFailure($this->plugin->txt("err_modules_denied"), true);
			ilUtil::redirect(ILIAS_HTTP_PATH);
			return false;
		}

		switch ($cmd) {
			// Need to have permission to access modules
			case self::CMD_SHOW_MODULES:
			// case self::CMD_PARTNERS:
			case self::CMD_FILTER_APPLY:
			case self::CMD_FILTER_RESET:
				$this->$cmd();
				break;

			// Need to be registered AND to have permission to access cart
			// case self::CMD_CART_SHOW:
			// case self::CMD_CART_ADD:
			// case self::CMD_CART_REMOVE:
			// case self::CMD_CART_EMPTY:
			// case self::CMD_PURCHASE:
			// case self::CMD_PURCHASE_LIST:
			// case self::CMD_PURCHASE_CHECK:
			// case self::CMD_PURCHASE_FILTER_APPLY:
			// case self::CMD_PURCHASE_FILTER_RESET:
			// 	if ($this->config->isRegistered() && $this->plugin->canAccessCart()) {
			// 		$this->$cmd();
			// 	} else {
			// 		$this->showModules();
			// 	}
			// 	break;

			default:
				$this->showModules();
		}

		$tpl->printToStdout();

		return true;
	}

	/**
	 * Init and activate tabs
	 */
	protected function initTabs($active_tab = null)
	{
		global $tpl;

		$this->tabs->addTab(
			self::TAB_MODULES,
			$this->plugin->txt("tab_" . self::TAB_MODULES),
			$this->ctrl->getLinkTarget($this, self::CMD_SHOW_MODULES)
		);

		// if ($this->config->isRegistered() && $this->plugin->canAccessCart()) {
		// 	$count = $this->cart->count();

		// 	if ($count > 0) {
		// 		$this->tabs->addTab(
		// 			self::TAB_CART,
		// 			sprintf($this->plugin->txt("tab_" . self::TAB_CART), $count),
		// 			$this->ctrl->getLinkTarget($this, self::CMD_CART_SHOW)
		// 		);
		// 	}

		// 	$this->tabs->addTab(
		// 		self::TAB_PURCHASED,
		// 		$this->plugin->txt("tab_" . self::TAB_PURCHASED),
		// 		$this->ctrl->getLinkTarget($this, self::CMD_PURCHASE_LIST)
		// 	);
		// }

		// Direct link to plugin configuration
		// if ($this->plugin->isAdmin()) {
		// 	include_once(ilNolejPlugin::PLUGIN_DIR . "/classes/class.ilNolejConfigGUI.php");
		// 	$this->tabs->addTab(
		// 		ilNolejConfigGUI::TAB_CONFIGURE,
		// 		($this->config->isRegistered() ? "" : "<i class='glyphicon glyphicon-alert'></i> ") // Add a warning if not yet registered
		// 		. $this->plugin->txt("tab_" . ilNolejConfigGUI::TAB_CONFIGURE),
		// 		$this->plugin->getConfigurationLink()
		// 	);
		// }

		// $this->tabs->addTab(
		//	self::TAB_PARTNERS,
		//	$this->plugin->txt("tab_" . self::TAB_PARTNERS),
		//	$this->ctrl->getLinkTarget($this, self::CMD_PARTNERS)
		// );

		switch ($active_tab) {
			case self::TAB_MODULES:
			// case self::TAB_PURCHASED:
			// case self::TAB_PARTNERS:
				$this->tabs->activateTab($active_tab);
				$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("tab_" . $active_tab), false);
				break;

			// case self::TAB_CART:
			// 	$this->tabs->activateTab($active_tab);
			// 	$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("cart"), false);
			// 	break;

			default:
				$this->tabs->activateTab(self::TAB_MODULES);
				$tpl->setTitle($this->plugin->txt("plugin_title") . ": " . $this->plugin->txt("tab_" . self::TAB_MODULES), false);
		}

		$tpl->setDescription($this->plugin->txt("plugin_description"));
	}

	protected function getPermalinkGUI($idPartner, $idCourse)
	{
		$tpl = new ilTemplate(
			"tpl.permanent_link.html",
			true,
			true,
			"Services/PermanentLink"
		);

		include_once('./Services/Link/classes/class.ilLink.php');
		$href = $this->config->getPermalink($idPartner, $idCourse);

		$tpl->setVariable("LINK", $href);
		$tpl->setVariable("ALIGN", "left");

		return $tpl->get();
	}

	protected function renderCourseInfo($course)
	{
		// global $DIC;

		// include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		// $info = new ilInfoScreenGUI($this);
		// $info->setFormAction($this->ctrl->getFormAction($this, self::CMD_SHOW_MODULES));

		// // $info->enablePrivateNotes(false);
		// // $info->enableLearningProgress(false);
		// // $info->enableAvailability(true);
		// // $info->enableBookingInfo(false);
		// // $info->enableFeedback(false);
		// // $info->enableNews(false);
		// // $info->enableNewsEditing(false);

		// //if ($ilAccess->checkAccess("write", "", $_GET["ref_id"])) {
		// //		$info->enableNewsEditing();
		// //}

		// $this->lng->loadLanguageModule('crs');
		// $info->addSection($this->lng->txt("crs_general_informations"));
		// $info->addProperty(
		// 	"<img style='max-width: 90%;' src='" . $course->image . "'>",
		// 	"<h1>" . $course->name . "</h1>" . $this->config->renderProperties(
		// 		array(
		// 			$this->plugin->txt("prop_price") => $course->price,
		// 			$this->plugin->txt("prop_category") => $this->plugin->txt("cat_" . $course->category) . " &raquo; " . $this->plugin->txt("subcat_" . $course->subcategory),
		// 			$this->plugin->txt("prop_review") => $this->config->buildReviewString($course->review),
		// 			$this->plugin->txt("prop_language") => $course->language
		// 		)
		// 	)
		// );

		// $info->addProperty($this->plugin->txt("prop_teacher"), $course->teacher);

		// $btnBack = ilLinkButton::getInstance();
		// $btnBack->setCaption($this->plugin->txt("cmd_back_to_modules"), false);
		// $btnBack->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SHOW_MODULES));
		// $DIC->toolbar()->addButtonInstance($btnBack);

		// if ($this->config->isRegistered() && $this->plugin->canAccessCart()) {
		// 	$btnCart = ilLinkButton::getInstance();
		// 	if ($this->cart->isIn($course->id_partner, $course->id_course)) {
		// 		$btnCart->setCaption($this->plugin->txt("cart_remove"), false);
		// 		$btnCart->setUrl(
		// 			$this->ctrl->getLinkTarget($this, self::CMD_CART_REMOVE) .
		// 			"&id_partner=" . $course->id_partner . "&id_course=" . $course->id_course
		// 		);
		// 	} else {
		// 		$btnCart->setCaption($this->plugin->txt("cart_add"), false);
		// 		$btnCart->setUrl(
		// 			$this->ctrl->getLinkTarget($this, self::CMD_CART_ADD) .
		// 			"&id_partner=" . $course->id_partner . "&id_course=" . $course->id_course
		// 		);
		// 	}
		// 	$btnCart->setPrimary(true);
		// 	$DIC->toolbar()->addButtonInstance($btnCart);
		// }

		// $info->addProperty($this->plugin->txt("prop_description"), nl2br($course->description));

		// if (!empty($course->structure)) {
		// 	$structure = "<ol>";
		// 	for ($i = 0, $n = count($course->structure); $i < $n; $i++) {
		// 		$structure .= "<li>" . $course->structure[$i]->title . "<ol>";
		// 		//$structure .= "<li>" . count($course->structure[$i]->pages) . "</li>";
		// 		for ($j = 0, $m = count($course->structure[$i]->pages); $j < $m; $j++) {
		// 			$structure .= "<li>" . $course->structure[$i]->pages[$j]->title . "</li>";
		// 		}
		// 		$structure .= "</ol></li>";
		// 	}
		// 	$structure .= "</ol>";

		// 	$info->addProperty($this->plugin->txt("prop_structure"), $structure);
		// }

		// $info->addSection($this->lng->txt("additional_info"));
		// $info->addProperty(
		// 	$this->lng->txt("perma_link"),
		// 	$this->getPermalinkGUI($course->id_partner, $course->id_course)
		// );

		// return $info->getHTML();
	}

	protected function showCourse($idPartner, $idCourse)
	{
		// global $tpl;

		// $course = $this->config->api(array(
		// 	"cmd" => "details",
		// 	"id_partner" => $idPartner,
		// 	"id_course" => $idCourse
		// ));

		// switch ($course) {
		// 	case "err_course_id":
		// 	case "err_partner_id":
		// 	case "err_maintenance":
		// 	case "err_response":
		// 		ilUtil::sendFailure($this->plugin->txt($course), true);
		// 		return false;
		// }

		// $tpl->setTitle($course->name, false);
		// $tpl->setContent($this->renderCourseInfo($course));

		return true;
	}

	public function showModules()
	{
		// global $tpl;
		// $this->initTabs(self::TAB_MODULES);
		// $tpl->addCss(ilNolejPlugin::CSS);

		// $idPartner = $this->config->getIdPartner();
		// $idCourse = $this->config->getIdCourse();
		// if($idPartner !== false && $idCourse !== false) {
		// 	if($this->showCourse($idPartner, $idCourse)) {
		// 		return;
		// 	}

		// 	// ilUtil::sendFailure($this->plugin->txt("err_course_id"), true);
		// }

		// // No course selected, show the modules
		// $offset = $this->config->getOffset();
		// $rows = $this->config->getRows();

		// $this->plugin->setPermanentLink("modules");

		// $list = new ilTemplate("tpl.modules.html", true, true, ilNolejPlugin::PLUGIN_DIR);

		// $list->setCurrentBlock("partner");
		// $list->setVariable("TITLE", $this->plugin->txt("modules"));

		// $result = $this->config->api(array(
		// 	"cmd" => "modules",
		// 	"rows" => $rows,
		// 	"offset" => $offset,
		// 	"filters" => $this->config->lookupFilters()
		// ));

		// if ($result == "err_maintenance") {
		// 	ilUtil::sendFailure($this->plugin->txt("err_maintenance"), true);
		// 	return;
		// }

		// if (!isset($result->courses)) {
		// 	ilUtil::sendFailure($this->plugin->txt("err_modules") . " - " . print_r($result, true), true);
		// 	return;
		// }

		// $total = $result->total;
		// $courses = $result->courses;
		// $categories = $result->categories;
		// $languages = $result->languages;

		// // ilUtil::sendInfo("ERR: " . print_r($result, true), true);
		// // return;

		// for ($i = 0; $i < count($courses); $i++) {
		// 		$list->setCurrentBlock("item");
		// 		$list->setVariable("ITEM", $this->config->renderCourse($courses[$i]));
		// 		$list->parseCurrentBlock();
		// }

		// $this->config->renderFilter($list, $categories, $languages);

		// if (count($courses) == 0) {
		// 	$list->setCurrentBlock("item");
		// 	$list->setVariable("ITEM", "<br><div class='alert alert-info' role='alert'>" . $this->plugin->txt("filter_empty") . "</div>");
		// 	$list->parseCurrentBlock();
		// 	$list->parseCurrentBlock();
		// } else {
		// 	$list->parseCurrentBlock();
		// 	$link = $this->ctrl->getLinkTarget($this, self::CMD_SHOW_MODULES);
		// 	$this->config->fillHeader($list, $link, $offset, $rows, $total);
		// 	$this->config->fillFooter($list, $link, $offset, $rows, $total);

		// }

		// $tpl->setContent($list->get());
	}

	protected function gotoModules()
	{
		// $offset = $this->config->getOffset();
		// $rows = $this->config->getRows();
		// ilUtil::redirect($this->ctrl->getLinkTarget($this, self::CMD_SHOW_MODULES) . "&rows=" . $rows . "&offset=" . $offset);
	}

	public function showCart()
	{
		// global $tpl;

		// if ($this->cart->count() == 0) {
		// 	ilUtil::sendInfo($this->plugin->txt("cart_is_empty"), true);
		// 	$this->gotoModules();
		// 	return;
		// }

		// $this->initTabs(self::TAB_CART);
		// $tpl->addCss(ilNolejPlugin::CSS);
		// $this->plugin->setPermanentLink("cart");
		// $tpl->setContent($this->cart->get());
	}

	public function addToCart()
	{
		// $this->cart->add();
		// $this->gotoModules();
	}

	public function removeFromCart()
	{
		// $this->cart->remove();
		// ilUtil::sendInfo($this->plugin->txt("cart_removed"), true);
		// $this->gotoModules();
	}

	public function emptyCart()
	{
		// $this->cart->empty();
		// ilUtil::sendInfo($this->plugin->txt("cart_emptied"), true);
		// $this->gotoModules();
	}

	public function purchase()
	{
		// global $tpl;

		// $this->initTabs(self::TAB_CART);
		// $tpl->addCss(ilNolejPlugin::CSS);
		// $tpl->setContent($this->cart->purchase());
	}

	public function listPurchased()
	{
		// global $tpl;
		// $this->initTabs(self::TAB_PURCHASED);

		// // Insert table
		// $this->plugin->setPermanentLink("orders");
		// $table = new ilNolejOrderTableGUI($this, self::CMD_PURCHASE_LIST, true);
		// $table->getItems();
		// $tpl->setContent($table->getHTML());
	}

	public function purchasedApplyFilter()
	{
		// $table = new ilNolejOrderTableGUI($this, self::CMD_PURCHASE_LIST);
		// $table->resetOffset();
		// $table->writeFilterToSession();
		// $this->listPurchased();
	}

	public function purchasedResetFilter()
	{
		// $table = new ilNolejOrderTableGUI($this, self::CMD_PURCHASE_LIST);
		// $table->resetOffset();
		// $table->resetFilter();
		// $this->listPurchased();
	}

	/**
	 * @return string
	 */
	public function buildIcon($id, $alt = "")
	{
		return '<img border="0" align="middle"'
		. ' src="' . ilUtil::getImagePath($id . ".svg") . '"'
		. ' alt="' . ($alt == "" ? "" : $this->lng->txt($alt)) . '" /> ';
	}

	public function showPartners()
	{
		// global $tpl;
		// $this->initTabs(self::TAB_PARTNERS);

		// // TODO
		// ilUtil::sendInfo("Work in progress", true);
	}

	public function checkPurchase()
	{
		// global $DIC, $tpl;

		// $purchase = $this->cart->checkPurchase();
		// $courses = $purchase["courses"];
		// $idPartner = $purchase["id_partner"];

		// $this->initTabs(self::TAB_PURCHASED);
		// $tpl->addCss(ilNolejPlugin::CSS);

		// $list = new ilTemplate("tpl.modules.html", true, true, ilNolejPlugin::PLUGIN_DIR);

		// $idCourses = array_column($courses, "id_course");
		// $result = $this->config->api(array(
		// 	"cmd" => "modules",
		// 	"subset" => array(
		// 		$idPartner => $idCourses
		// 	)
		// ));

		// if ($result == "err_maintenance") {
		// 	ilUtil::sendFailure($this->plugin->txt("err_maintenance"), true);
		// 	return;
		// }

		// $catalogue = $result->courses;
		// $this->plugin->setPermanentLink("order_" . $purchase["id_order"]);

		// $total = 0;
		// for ($i = 0, $len = count($catalogue); $i < $len; $i++) {
		// 	$course = $catalogue[$i];
		// 	$course->quantity = $courses[$i]->quantity;

		// 	// TODO: change how properties work
		// 	$course->locked = true;
		// 	$course->fixedQuantity = true;

		// 	$list->setCurrentBlock("item");
		// 	$list->setVariable("ITEM", $this->config->renderCourse($course));
		// 	$list->parseCurrentBlock();
		// 	$total++;
		// }

		// $list->setCurrentBlock("partner");
		// $list->parseCurrentBlock();

		// // $ul = "<ul>";
		// // for ($i = 0, $len = count($courses); $i < $len; $i++) {
		// // 	$ul .= "<li>" . $courses[$i]->text . "</li>";
		// // }
		// // $ul .= "</ul>";

		// $tpl->setContent($list->get());
	}

	/*
	 * Report table
	 */
	public function report()
	{
		// global $tpl;
		//
		// $this->initTabs("report");
		//
		// // Insert table
		// include_once("class.ilNolejReportTableGUI.php");
		// $table = new ilNolejReportTableGUI($this, "report");
		// $table->getItems();
		// $tpl->setContent($table->getHTML());
	}

	/**
	 * Apply filter
	 */
	public function applyFilter()
	{
		$this->config->applyFilter();
		$this->showModules();
	}

	/**
	 * Reset filter
	 */
	public function resetFilter()
	{
		$this->config->resetFilter();
		$this->showModules();
	}

	public function addUserAutoComplete()
	{
		// include_once './Services/User/classes/class.ilUserAutoComplete.php';
		// $auto = new ilUserAutoComplete();
		// $auto->addUserAccessFilterCallable([$this, 'filterUserIdsByRbacOrPositionOfCurrentUser']);
		// $auto->setSearchFields(array(
		// 	'login',
		// 	'firstname',
		// 	'lastname',
		// 	'email')); // , 'second_email'
		// $auto->enableFieldSearchableCheck(false);
		// $auto->setMoreLinkAvailable(true);
		//
		// if (($_REQUEST['fetchall'])) {
		// 	$auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
		// }
		//
		// echo $auto->getList($_REQUEST['term']);
		// exit();
	}

	/**
	 * @param int[] $user_ids
	 */
	public function filterUserIdsByRbacOrPositionOfCurrentUser($user_ids = [])
	{
		// global $DIC;
		// $access = $DIC->access();
		//
		// return $access->filterUserIdsByRbacOrPositionOfCurrentUser(
		// 	'read_users',
		// 	\ilObjUserFolder::ORG_OP_EDIT_USER_ACCOUNTS,
		// 	USER_FOLDER_ID,
		// 	$user_ids
		// );
	}
}
