<?php

use Portal\Core\Model\PortalAuth;

require_once "init.php";

if(PortalAuth::updateSchema()) {
    echo "Auth schema updated \n";
}