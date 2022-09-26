<?php

if (isset($_POST['command'])) {
    if (!@require_once(dirname(__FILE__) . '/game.class.php')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
        echo '<html><title>Error 503: Site temporarily unavailable</title><body><h1>Error 503</h1></body></html>';
        exit();
    }
    
    $gameObj = new SkarbGame();
    if (!is_object($gameObj) || !($gameObj instanceof SkarbGame)) {
        exit();
    }
    
    echo $gameObj->initApiSk($_POST);
}
    