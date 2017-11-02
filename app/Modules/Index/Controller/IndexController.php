<?php

namespace Portal\Modules\Index\Controller;

use Portal\Common\Model\Page;
use Portal\Core\PortalController;

class IndexController extends PortalController {

    /**
     * @url-param index
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function indexGetAction($request, $response, $args) {
        $page = !empty($args['index']) ? $args['index'] : '';

        $page = Page::get(null, false, array(
            'slug' => $page
        ));

        if(empty($page)) {
            $page = Page::get(1);
        }

        $args['title'] = $page->title;
        $args['page'] = $page;

        return $args;
    }
}