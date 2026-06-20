# AirCMS Core V3

**AirCMS Core V3** — ядро PHP-фреймворка AirCMS для разработки API, UI-сайтов, административных панелей, CRUD-разделов, фоновых worker-ов и внутренних сервисов проекта.

Пакет содержит базовую инфраструктуру приложения: routing, controllers, request/response, ORM, forms, admin CRUD, storage, localization, notifications, billing, utilities и интеграции с внешними сервисами.

> Core V3 — это не готовый сайт, а ядро, которое подключается в проект через Composer и используется вместе с проектной структурой `app/`, `config.php` и `www/index.php`.

---

## Требования

Из `composer.json` пакета:

```json
{
  "php": ">=8.3",
  "ext-json": "*",
  "ext-mbstring": "*",
  "ext-mongodb": "*",
  "ext-curl": "*",
  "phpmailer/phpmailer": "*"
}
```

Минимально нужно:

- PHP `8.3+`;
- MongoDB extension;
- cURL extension;
- mbstring extension;
- JSON extension;
- Composer.

---

## Установка

Обычно пакет подключается как зависимость проекта:

```bash
composer require aircms/core-v3
```

Если пакет находится в приватном репозитории, сначала нужно настроить Composer repository/auth, затем выполнить установку.

---

## Минимальная структура проекта

Типичная структура проекта на AirCMS Core V3:

```text
project/
├── app/
│   └── Module/
│       ├── Bootstrap.php
│       ├── Api/
│       ├── Ui/
│       ├── Admin/
│       └── Cli/
├── config.php
├── vendor/
└── www/
    ├── index.php
    └── assets/
```

Главная идея:

- `config.php` описывает окружение, модули, домены и роуты;
- `www/index.php` запускает `Air\Core\Front`;
- `app/Module/*` содержит контроллеры, формы, модели, сервисы и view-файлы проекта;
- `vendor/aircms/core-v3` содержит ядро.

---

## Точка входа

Пример `www/index.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo \Air\Core\Front::getInstance(require_once __DIR__ . '/../config.php')
  ->bootstrap()
  ->run();
```

`Front` выполняет полный жизненный цикл запроса:

1. собирает `Request`;
2. инициализирует router;
3. определяет module/context/controller/action;
4. создаёт controller;
5. запускает plugins;
6. вызывает action;
7. рендерит `Response`.

---

## Базовая конфигурация

Пример `config.php`:

```php
<?php

use Air\Config;
use Air\Core\Route;
use Air\Crud\Nav\Nav;

return Config::defaults(
  project: 'AIR',
  admin: fn() => Config::admin(
    domain: 'admin.*',
    title: 'AirCMS V3',
    nav: [
      Nav::item('Settings', items: [
        Nav::item('Index', controller: 'index'),
      ]),
    ],
  ),
  api: fn() => Config::api(
    domain: 'api.*',
    routes: [
      '/users' => Route::r(controller: 'users'),
    ],
  ),
  ui: fn() => Config::ui(
    domain: '*',
    routes: [
      '/' => Route::r(controller: 'index'),
    ],
  ),
  cli: fn() => Config::cli(
    routes: [
      '/worker/email' => Route::r(controller: 'email'),
    ],
  ),
);
```

`Config::defaults()` собирает общий конфиг приложения, а `Config::admin()`, `Config::api()`, `Config::ui()` и `Config::cli()` описывают отдельные entry points.

---

## Переменные окружения

`Air\Config::getEnv()` читает переменные с префиксом проекта.

Если проект указан как:

```php
<?php

Config::defaults(project: 'AIR');
```

то переменные читаются как:

```text
AIR_DB_HOST
AIR_DB_PORT
AIR_DB_USER
AIR_DB_PASS
AIR_DB_DB
AIR_FS_URL
AIR_FS_KEY
AIR_ADMIN_AUTH_ROOT_LOGIN
AIR_ADMIN_AUTH_ROOT_PASSWORD
AIR_ADMIN_TINY_KEY
```

Пример:

```bash
export AIR_DB_HOST=localhost
export AIR_DB_PORT=27017
export AIR_DB_DB=aircms
export AIR_ADMIN_AUTH_ROOT_LOGIN=admin
export AIR_ADMIN_AUTH_ROOT_PASSWORD=secret
```

---

## Routing

Роуты описываются в `config.php` через `Air\Core\Route`.

```php
<?php

use Air\Core\Route;

'/products' => Route::r(controller: 'products')
```

Роут с context и action:

```php
<?php

'/catalog/import' => Route::r(
  context: 'catalog',
  controller: 'import',
  action: 'run'
)
```

Для генерации ссылок используется `Route::a()`:

```php
<?php

use Air\Core\Route;

$url = Route::a(
  controller: 'products',
  action: 'view',
  params: ['id' => '123']
);
```

---

## Controllers

Контроллеры наследуются от `Air\Core\Controller`.

```php
<?php

namespace App\Module\Api\Controller;

use Air\Core\Controller;

class Users extends Controller
{
  public function index(): array
  {
    return [
      'items' => [],
    ];
  }
}
```

В controller доступны:

```php
<?php

$this->getRequest();
$this->getResponse();
$this->getView();
$this->getParams();
$this->getParam('id');
$this->redirect('/login');
```

Пример чтения данных запроса:

```php
<?php

$name = $this->getRequest()->getPost('name');
$page = $this->getRequest()->getGet('page', 1);
$token = $this->getRequest()->getHeader('token');
```

---

## Request & Response

`Air\Core\Request` хранит данные текущего HTTP/CLI-запроса:

- GET;
- POST;
- params;
- headers;
- method;
- URI;
- domain;
- IP;
- user-agent;
- uploaded files.

```php
<?php

$request = $this->getRequest();

if ($request->isPost()) {
  $data = $request->getPostAll();
}

$id = $request->getParam('id');
$language = $request->getHeader('language');
$file = $request->getFile('image');
```

`Air\Core\Response` отвечает за статус, headers и body:

```php
<?php

$response = $this->getResponse();

$response->setStatusCode(201);
$response->setHeader('Content-Type', 'application/json');
$response->setBody([
  'success' => true,
]);
```

---

## Models & ORM

Модели наследуются от `Air\Model\ModelAbstract` и описываются через PHPDoc.

```php
<?php

namespace App\Model;

use Air\Model\ModelAbstract;

/**
 * @collection products
 *
 * @property string $title
 * @property string $url
 * @property bool $enabled
 * @property int $position
 */
class Product extends ModelAbstract
{
}
```

Примеры выборки:

```php
<?php

$product = Product::one(['url' => 'iphone-15']);
$items = Product::all(['enabled' => true], ['position' => 1]);
$count = Product::count(['enabled' => true]);
```

Пример создания и сохранения:

```php
<?php

$product = new Product();
$product->title = 'iPhone 15';
$product->url = 'iphone-15';
$product->enabled = true;
$product->save();
```

Пример обновления:

```php
<?php

Product::update(
  ['url' => 'iphone-15'],
  ['enabled' => false]
);
```

---

## Forms

Формы используются и для UI/admin rendering, и для API validation.

```php
<?php

namespace App\Form;

use Air\Form\Form;
use Air\Form\Input;
use Air\Validator;

class ProductForm extends Form
{
  public function __construct()
  {
    parent::__construct(elements: [
      Input::text('title', label: 'Title', validators: [
        new Validator\StringLength(min: 2),
      ]),
      Input::checkbox('enabled', label: 'Enabled'),
    ]);
  }
}
```

Использование в API:

```php
<?php

$form = new ProductForm();
$data = $form->validateOfFail($this->getRequest()->getPostAll());
```

Использование в UI/admin:

```php
<?php echo new ProductForm(); ?>
```

---

## Admin & CRUD

Core V3 содержит встроенную CRUD-инфраструктуру для административной панели.

Основные controller-типы:

- `Air\Crud\Controller\Single` — управление одной записью/настройкой;
- `Air\Crud\Controller\Many` — управление коллекцией записей.

Пример `Many` controller:

```php
<?php

namespace App\Module\Admin\Controller;

use Air\Crud\Controller\Many;
use App\Model\Product;
use App\Form\ProductForm;

class Products extends Many
{
  protected string $model = Product::class;
  protected string $form = ProductForm::class;
}
```

Пункты меню админки задаются через `Nav::item()`:

```php
<?php

use Air\Crud\Nav\Nav;

nav: [
  Nav::item('Catalog', items: [
    Nav::item('Products', controller: 'products'),
  ]),
]
```

---

## Built-in Type Objects

Core V3 содержит встроенные typed objects:

- `Air\Type\File`;
- `Air\Type\Meta`;
- `Air\Type\Page`;
- `Air\Type\PageItem`;
- `Air\Type\Quote`;
- `Air\Type\RichContent`;
- `Air\Type\Social`;
- `Air\Type\FaIcon`;
- `Air\Type\TypeAbstract`.

Пример модели с `Meta` и `File`:

```php
<?php

namespace App\Model;

use Air\Model\ModelAbstract;
use Air\Type\File;
use Air\Type\Meta;

/**
 * @collection pages
 *
 * @property string $title
 * @property File $image
 * @property Meta $meta
 */
class Page extends ModelAbstract
{
}
```

Пример использования meta в layout:

```php
<?php echo $page->meta; ?>
```

---

## Storage

`Air\Storage` работает с внешним storage-сервисом. Настройки берутся из config/env:

```text
AIR_FS_URL
AIR_FS_KEY
```

Пример загрузки файла:

```php
<?php

use Air\Storage;

$file = Storage::uploadByUrl(
  path: 'products',
  url: 'https://example.com/image.jpg'
);
```

`Air\Type\File` используется в моделях, формах, Map и админке.

Пример модификации изображения:

```php
<?php

$imageUrl = $product->image->getSrc(
  width: 800,
  height: 600,
  format: 'webp'
);
```

Storage физически создаёт модифицированный файл, если такой вариант изображения ещё не был создан.

---

## Localization

Локализация построена на моделях:

- `Language`;
- `Phrase`.

Записи проекта могут иметь поле `language`, и ORM-методы вроде `one()` / `all()` могут учитывать текущий язык.

Пример API-контроллера:

```php
<?php

$language = $this->getRequest()->getHeader('language');

$items = Product::all([
  'enabled' => true,
  'language' => $language,
]);
```

Фразы используются для переводимых UI/API-сообщений:

```php
<?php

$message = \Air\Crud\Model\Phrase::t('order_created');
```

---

## Notifications

Core V3 поддерживает встроенные уведомления:

- email;
- Telegram;
- SMS;
- SMS.to;
- GatewayAPI.

Отправка через очередь:

```php
<?php

use Air\Email;

Email::add(
  to: 'client@example.com',
  template: 'welcome',
  data: ['name' => 'Edward']
);
```

Отправка без шаблона:

```php
<?php

Email::addPlain(
  to: 'client@example.com',
  subject: 'Welcome',
  body: 'Hello!'
);
```

Worker:

```php
<?php

while (true) {
  \Air\Email::consume();
  sleep(1);
}
```

---

## Billing

Core V3 содержит billing-инфраструктуру и online payment providers.

Основные сценарии:

- использовать общий adapter/factory слой проекта;
- использовать provider напрямую;
- создать invoice/order в проекте;
- получить payment URL;
- принять callback/webhook;
- проверить статус;
- обновить заказ idempotent-логикой.

Поддерживаемые provider-ы зависят от подключённых классов проекта и ядра. В Core V3 есть third-party интеграции и базовые модели для billing-настроек.

---

## Utilities

В ядре есть набор утилит:

- `Air\Map`;
- `Air\Http\Request` / `Air\Http\Response`;
- `Air\Util\Arr`;
- `Air\Util\Str`;
- `Air\Cookie`;
- `Air\Crypt`;
- `Air\Csv`;
- `Air\Dom`;
- `Air\IsBot`;
- `Air\Password`;
- `Air\Sitemap`;
- `Air\SitemapImage`;
- `Air\Slug`;
- `Air\Guard\Guard`.

Пример `Map` для API:

```php
<?php

use Air\Map;

return Map::multiple($products, [
  'id',
  'title',
  'image' => fn($product) => $product->image?->getSrc(width: 400, format: 'webp'),
]);
```

---

## Cache & Logs

Кэш:

```php
<?php

use Air\Cache;

$data = Cache::quick('products-home', function () {
  return Product::all(['enabled' => true]);
});
```

Логи:

```php
<?php

use Air\Log;

Log::info('Payment callback received', [
  'orderId' => $orderId,
]);

Log::error('Payment failed', [
  'error' => $message,
]);
```

---

## Workers / CLI

CLI routes задаются через `Config::cli()`:

```php
<?php

cli: fn() => Config::cli(
  routes: [
    '/email' => Route::r(controller: 'email'),
  ],
)
```

Пример controller-а worker-а:

```php
<?php

namespace App\Module\Cli\Controller;

use Air\Core\Controller;

class Email extends Controller
{
  public function index(): bool
  {
    return \Air\Email::consume();
  }
}
```

---

## Assets

Core V3 поставляет assets для встроенной админки:

```text
assets/css/
assets/js/
assets/vendor/
assets/logo.png
```

Обычно они публикуются/доступны через `/assets/air`, если используется `Config::admin()`.

---

## Debugging

В ядре есть helper `Air\dd()`:

```php
<?php

\Air\dd($product->toArray());
```

Использовать только локально. В production лучше писать в `Air\Log`.

---

## Рекомендованный стиль проекта

Для крупных проектов удобно держать бизнес-логику слоями:

```text
Controller → Form → Service → Model → Map
```

Пример:

```text
App\Module\Api\Controller\Orders
App\Form\OrderForm
App\Service\OrderService
App\Model\Order
App\Map\OrderMap
```

Контроллер должен быть тонким: принять request, провалидировать форму, вызвать service, вернуть mapped response.

---

## Что входит в ядро

Крупные подсистемы:

- Core lifecycle;
- routing;
- request/response;
- controllers;
- plugins;
- workers;
- views/layouts;
- forms;
- validators/filters;
- ORM models;
- MongoDB/MySQL drivers;
- admin CRUD;
- localization;
- storage;
- notifications;
- billing;
- third-party integrations;
- utilities;
- cache/logs;
- system tools.

---

## Документация

Документация по AirCMS V3 ведётся отдельно и должна раскрывать каждую подсистему подробнее: Core, ORM, Forms, Admin & CRUD, Utilities, Notifications, Storage, Localization, Billing, Type Objects и другие разделы.

Сайт проекта: [aircms.pro](https://aircms.pro)
