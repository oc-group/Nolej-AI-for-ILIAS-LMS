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
	'exchange_id' => array(
		'type' => 'text',
		'length' => 100,
		'fixed' => false,
		'notnull' => true
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'request_on' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'response_on' => array(
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
	'request_url' => array(
		'type' => 'text',
		'length' => 400,
		'fixed' => false,
		'notnull' => true
	),
	'response_url' => array(
		'type' => 'text',
		'length' => 400,
		'fixed' => false,
		'notnull' => false
	)
);

if(!$ilDB->tableExists("rep_robj_xnlj_tic")) {
	$ilDB->createTable("rep_robj_xnlj_tic", $fields);
	$ilDB->addPrimaryKey("rep_robj_xnlj_tic", array("exchange_id"));
}

/* Document data */
$fields = array(
	'document_id' => array(
		'type' => 'text',
		'length' => 50,
		'fixed' => false,
		'notnull' => true
	),
	'status' => array(
		/**
		 *  0 => idle,
		 * 10 => transcripting,
		 * 11 => transcription ready,
		 * 12 => transcription error,
		 * 20 => analyzing,
		 * 21 => analysis ready
		 * 22 => analysis error
		*/
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'consumed_credit' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'doc_url' => array(
		'type' => 'text',
		'length' => 200,
		'fixed' => false,
		'notnull' => true
	),
	'media_type' => array(
		/**
		 * Available: web, audio, video, document, freetext, youtube.
		 * Soon: slide.
		 */
		'type' => 'text',
		'length' => 20,
		'fixed' => false,
		'notnull' => true
	),
	'automatic_mode' => array(
		'type' => 'text',
		'length' => 1,
		'fixed' => false,
		'notnull' => true
	),
	'language' => array(
		'type' => 'text',
		'length' => 5,
		'fixed' => false,
		'notnull' => true
	),
	'transcription' => array(
		'type' => 'text',
		'length' => 10000,
		'fixed' => false,
		'notnull' => false
	),
);

if(!$ilDB->tableExists("rep_robj_xnlj_doc")) {
	$ilDB->createTable("rep_robj_xnlj_doc", $fields);
	$ilDB->addPrimaryKey("rep_robj_xnlj_doc", array("document_id"));
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
	'document_id' => array(
		'type' => 'text',
		'length' => 50,
		'fixed' => false,
		'notnull' => false
	),
);

if(!$ilDB->tableExists("rep_robj_xnlj_data")) {
	$ilDB->createTable("rep_robj_xnlj_data", $fields);
	$ilDB->addPrimaryKey("rep_robj_xnlj_data", array("id"));
}

/* LP */
$fields = array(
	'document_id' => array(
		'type' => 'text',
		'length' => 50,
		'fixed' => false,
		'notnull' => true
	),
	'activity_id' => array(
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
	$ilDB->addPrimaryKey("rep_robj_xnlj_lp", array("document_id", "activity_id", "user_id"));
}

?>
