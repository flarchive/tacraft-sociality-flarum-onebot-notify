<?php

use Flarum\Discussion\Event\Started;
use Flarum\Extend;
use Flarum\Post\Event\Posted;
use TaCraft\FlarumOneBotNotify\Api\TestOneBotController;
use TaCraft\FlarumOneBotNotify\MessageTemplates;
use TaCraft\FlarumOneBotNotify\NewDiscussionListener;
use TaCraft\FlarumOneBotNotify\NewPostListener;
use TaCraft\FlarumOneBotNotify\OneBotServiceProvider;

return [
    new Extend\ServiceProvider(OneBotServiceProvider::class),

    (new Extend\Event())
        ->listen(Started::class, NewDiscussionListener::class)
        ->listen(Posted::class, NewPostListener::class),

    (new Extend\Routes('api'))
        ->post('/onebot-notify/test', 'tacraft-onebot-notify.test', TestOneBotController::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/less/admin.less'),

    (new Extend\Settings())
        ->default('tacraft-onebot-notify.new_discussion_template', MessageTemplates::NEW_DISCUSSION)
        ->default('tacraft-onebot-notify.new_post_template', MessageTemplates::NEW_POST)
        ->default('tacraft-onebot-notify.enabled_tags', '[]')
        ->default('tacraft-onebot-notify.retry_attempts', '2')
        ->default('tacraft-onebot-notify.retry_delay', '1')
        ->default('tacraft-onebot-notify.connect_timeout', '5')
        ->default('tacraft-onebot-notify.timeout', '10')
        ->default('tacraft-onebot-notify.enabled', '')
        ->default('tacraft-onebot-notify.api_url', '')
        ->default('tacraft-onebot-notify.access_token', '')
        ->default('tacraft-onebot-notify.target_type', 'group')
        ->default('tacraft-onebot-notify.target_id', '')
        ->default('tacraft-onebot-notify.use_proxy', '')
        ->default('tacraft-onebot-notify.proxy', '')
        ->serializeToForum('tacraft-onebot-notify.default_new_discussion_template', 'tacraft-onebot-notify.new_discussion_template')
        ->serializeToForum('tacraft-onebot-notify.default_new_post_template', 'tacraft-onebot-notify.new_post_template'),

    new Extend\Locales(__DIR__ . '/locale'),
];