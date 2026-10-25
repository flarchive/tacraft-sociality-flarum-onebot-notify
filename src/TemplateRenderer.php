<?php

namespace TaCraft\FlarumOneBotNotify;

final class TemplateRenderer
{
    public static function render(string $template, array $placeholders): string
    {
        return strtr($template, $placeholders);
    }

    public static function excerpt(?string $text, int $length = 200): string
    {
        $text = strip_tags((string) $text);
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length);
        $space = mb_strrpos($cut, ' ');

        if ($space !== false && $space > (int) ($length * 0.6)) {
            $cut = mb_substr($cut, 0, $space);
        }

        return $cut . '…';
    }

    public static function discussionTags($discussion): string
    {
        if (!class_exists('Flarum\Tags\Tag')) {
            return '';
        }

        $tags = $discussion->tags;

        if ($tags !== null && $tags->isNotEmpty()) {
            return $tags
                ->pluck('name')
                ->map(fn ($name) => '#' . $name)
                ->implode(' ');
        }

        return '';
    }
}