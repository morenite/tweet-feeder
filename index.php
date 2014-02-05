<?php
    require './config/const.php';
    require './config/database.php';
    require './config/secret.php';
    
    require './includes/TweetFeeder.php';
    
    $action = $_GET["action"];
    $user = $_GET["user"];
    
    $feeder = new TweetFeeder(array(
        "action" => $action,
        "user" => $user,
        "database" => new CONFIG_DATABASE(),
        "secret" => new CONFIG_SECRET()
    ));
    
    $feeder->feed();
    
    