<?php

use Portal\Common\Model\Page;
use Portal\Core\Model\PortalAuth;

require_once "init.php";

if(PortalAuth::updateSchema()) {
    echo "Auth schema updated \n";
}

if(Page::updateSchema()) {
    echo "Page schema updated \n";
}

if(\Portal\Common\Model\Link::updateSchema()) {
    echo "Link schema updated \n";
}