<?php

namespace TaCraft\FlarumOneBotNotify\Api;

use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\Translator;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TaCraft\FlarumOneBotNotify\MessageTemplates;
use TaCraft\FlarumOneBotNotify\OneBotNotifier;
use TaCraft\FlarumOneBotNotify\TemplateRenderer;

class TestOneBotController implements RequestHandlerInterface
{
    public function __construct(
        private readonly OneBotNotifier $notifier,
        private readonly SettingsRepositoryInterface $settings,
        private readonly UrlGenerator $url,
        private readonly Translator $translator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $template = (string) $this->settings->get('tacraft-onebot-notify.new_post_template');

        if (trim($template) === '') {
            $template = MessageTemplates::NEW_POST;
        }

        $placeholders = [
            '{title}' => (string) $this->translator->trans('tacraft-onebot-notify.admin.test_sample_title'),
            '{author}' => $actor->display_name ?? $actor->username ?? 'Admin',
            '{excerpt}' => (string) $this->translator->trans('tacraft-onebot-notify.admin.test_sample_excerpt'),
            '{tags}' => '#test',
            '{url}' => $this->url->to('forum')->base(),
        ];

        return new JsonResponse(
            $this->notifier->send(TemplateRenderer::render($template, $placeholders))
        );
    }
}