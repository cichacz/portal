<?php

namespace Portal\Modules\Index\Controller;

use Portal\Core\PortalController;

class IndexController extends PortalController {

    /**
     * @url-param return
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function indexGetAction($request, $response, $args) {

        return $args;
    }
}