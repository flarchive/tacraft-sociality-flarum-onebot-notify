<?php

namespace TaCraft\FlarumOneBotNotify;

use Flarum\Discussion\Discussion;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

class TagFilter
{
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly LoggerInterface $logger,
    ) {}

    public function shouldNotify(Discussion $discussion): bool
    {
        $enabledTagIds = json_decode(
            (string) $this->settings->get('tacraft-onebot-notify.enabled_tags', '[]'),
            true
        );

        if (!is_array($enabledTagIds) || $enabledTagIds === []) {
            return true;
        }

        if (!class_exists('Flarum\Tags\Tag')) {
            return true;
        }

        $tagIds = Arr::pluck($discussion->tags, 'id');

        $matched = array_intersect($enabledTagIds, $tagIds) !== [];

        if (!$matched) {
            // 排查「测试正常、发帖没通知」时先看这条日志。
            $this->logger->debug(
                '[tacraft/flarum-onebot-notify] Skipped discussion ' . $discussion->id
                . ': tags [' . implode(',', $tagIds) . '] not in enabled_tags [' . implode(',', $enabledTagIds) . '].'
            );
        }

        return $matched;
    }
}