<?php

/**
 * @author Vincenzo Padula <vincenzo@oc-group.eu>
 */

class ilNolejWebhook
{
	/** @var \ilNolejPlugin */
	protected $plugin;

	/** @var array */
	protected $data;

	public function __construct()
	{
		$this->plugin = ilNolejPlugin::getInstance();
	}

	public function parse() : void
	{
		header("Content-type: application/json; charset=UTF-8");
		$data = json_decode(file_get_contents("php://input"), true);

		if (
			!is_array($data) ||
			!isset($data["action"]) ||
			!is_string($data["action"])
		) {
			$this->die_message(400, "Request not valid.");
		}

		$this->data = $data;
		switch ($data["action"]) {
			case "tac":
				$this->checkTac();
				break;
		}
		exit;
	}

	protected function die_message(
		int $code = 400,
		string $message = ""
	) : void
	{
		http_response_code($code);
		if (!empty($message)) {
			echo json_encode(["message" => $message]);
		}
		exit;
	}

	public function checkTac() : void
	{
		global $DIC;

		if (
			!isset($this->data["exchangeId"], $this->data["message"], $this->data["s3URL"]) ||
			!is_string($this->data["exchangeId"]) ||
			!is_string($this->data["message"]) ||
			!is_string($this->data["s3URL"])
		) {
			$this->die_message(400, "Request not valid.");
			return;
		}

		$db = $DIC->database();
		$exchangeId = $this->data["exchangeId"];

		$sql = "SELECT * FROM " . ilNolejPlugin::TABLE_TIC . " WHERE exchange_id = %s AND response_on IS NULL;";
		$result = $db->queryF(
			$sql,
			["text"],
			[$exchangeId]
		);
		if ($db->numRows($result) != 1) {
			$this->die_message(404, "Exchange not found.");
			return;
		}

		$exchange = $db->fetchAssoc($result);

		$now = strtotime("now");
		$sql = "UPDATE " . ilNolejPlugin::TABLE_TIC . " SET response_on = %s, response_url = %s WHERE exchange_id = %s;";
		$result = $db->manipulateF(
			$sql,
			["integer", "text", "text"],
			[$now, $this->data["s3URL"], $exchangeId]
		);
		if (!$result) {
			$this->die_message(404, "Exchange not found.");
		}

		require_once "Services/Notifications/classes/class.ilNotificationConfig.php";
		$recipient_id = $exchange["user_id"];
		$sender_id = SYSTEM_USER_ID;

		$notification = new ilNotificationConfig("osd_maint");
		$notification->setTitleVar("tac_received", [], ilNolejPlugin::PREFIX);
		$notification->setShortDescriptionVar("tac_received_info_short", [], ilNolejPlugin::PREFIX);
		$notification->setLongDescriptionVar(
			"tac_received_info_long",
			[
				"EXCHANGE_ID" => $exchangeId,
				"RECEIVED_ON" => ilDatePresentation::formatDate(new ilDateTime($now, IL_CAL_UNIX))
			],
			ilNolejPlugin::PREFIX
		);
		$notification->setAutoDisable(false);
		$notification->setValidForSeconds(0);
		$notification->setHandlerParam('mail.sender', $sender_id);
		$notification->notifyByUsers([$recipient_id]);

		$this->die_message(200, "TAC received!");
	}

	public function getOffset($default = 0)
	{
		return $this->getParameterPositive("offset", $default);
	}

	public function getRows($default = 20)
	{
		return $this->getParameterPositive("rows", $default);
	}

	public function getPermalink($idPartner, $idCourse)
	{
		return sprintf(
			"%s/goto.php?target=%s_course_%s_%s",
			ILIAS_HTTP_PATH,
			ilNolejPlugin::PLUGIN_ID,
			$idPartner,
			$idCourse
		);
	}

	public function fillHeader(&$tpl, $link, $offset, $rows, $total)
	{
		// global $DIC;
		// $start = $offset + 1;
		// $end = min($offset + $rows, $total);
		// $numinfo = "(" . $start . " - " . $end . " " . ($DIC->language() ? strtolower($DIC->language()->txt("of")) : "of") . " " . $total . ")";
		// $tpl->setCurrentBlock("top_numinfo");
		// $tpl->setVariable("NUMINFO", $numinfo);
		// $tpl->parseCurrentBlock();

		// if ($rows >= $total && $offset == 0) {
		// 	// No need to show the linkbar
		// 	return;
		// }

		// $pageLink = $link . "&rows=" . $rows . "&offset=";

		// // Calculate number of pages
		// $pages = intval($total / $rows);

		// // Add a page if a rest remains
		// if (($total % $rows)) {
		// 	$pages++;
		// }

		// $layout_prev = $DIC->language() ? $DIC->language()->txt("previous") : "<<<";
		// $layout_next = $DIC->language() ? $DIC->language()->txt("next") : ">>>";
		// $layout_page = $DIC->language() ? $DIC->language()->txt("page") : "Page";
		// $sep = "<span>&nbsp;&nbsp;&nbsp;&nbsp;</span>";
		// $linkBar = "";

		// // Links to other pages
		// $offset_arr = array();
		// for ($i = 1; $i <= $pages; $i++) {
		// 	$newOffset = $rows * ($i - 1);
		// 	// $nav_value = $this->getOrderField() . ":" . $this->getOrderDirection() . ":" . $newOffset;
		// 	$nav_value = $newOffset;
		// 	$offset_arr[$nav_value] = $i;
		// }

		// if ($linkBar != "") {
		// 	$linkBar .= $sep;
		// }

		// // Show previous link
		// if ($offset > 0) {
		// 	$prevoffset = $offset - $rows;
		// 	$linkBar .= "<a href=\"" . $pageLink . $prevoffset . "\">" . $layout_prev . "</a>";
		// } else {
		// 	$linkBar .= '<span class="ilTableFootLight">' . $layout_prev . "</span>";
		// }

		// if ($linkBar != "") {
		// 	$linkBar .= $sep;
		// }

		// // Show next link
		// if (!(($offset / $rows) == ($pages - 1)) && ($pages != 1)) {
		// 	$newoffset = $offset + $rows;
		// 	$linkBar .= "<a href=\"" . $pageLink . $newoffset . "\">" . $layout_next . "</a>";
		// } else {
		// 	$linkBar .= '<span class="ilTableFootLight">' . $layout_next . "</span>";
		// }

		// if (count($offset_arr)) {
		// 	$linkBar .= $sep .
		// 		'<label for="nlj_page_sel">' . $layout_page . '</label> ' .
		// 		ilUtil::formSelect(
		// 			$offset,
		// 			$this->plugin::PLUGIN_ID . "1",
		// 			$offset_arr,
		// 			false,
		// 			true,
		// 			0,
		// 			"small",
		// 			array(
		// 				"id" => "nlj_page_sel",
		// 				"onchange" => "window.location = '" . $pageLink . "' + this.value"
		// 			)
		// 		);
		// }

		// $tpl->setCurrentBlock("top_linkbar");
		// $tpl->setVariable("LINKBAR", $linkBar);
		// $tpl->parseCurrentBlock();


		// // Number of rows
		// $alist = new ilAdvancedSelectionListGUI();
		// $alist->setStyle(ilAdvancedSelectionListGUI::STYLE_LINK_BUTTON);

		// $options = array(
		// 	10 => 10, 15 => 15, 20 => 20,
		// 	30 => 30, 40 => 40, 50 => 50
		// );
		// foreach ($options as $key => $text) {
		// 	$alist->addItem($text, $key, $link . "&rows=" . $key . "&offset=" . $offset);
		// }
		// $alist->setListTitle($this->plugin->txt("prop_rows"));
		// $tpl->setVariable("ROW_SELECTOR", $alist->getHTML());
	}

	public function fillFooter(&$tpl, $link, $offset, $rows, $total)
	{
		// global $DIC;
		// $start = $offset + 1;
		// $end = min($offset + $rows, $total);
		// $numinfo = "(" . $start . " - " . $end . " " . ($DIC->language() ? strtolower($DIC->language()->txt("of")) : "of") . " " . $total . ")";
		// $tpl->setCurrentBlock("tbl_footer_numinfo");
		// $tpl->setVariable("NUMINFO", $numinfo);
		// $tpl->parseCurrentBlock();

		// if ($rows >= $total && $offset == 0) {
		// 	// No need to show the linkbar
		// 	return;
		// }

		// $link .= "&rows=" . $rows . "&offset=";

		// // Calculate number of pages
		// $pages = intval($total / $rows);

		// // Add a page if a rest remains
		// if (($total % $rows)) {
		// 	$pages++;
		// }

		// $layout_prev = $DIC->language() ? $DIC->language()->txt("previous") : "<<<";
		// $layout_next = $DIC->language() ? $DIC->language()->txt("next") : ">>>";
		// $layout_page = $DIC->language() ? $DIC->language()->txt("page") : "Page";
		// $sep = "<span>&nbsp;&nbsp;&nbsp;&nbsp;</span>";
		// $linkBar = "";

		// // Links to other pages
		// $offset_arr = array();
		// for ($i = 1; $i <= $pages; $i++) {
		// 	$newOffset = $rows * ($i - 1);
		// 	// $nav_value = $this->getOrderField() . ":" . $this->getOrderDirection() . ":" . $newOffset;
		// 	$nav_value = $newOffset;
		// 	$offset_arr[$nav_value] = $i;
		// }

		// if ($linkBar != "") {
		// 	$linkBar .= $sep;
		// }

		// // Show previous link
		// if ($offset > 0) {
		// 	$prevoffset = $offset - $rows;
		// 	$linkBar .= "<a href=\"" . $link . $prevoffset . "\">" . $layout_prev . "</a>";
		// } else {
		// 	$linkBar .= '<span class="ilTableFootLight">' . $layout_prev . "</span>";
		// }

		// if ($linkBar != "") {
		// 	$linkBar .= $sep;
		// }

		// // Show next link
		// if (!(($offset / $rows) == ($pages - 1)) && ($pages != 1)) {
		// 	$newoffset = $offset + $rows;
		// 	$linkBar .= "<a href=\"" . $link . $newoffset . "\">" . $layout_next . "</a>";
		// } else {
		// 	$linkBar .= '<span class="ilTableFootLight">' . $layout_next . "</span>";
		// }

		// if (count($offset_arr)) {
		// 	$linkBar .= $sep .
		// 		'<label for="nlj_page_sel">' . $layout_page . '</label> ' .
		// 		ilUtil::formSelect(
		// 			$offset,
		// 			$this->plugin::PLUGIN_ID . "1",
		// 			$offset_arr,
		// 			false,
		// 			true,
		// 			0,
		// 			"small",
		// 			array(
		// 				"id" => "nlj_page_sel",
		// 				"onchange" => "window.location = '" . $link . "' + this.value"
		// 			)
		// 		);
		// }

		// $tpl->setCurrentBlock("tbl_footer_linkbar");
		// $tpl->setVariable("LINKBAR", $linkBar);
		// $tpl->parseCurrentBlock();
	}

	public function lookupFilters()
	{
		// if($this->filters != null) {
		// 	return $this->filters;
		// }

		// $filters = [
		// 	self::FILTER_DESCRIPTION,
		// 	self::FILTER_CATEGORY,
		// 	self::FILTER_MIN_PRICE,
		// 	self::FILTER_MAX_PRICE,
		// 	self::FILTER_MIN_REVIEW,
		// 	self::FILTER_LANGUAGE
		// ];
		// $this->filters = [];
		// for ($i = 0, $n = count($filters); $i < $n; $i++) {
		// 	if (isset($_SESSION[$filters[$i]])) {
		// 		$this->filters[$filters[$i]] = $_SESSION[$filters[$i]];
		// 	}
		// }

		// return $this->filters = $_SESSION["nlj_filters"];
	}

	protected function saveFilter($keyword, $value)
	{
		// $this->filters[$keyword] = $value;
		// $_SESSION["nlj_filters"][$keyword] = $value;
	}

	public function getFilters($givenCategories = null, $givenLanguages = null)
	{
		// include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		// $description = new ilTextInputGUI($this->plugin->txt("filter_description"), self::FILTER_DESCRIPTION);
		// $description->setMaxLength(100);
		// $description->setSize(20);

		// include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		// $category = new ilSelectInputGUI($this->plugin->txt("filter_category"), self::FILTER_CATEGORY);

		// if (!$givenCategories) {
		// 	$result = $this->api(array(
		// 		"cmd" => "categories"
		// 	));
		// 	switch ($result) {
		// 		case "err_maintenance":
		// 		case "err_response":
		// 			return [];
		// 		default:
		// 			if (!is_array($result)) {
		// 				return [];
		// 			}
		// 	}

		// 	$givenCategories = $result;
		// }

		// $categories = array(
		// 	"all" => $this->plugin->txt("filter_category_all")
		// );

		// for ($i = 0, $len = count($givenCategories); $i < $len; $i++) {
		// 	$categories[$givenCategories[$i]] = $this->plugin->txt("cat_" . $givenCategories[$i]);
		// }
		// $category->setOptions($categories);

		// $language = new ilSelectInputGUI($this->plugin->txt("filter_language"), self::FILTER_LANGUAGE);

		// if (!$givenLanguages) {
		// 	$result = $this->api(array(
		// 		"cmd" => "languages"
		// 	));
		// 	switch ($result) {
		// 		case "err_maintenance":
		// 		case "err_response":
		// 			return [];
		// 		default:
		// 			if (!is_array($result)) {
		// 				return [];
		// 			}
		// 	}

		// 	$givenLanguages = $result;
		// }

		// $languages = array(
		// 	"all" => $this->plugin->txt("filter_language_all")
		// );

		// for ($i = 0, $len = count($givenLanguages); $i < $len; $i++) {
		// 	$languages[$givenLanguages[$i]] = $givenLanguages[$i];
		// }
		// $language->setOptions($languages);

		// include_once("./Services/Form/classes/class.ilNumberInputGUI.php");
		// $review = new ilNumberInputGUI($this->plugin->txt("filter_review"), self::FILTER_MIN_REVIEW);
		// $review->setMaxLength(1);
		// $review->setSize(20);

		// $min_price = new ilNumberInputGUI($this->plugin->txt("filter_price_min"), self::FILTER_MIN_PRICE);
		// $min_price->setMaxLength(3);
		// $min_price->setSize(20);

		// $max_price = new ilNumberInputGUI($this->plugin->txt("filter_price_max"), self::FILTER_MAX_PRICE);
		// $max_price->setMaxLength(3);
		// $max_price->setSize(20);

		// return array(
		// 	$description,
		// 	$category,
		// 	$language,
		// 	$review,
		// 	$min_price,
		// 	$max_price
		// );
	}

	public function renderFilter(&$tpl, $categories, $languages)
	{
		// global $DIC;

		// // Filters
		// $filters = $this->getFilters($categories, $languages);
		// $values = $this->lookupFilters();

		// for ($i = 0, $len = count($filters); $i < $len; $i++) {
		// 	if ($i % 3 == 0) {
		// 		$tpl->setCurrentBlock("filter_row");
		// 		$tpl->parseCurrentBlock();
		// 	}

		// 	// $filters[$i]->setValueByArray($values);
		// 	$filters[$i]->setValueByArray($values);

		// 	$tpl->setCurrentBlock("filter_item");
		// 	$tpl->setVariable("OPTION_NAME", $filters[$i]->getTitle());
		// 	$tpl->setVariable("F_INPUT_ID", $filters[$i]->getTableFilterLabelFor());
		// 	// $tpl->setVariable("INPUT_HTML", $filters[$i]->getTableFilterHTML());
		// 	$tpl->setVariable("INPUT_HTML", $filters[$i]->render());
		// 	$tpl->parseCurrentBlock();
		// }

		// $tpl->setVariable("FILTER_ACTION", $DIC->ctrl()->getLinkTarget($this->gui_obj, ilNolejGUI::CMD_FILTER_APPLY) . "&rows=" . $this->getRows());

		// $tpl->setCurrentBlock("filter_buttons");
		// // $tpl->setVariable("CMD_APPLY", ilNolejGUI::CMD_FILTER_APPLY);
		// $tpl->setVariable("TXT_APPLY", $this->plugin->txt("filter_apply"));

		// $tpl->setVariable("LINK_RESET", $DIC->ctrl()->getLinkTarget($this->gui_obj, ilNolejGUI::CMD_FILTER_RESET) . "&rows=" . $this->getRows());
		// $tpl->setVariable("TXT_RESET", $this->plugin->txt("filter_reset"));

		// $tpl->setCurrentBlock("filter_section");
		// $tpl->setVariable("FIL_ID", ilNolejPlugin::PLUGIN_ID);
		// $tpl->parseCurrentBlock();
	}

	public function applyFilter()
	{
		// $filters = $this->getFilters();

		// for ($i = 0, $len = count($filters); $i < $len; $i++) {
		// 	if ($filters[$i]->checkInput()) {
		// 		$filters[$i]->setValue($_POST[$filters[$i]->getPostVar()]);
		// 		$this->saveFilter($filters[$i]->getPostVar(), $filters[$i]->getValue());
		// 	}
		// }
	}

	public function resetFilter()
	{
		// $filters = $this->getFilters();

		// for ($i = 0, $len = count($filters); $i < $len; $i++) {
		// 	if (isset($_SESSION["nlj_filters"][$filters[$i]->getPostVar()])) {
		// 		unset($_SESSION["nlj_filters"][$filters[$i]->getPostVar()]);
		// 	}
		// }
		// $this->filters = [];
	}

	public function renderProperties($props)
	{
		// $tpl = new ilTemplate("tpl.properties.html", true, true, ilNolejPlugin::PLUGIN_DIR);

		// $cnt = 0;
		// foreach ($props as $name => $value) {
		// 	$cnt++;
		// 	if ($cnt % 2 == 1) {
		// 		$tpl->setCurrentBlock("property_row");
		// 		$tpl->setVariable("PROP_NAME_A", $name);
		// 		$tpl->setVariable("PROP_VAL_A", $value);
		// 	} else {
		// 		$tpl->setVariable("PROP_NAME_B", $name);
		// 		$tpl->setVariable("PROP_VAL_B", $value);
		// 		$tpl->parseCurrentBlock();
		// 	}
		// }
		// if ($cnt % 2 == 1) {
		// 	$tpl->parseCurrentBlock();
		// }
		// $tpl->setCurrentBlock("properties");
		// $tpl->parseCurrentBlock();

		// return $tpl->get();
	}

}
