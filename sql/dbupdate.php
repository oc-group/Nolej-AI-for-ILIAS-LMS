<#1>
<?php

/* Config */
$fields = array(
	'keyword' => array(
		'type' => 'text',
		'length' => 100,
		'fixed' => false,
		'notnull' => true
	),
	'value' => array(
		'type' => 'text',
		'length' => 200,
		'fixed' => false,
		'notnull' => true
	)
);

if(!$ilDB->tableExists("rep_robj_xnlj_config")) {
	$ilDB->createTable("rep_robj_xnlj_config", $fields);
	$ilDB->addPrimaryKey("rep_robj_xnlj_config", array("keyword"));
}

/* TicTac */
$fields = array(
	'exchangeId' => array(
		'type' => 'text',
		'length' => 100,
		'fixed' => false,
		'notnull' => true
	),
	'requestOn' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'responseOn' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => false
	),
	'message' => array(
		'type' => 'text',
		'length' => 200,
		'fixed' => false,
		'notnull' => true
	),
	'requestUrl' => array(
		'type' => 'text',
		'length' => 400,
		'fixed' => false,
		'notnull' => true
	),
	'responseUrl' => array(
		'type' => 'text',
		'length' => 400,
		'fixed' => false,
		'notnull' => false
	)
);

if(!$ilDB->tableExists("rep_robj_xnlj_tic")) {
	$ilDB->createTable("rep_robj_xnlj_tic", $fields);
	$ilDB->addPrimaryKey("rep_robj_xnlj_tic", array("exchangeId"));
}

/* Object data */
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'is_online' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'id_partner' => array(
		'type' => 'text',
		'length' => 20,
		'fixed' => false,
		'notnull' => false
	),
	'id_course' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);

if(!$ilDB->tableExists("rep_robj_xnlj_data")) {
	$ilDB->createTable("rep_robj_xnlj_data", $fields);
	$ilDB->addPrimaryKey("rep_robj_xnlj_data", array("id"));
}

/* LP */
$fields = array(
	'id_partner' => array(
		'type' => 'text',
		'length' => 20,
		'fixed' => false,
		'notnull' => true
	),
	'id_course' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'id_page' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'status' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'last_change' => array(
		"type" => 'integer',
		'length' => 4,
		"notnull" => false
	)
);

if(!$ilDB->tableExists("rep_robj_xnlj_lp")) {
	$ilDB->createTable("rep_robj_xnlj_lp", $fields);
	$ilDB->addPrimaryKey("rep_robj_xnlj_lp", array("id_partner", "id_course", "id_page", "user_id"));
}

?>
