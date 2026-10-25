<?php

namespace TaCraft\FlarumOneBotNotify;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOneBotMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $message,
    ) {}

    public function handle(OneBotNotifier $notifier): void
    {
        $notifier->send($this->message);
    }
}