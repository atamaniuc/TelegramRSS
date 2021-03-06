<?php

namespace TelegramRSS;


class Messages {
    private const TELEGRAM_URL = 'https://t.me/';

    private $list = [];
    private $telegramResponse;
    private $channelUrl;
    private $username;
    private $client;

    private const MEDIA_TYPES = [
        'messageMediaDocument',
        'messageMediaPhoto',
        'messageMediaVideo',
    ];

    /**
     * Messages constructor.
     * @param $telegramResponse
     * @param Client $client
     */
    public function __construct($telegramResponse, Client $client) {
        $this->telegramResponse = $telegramResponse;
        $this->client = $client;
        $this->parseMessages();
    }

    private function parseMessages(): self {
        if ($messages = $this->telegramResponse->messages ?? []) {
            foreach ($messages as $message) {
                $description = $message->message ?? '';
                if ($description || $this->hasMedia($message)) {
                    $parsedMessage = [
                        'url' => $this->getMessageUrl($message->id),
                        'title' => null,
                        'description' => $description,
                        'media' => $this->getMediaInfo($message),
                        'preview' => $this->hasMedia($message) ? $this->getMediaUrl($message) . '/preview' : '',
                        'timestamp' => $message->date ?? '',
                    ];

                    $mime = $message->media->document->mime_type ?? '';
                    if (strpos($mime, 'video') !== false) {
                        $parsedMessage['title'] = '[Видео]';
                    }

                    $this->list[$message->id] = $parsedMessage;
                }
            }
        }
        return $this;
    }

    /**
     * @param string $messageId
     * @return string|null
     */
    private function getMessageUrl($messageId = '') {
        if (!$this->channelUrl) {
            $this->username = $this->telegramResponse->chats[0]->username ?? '';
            if (!$this->username) {
                return null;
            }
            $this->channelUrl = static::TELEGRAM_URL . $this->username . '/';
        }
        return $this->channelUrl . $messageId;
    }

    private function getMediaInfo($message) {
        if (!$this->hasMedia($message)) {
            return [];
        }
        $info = $this->client->getMediaInfo($message->media);
        if (!empty($info->size) && !empty($info->mime)) {
            return [
                'url' => $this->getMediaUrl($message),
                'mime' => $info->mime,
                'size' => $info->size,
            ];
        }
    }

    private function hasMedia($message) {
        if (
            empty($message->media) ||
            !in_array($message->media->{'_'}, static::MEDIA_TYPES, true) ||
            (
                isset($message->media->photo) &&
                empty($message->media->photo)
            )
        ) {
            return false;
        }

        return true;
    }

    private function getMediaUrl($message) {
        if (!$this->hasMedia($message)) {
            return false;
        }
        $url = Config::getInstance()->get('url');

        return "{$url}/media/{$this->username}/{$message->id}";
    }

    /**
     * @return array
     */
    public function get(): array {
        return $this->list;
    }

}