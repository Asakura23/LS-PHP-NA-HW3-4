<?php

if( !defined( "IN_DEV" ) ) { print $_FORBIDDEN; die(); }

$_DB = array(
    "host"      => "127.0.0.1",
    "user"      => "blog",
    "pass"      => "blog",
    "dbase"     => "blogdb",
);

$_ADMINISTRATORS = array(
	"Hiori"		=> true,
);

?>