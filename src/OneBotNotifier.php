<?php

namespace TaCraft\FlarumOneBotNotify;

use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\Queue;
use Psr\Log\LoggerInterface;

class OneBotNotifier
{
    private const DEFAULT_ATTEMPTS = 2;

    private const DEFAULT_RETRY_DELAY = 1;

    private const DEFAULT_CONNECT_TIMEOUT = 5;

    private const DEFAULT_TIMEOUT = 10;

    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly Queue $queue,
        private readonly LoggerInterface $logger,
    ) {}

    private ?ClientInterface $http = null;

    public function setHttpClient(ClientInterface $http): void
    {
        $this->http = $http;
    }

    private function http(): ClientInterface
    {
        return $this->http ??= new Client();
    }

    public function dispatch(string $message): void
    {
        if (!$this->configured()) {
            return;
        }

        $this->queue->push(new SendOneBotMessage($message));
    }

    private function configured(): bool
    {
        return in_array($this->settings->get('tacraft-onebot-notify.enabled'), [true, '1', 1], true)
            && trim((string) $this->settings->get('tacraft-onebot-notify.api_url')) !== ''
            && $this->targets() !== [];
    }

    private function targets(): array
    {
        $raw = (string) $this->settings->get('tacraft-onebot-notify.target_id', '');

        $targets = array_map('trim', explode(',', $raw));
        $targets = array_filter($targets, static fn ($v) => $v !== '');

        return array_values($targets);
    }

    public function send(string $message): array
    {
        if (!in_array($this->settings->get('tacraft-onebot-notify.enabled'), [true, '1', 1], true)) {
            return ['success' => false, 'error' => 'OneBot channel is disabled'];
        }

        $apiUrl = trim((string) $this->settings->get('tacraft-onebot-notify.api_url'));
        $targetType = (string) $this->settings->get('tacraft-onebot-notify.target_type', 'group');
        $token = trim((string) $this->settings->get('tacraft-onebot-notify.access_token'));
        $targets = $this->targets();

        if ($apiUrl === '' || $targets === []) {
            $this->logger->warning('[tacraft/flarum-onebot-notify] Not configured: api_url or target_id missing.');

            return ['success' => false, 'error' => 'OneBot is not configured (api_url / target_id missing)'];
        }

        $endpoint = $targetType === 'private' ? '/send_private_msg' : '/send_group_msg';
        $url = rtrim($apiUrl, '/') . $endpoint;

        $headers = ['Content-Type' => 'application/json'];
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $errors = [];

        foreach ($targets as $target) {
            if (!is_numeric($target)) {
                $errors[] = "[{$target}] invalid numeric ID";

                continue;
            }

            $data = $targetType === 'private'
                ? ['user_id' => (int) $target, 'message' => $message]
                : ['group_id' => (int) $target, 'message' => $message];

            $result = $this->request($url, $data, $headers);

            if ($result['error'] !== null) {
                $this->logger->error("[tacraft/flarum-onebot-notify] Request failed for {$target}: " . $result['error']);
                $errors[] = "[{$target}] " . $result['error'];

                continue;
            }

            $decoded = json_decode((string) $result['response'], true);

            if (!is_array($decoded)) {
                $errors[] = "[{$target}] invalid OneBot response";

                continue;
            }

            $ok = ($decoded['status'] ?? '') === 'ok' || (int) ($decoded['retcode'] ?? -1) === 0;

            if (!$ok) {
                $description = $decoded['message']
                    ?? $decoded['wording']
                    ?? $decoded['msg']
                    ?? 'Unknown OneBot API error';

                $this->logger->warning("[tacraft/flarum-onebot-notify] API error for {$target}: " . $description);
                $errors[] = "[{$target}] " . $description;
            }
        }

        if ($errors !== []) {
            return ['success' => false, 'error' => implode('; ', $errors)];
        }

        return ['success' => true];
    }

    private function request(string $url, array $data, array $headers): array
    {
        $proxy = null;
        if (in_array($this->settings->get('tacraft-onebot-notify.use_proxy'), [true, '1', 1], true)) {
            $raw = trim((string) $this->settings->get('tacraft-onebot-notify.proxy'));
            $proxy = $raw !== '' ? $raw : null;
        }

        $attempts = $this->intSetting('retry_attempts', self::DEFAULT_ATTEMPTS, 1, 5);
        $retryDelay = $this->intSetting('retry_delay', self::DEFAULT_RETRY_DELAY, 0, 30);
        $connectTimeout = $this->intSetting('connect_timeout', self::DEFAULT_CONNECT_TIMEOUT, 1, 30);
        $timeout = $this->intSetting('timeout', self::DEFAULT_TIMEOUT, 1, 60);

        $options = [
            'json' => $data,
            'headers' => $headers,
            'connect_timeout' => $connectTimeout,
            'timeout' => $timeout,
            'http_errors' => false,
        ];
        if ($proxy !== null) {
            $options['proxy'] = $proxy;
        }

        $lastError = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->http()->post($url, $options);

                $status = $response->getStatusCode();

                if ($status >= 400) {
                    $lastError = $this->sanitize("OneBot API responded with HTTP {$status}");
                } else {
                    return ['response' => (string) $response->getBody(), 'error' => null];
                }
            } catch (GuzzleException $e) {
                $lastError = $this->sanitize($e->getMessage());
            }

            if ($attempt < $attempts && $retryDelay > 0) {
                sleep($retryDelay);
            }
        }

        return ['response' => null, 'error' => $lastError];
    }

    private function intSetting(string $key, int $default, int $min, int $max): int
    {
        $raw = $this->settings->get('tacraft-onebot-notify.' . $key);

        if (!is_numeric($raw)) {
            return $default;
        }

        return max($min, min($max, (int) $raw));
    }

    private function sanitize(string $message): string
    {
        $token = trim((string) $this->settings->get('tacraft-onebot-notify.access_token'));

        if ($token !== '') {
            $message = str_replace($token, '***', $message);
        }

        return $message;
    }
}