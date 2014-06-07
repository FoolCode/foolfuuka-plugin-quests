<?php

use Doctrine\DBAL\Schema\Schema;
use Foolz\Foolframe\Model\Autoloader;
use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolframe\Model\Plugins;
use Foolz\Foolframe\Model\Uri;
use Foolz\Foolfuuka\Model\RadixCollection;
use Foolz\Plugin\Event;
use Symfony\Component\Routing\Route;

class HHVM_Quests
{
    public function run()
    {
        Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-quests')
            ->setCall(function ($plugin) {
                /** @var Context $context */
                $context = $plugin->getParam('context');
                $context->getContainer()
                    ->register('foolfuuka-plugin.quests', 'Foolz\Foolfuuka\Plugins\Quests\Model\Quests')
                    ->addArgument($context);

                /** @var Autoloader $autoloader */
                $autoloader = $context->getService('autoloader');
                $autoloader->addClassMap([
                    'Foolz\Foolfuuka\Controller\Chan\Quests' => __DIR__.'/classes/controller/chan.php'
                ]);

                Event::forge('Foolz\Foolframe\Model\Context.handleWeb.route_collection')
                    ->setCall(function ($result) use ($context) {
                        $radix_collection = $context->getService('foolfuuka.radix_collection');
                        $radices = $radix_collection->getAll();

                        foreach ($radices as $radix) {
                            if (!$radix->getValue('plugin_quests')) {
                                continue;
                            }

                            $routes = $result->getObject();
                            $routes->getRouteCollection()->add(
                                'foolfuuka.plugin.quests.chan.radix.'.$radix->shortname, new Route(
                                    '/'.$radix->shortname.'/quests/{_suffix}',
                                    [
                                        '_controller' => '\Foolz\Foolfuuka\Controller\Chan\Quests::*',
                                        '_default_suffix' => 'page',
                                        '_suffix' => '',
                                        'radix_shortname' => $radix->shortname
                                    ],
                                    [
                                        '_suffix' => '.*'
                                    ]
                                )
                            );
                        }
                    });

                Event::forge('Foolz\Foolframe\Model\Context.handleWeb.has_auth')
                    ->setCall(function ($result) use ($context) {
                        Event::forge('foolframe.themes.generic_top_nav_buttons')
                            ->setCall(function ($nav) {
                                $obj = $nav->getObject();
                                $top = $nav->getParam('nav');
                                if ($obj->getRadix() && $obj->getRadix()->getValue('plugin_quests')) {
                                    $top[] = ['href' => $obj->getUri()->create([$obj->getRadix()->shortname, 'quests']), 'text' => _i('Quests')];
                                    $nav->setParam('nav', $top)->set($top);
                                }
                            })->setPriority(1);
                    });

                Event::forge('Foolz\Foolfuuka\Model\Radix::structure.result')
                    ->setCall(function ($result) {
                        $structure = $result->getParam('structure');
                        $structure['plugin_quests'] = [
                            'database' => true,
                            'boards_preferences' => true,
                            'type' => 'checkbox',
                            'help' => _i('Enable Quests')
                        ];
                        $result->setParam('structure', $structure)->set($structure);
                    })->setPriority(1);
            });
    }
}

(new HHVM_Quests())->run();
