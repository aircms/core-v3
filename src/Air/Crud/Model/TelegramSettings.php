<?php

declare(strict_types=1);

namespace Air\Crud\Model;

use Air\Model\ModelAbstract;

/**
 * @collection TelegramSettings
 *
 * @property string $id
 *
 * @property string $telegramBotToken
 * @property array $telegramChatIds
 *
 * @property boolean $telegramQueueEnabled
 */
class TelegramSettings extends ModelAbstract
{

}