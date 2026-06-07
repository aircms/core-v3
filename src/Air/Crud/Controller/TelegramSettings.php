<?php

declare(strict_types=1);

namespace Air\Crud\Controller;

use Air\Crud\Nav\Nav;
use Air\Crud\Nav\NavController;
use Air\Form\Form;
use Air\Form\Generator;
use Air\Form\Input;

class TelegramSettings extends Single
{
  use NavController;

  protected function getNav(): string
  {
    return Nav::SETTINGS_TELEGRAM_SETTINGS;
  }

  /**
   * @param \Air\Crud\Model\TelegramSettings $model
   * @return Form
   */
  protected function getForm($model = null): Form
  {
    return Generator::full($model, [
      'General' => [
        Input::checkbox('telegramQueueEnabled'),
        Input::text('telegramBotToken'),
        Input::multipleText('telegramChatIds'),
      ],
    ]);
  }
}