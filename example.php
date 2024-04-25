<?php
require_once("session.php");
$session = new x_session([
    //Settings
    //The location where your session files will be stored
    "storage"              => "/loc/to/session/storagee",
    //This is salt used in hashing
    "salt"                 => "saltexample",
    "global_token_var"     => "X_SESSION_TOKEN",
    "session_update_var"   => "X_SESSION_COOKIE_UPDATE",
    "session_update_timer" => 1800,

    //Session Cookie Settings
    //Cookie name stored in client browser
    "session_name"         => "SNHS",
    //Cookie expire time in seconds
    "session_expire"       => 10800,

    //Sync Cookie Settings
    //Cookie name stored in client browser
    "sync_name"            => "SCHS",
    //Cookie expire time in seconds
    "sync_expire"          => 10800,
]);

$session->init();
?>