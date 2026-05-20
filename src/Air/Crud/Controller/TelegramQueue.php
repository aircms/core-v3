<?php

declare(strict_types=1);

namespace Air\Crud\Controller;

use Air\Crud\Controller\MultipleHelper\Accessor\Control;
use Air\Crud\Controller\MultipleHelper\Accessor\Header;
use Air\Crud\Controller\MultipleHelper\Accessor\HeaderButton;
use Air\Crud\Nav\Nav;
use Air\Crud\Nav\NavController;
use Air\Telegram;
use Air\Type\FaIcon;

/**
 * @mod-sorting {"when": -1}
 *
 * @mod-filter {"type": "search", "by": ["botToken", "message", "status"]}
 */
class TelegramQueue extends Multiple
{
  use NavController;

  protected function getNav(): string
  {
    return Nav::SETTINGS_TELEGRAM_QUEUE;
  }

  public function manage(?string $id = null): void
  {
    $telegram = \Air\Crud\Model\TelegramQueue::fetchOne(['id' => $id]);

    $this->getView()->assign('telegram', $telegram);
    $this->getView()->assign('entity', $this->getEntity());

    $this->getView()->setScript('telegramQueue/manage');
  }

  public function body(string $id): string
  {
    $this->getView()->setAutoRender(false);
    $this->getView()->setLayoutEnabled(false);

    return \Air\Crud\Model\TelegramQueue::fetchOne(['id' => $id])->message;
  }

  public function getHeader(): array
  {
    return [
      Header::source('Status', function (\Air\Crud\Model\TelegramQueue $telegramQueue) {
        return vertical([
          badge(date('Y-m-d H:i', $telegramQueue->when), DARK),
          match ($telegramQueue->status) {
            \Air\Crud\Model\TelegramQueue::STATUS_NEW => badge('Planned', WARNING),
            \Air\Crud\Model\TelegramQueue::STATUS_SUCCESS => badge('Success', SUCCESS),
            \Air\Crud\Model\TelegramQueue::STATUS_FAIL => badge('Fail', DANGER),
          }
        ]);
      }),
      Header::source('Destination', function (\Air\Crud\Model\TelegramQueue $telegramQueue) {
        return vertical([
          badge($telegramQueue->botToken, INFO),
          badge(implode(', ', $telegramQueue->chatIds), SECONDARY),
        ]);
      }),
      Header::longtext(by: 'message')
    ];
  }

  public function send(string $id): array
  {
    return ['success' => Telegram::send(
      \Air\Crud\Model\TelegramQueue::one(['id' => $id])
    )];
  }

  public function clear(): void
  {
    \Air\Crud\Model\TelegramQueue::batchRemove([
      'status' => ['$ne' => \Air\Crud\Model\TelegramQueue::STATUS_NEW]
    ]);

    $this->redirect('/' . $this->getEntity());
  }

  public function clearAllForce(): void
  {
    \Air\Crud\Model\TelegramQueue::batchRemove();
    $this->redirect('/' . $this->getEntity());
  }

  protected function getHeaderButtons(): array
  {
    return [
      HeaderButton::item(
        title: 'Delete all successful ones?',
        url: ['controller' => $this->getEntity(), 'action' => 'clear'],
        confirm: 'Are you sure want to remove all successful?',
        style: DANGER,
        icon: FaIcon::ICON_XMARK
      ),
      HeaderButton::item(
        title: 'Clear all?',
        url: ['controller' => $this->getEntity(), 'action' => 'clearAllForce'],
        confirm: 'Are you sure want to remove all successful?',
        style: DANGER,
        icon: FaIcon::ICON_XMARK
      ),
    ];
  }

  protected function getControls(): array
  {
    return [
      Control::item(
        title: 'Details',
        url: ['controller' => $this->getEntity(), 'action' => 'manage'],
        icon: FaIcon::ICON_PAGE
      )
    ];
  }
}
