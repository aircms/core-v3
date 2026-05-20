<?php

declare(strict_types=1);

namespace Air;

use Air\Crud\Model\SmsQueue;
use Air\Crud\Model\TelegramQueue;
use Air\Crud\Model\TelegramSettings;
use Air\Crud\Model\TelegramTemplate;
use Air\Http\Request;
use Throwable;

class Telegram
{
  public static function add(
    TelegramTemplate $template,
    array            $vars = [],
    bool             $force = false,
    int              $when = 0,
    ?string          $botToken = null,
    ?array           $chatIds = null,
  ): void
  {
    self::addPlain(
      message: $template->message,
      vars: $vars,
      when: $when,
      force: $force,
      botToken: $botToken,
      chatIds: $chatIds,
    );
  }

  protected static function render(string $text, array $vars = []): string
  {
    foreach ($vars as $k => $v) {
      $text = str_replace('{' . $k . '}', $v, $text);
    }
    return $text;
  }

  public static function send(TelegramQueue $message): bool
  {
    $debug = [];

    foreach ($message->chatIds as $chatId) {
      try {
        $response = Request::postJson('https://api.telegram.org/bot' . $message->botToken . '/sendMessage', [
          'chat_id' => $chatId,
          'text' => $message->message
        ]);

        if (!$response->isOk()) {
          $debug[] = "Failed sendMessage for: " . $chatId . '. Reason: ' . var_export($response->body, true);
        } else {
          $debug[] = "Successfully sendMessage for: " . $chatId;
        }

      } catch (Throwable $e) {
        $debug[] = "Failed sendMessage for:" . $chatId . '. Reason: ' . $e->getMessage();
      }
    }

    $message->debugOutput = json_encode($debug);
    $message->status = TelegramQueue::STATUS_SUCCESS;
    $message->save();

    return false;
  }

  public static function addPlain(
    string  $message,
    array   $vars = [],
    int     $when = 0,
    bool    $force = false,
    ?string $botToken = null,
    ?array  $chatIds = null,
  ): void
  {
    if (!$botToken || !$chatIds) {
      $settings = TelegramSettings::one();

      $botToken = $settings->telegramBotToken;
      $chatIds = $settings->telegramChatIds;
    }

    $telegramQueue = new TelegramQueue([
      'when' => $when > 0 ? $when : time(),
      'status' => SmsQueue::STATUS_NEW,
      'botToken' => $botToken,
      'chatIds' => $chatIds,
      'message' => self::render($message, $vars),
    ]);

    $telegramQueue->save();

    if ($force) {
      self::send($telegramQueue);
    }
  }

  public static function consume(): bool
  {
    if (!TelegramSettings::one()?->telegramQueueEnabled) {
      return false;
    }

    $queue = TelegramQueue::fetchOneAndUpdate(
      cond: [
        'status' => TelegramQueue::STATUS_NEW,
        'inProgress' => ['$ne' => true],
        'when' => ['$lte' => time()],
      ],
      sort: ['when' => -1],
      data: ['inProgress' => true]
    );

    if ($queue) {
      self::send($queue);
      $queue->inProgress = false;
      $queue->save();
    }

    return true;
  }
}