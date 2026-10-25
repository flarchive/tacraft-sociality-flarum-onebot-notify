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
        $text = (string) $text;

        $text = preg_replace('/\[upl-image-preview[^\]]*\]/i', '[图片]', $text);
        $text = preg_replace('/\[upl-file[^\]]*\]/i', '[附件]', $text);

        $text = preg_replace('/\[img[^\]]*\].*?\[\/img\]/is', '[图片]', $text);
        $text = preg_replace('/\[url=[^\]]*\](.*?)\[\/url\]/is', '$1', $text);

        $text = preg_replace('/\[[a-zA-Z][^\]]*\]/', '', $text);

        $text = strip_tags($text);
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