<?php

declare(strict_types=1);

namespace Air\Crud\Model;

use Air\Model\ModelAbstract;

/**
 * @collection TelegramQueue
 *
 * @property string $id
 * @property integer $when
 * @property string $botToken
 * @property array $chatIds
 * @property string $message
 *
 * @property string $debugOutput
 * @property string $status
 * @property boolean $inProgress
 */
class TelegramQueue extends ModelAbstract
{
  const string STATUS_NEW = 'new';
  const string STATUS_SUCCESS = 'success';
  const string STATUS_FAIL = 'fail';
}