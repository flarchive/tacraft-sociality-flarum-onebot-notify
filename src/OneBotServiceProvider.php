<?php

namespace TaCraft\FlarumOneBotNotify;

use Flarum\Foundation\AbstractServiceProvider;

class OneBotServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(OneBotNotifier::class);
        $this->container->singleton(TagFilter::class);
    }
}