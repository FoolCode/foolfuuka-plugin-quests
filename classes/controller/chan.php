<?php

namespace Foolz\Foolfuuka\Controller\Chan;

use Foolz\Foolframe\Model\Plugins;
use Foolz\Foolframe\Model\Uri;
use Foolz\Foolfuuka\Model\Search;
use Foolz\Plugin\Plugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Quests extends \Foolz\Foolfuuka\Controller\Chan
{
    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @var Uri
     */
    protected $uri;

    public function before()
    {
        $this->plugin = $this->getContext()->getService('plugins')->getPlugin('foolz/foolfuuka-plugin-quests');
        $this->uri = $this->getContext()->getService('uri');

        parent::before();
    }

    public function radix_page($page = 1)
    {
        $this->response = new StreamedResponse();

        $search = [
            'subject' => 'Quest',
            'type' => 'op',
            'page' => $page
        ];

        try {
            $board = Search::forge($this->getContext())
                ->getSearch($search)
                ->setRadix($this->radix)
                ->setPage($search['page']);

            $board->getComments();
        } catch (\Foolz\Foolfuuka\Model\SearchException $e) {
            return $this->error($e->getMessage());
        } catch (\Foolz\Foolfuuka\Model\BoardException $e) {
            return $this->error($e->getMessage());
        } catch (\Foolz\SphinxQL\ConnectionException $e) {
            return $this->error("It appears that the search engine is offline at the moment. Please try again later.");
        }

        $content = $this->builder->createPartial('body', 'board');
        $content->getParamManager()->setParam('board', $board->getComments());

        $this->param_manager->setParam('pagination', [
            'base_url' => $this->uri->create([$this->radix->shortname, 'quests', 'page']),
            'current_page' => $search['page'] ? : 1,
            'total' => ceil($board->getCount()/25),
        ]);

        $this->param_manager->setParam('modifiers', [
            'post_show_board_name' => $this->radix === null,
            'post_show_view_button' => true
        ]);

        $this->response->setCallback(function() {
            $this->builder->stream();
        });

        return $this->response;
    }
}
