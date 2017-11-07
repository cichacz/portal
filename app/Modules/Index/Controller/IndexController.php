<?php

namespace Portal\Modules\Index\Controller;

use Portal\Common\Model\Link;
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
            $page = Page::get(null, false, array(
                'slug' => 'wprowadzenie'
            ));
        } elseif($page->slug == 'wprowadzenie') {
            $uri = self::$_router->pathFor('');
            return $response->withRedirect($uri)->withStatus(301);
        }

        $args['pages'] = Page::getList();
        $args['links'] = Link::getList();
        $args['title'] = $page->title;
        $args['page'] = $page;

        return $args;
    }
}