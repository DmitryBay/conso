# Project instructions

## Language

- При ответе пользователю использовать русский язык.

## Repository architecture

- Это модульный Laravel-монолит: Laravel 13, PHP `^8.3`. Фактические версии определяются `composer.lock`.
- Веб-приложение разделено на три контура: `app/Http/Controllers/Admin` (`/platform`, super admin), `Workspace` (`/workspace`, owner/manager) и `Guest` (`/guest/{company:slug}`, сессия проживания). Общая доменная модель находится в `app/Models`.
- Маршруты — в `routes/web.php`; middleware регистрируются в `bootstrap/app.php`; планировщик — в `routes/console.php`.
- Переиспользуемые многошаговые сценарии находятся в `app/Actions`; небольшие форматирующие и справочные классы — в `app/Support`. Не создавать параллельные `Services`, repositories, DTO, handlers или API Resources без подтверждённой потребности: таких слоёв сейчас нет.
- Модели используют Eloquent relationships с типами возврата, PHP-атрибуты `#[Fillable]`, метод `casts()` и backed enums из `app/Enums`. Сохранять этот стиль.
- Текущего `/api/v1` и мобильного API нет. `docs/ARCHITECTURE.md` содержит также планы развития; считать код, зависимости и маршруты источником истины для уже реализованного поведения.

## Working with existing code

- Решать запрошенную задачу, не перепроектируя несвязанный код и не делая оппортунистический рефакторинг.
- Сначала искать существующие action, support-класс, enum, query scope, middleware, partial или JS-механизм и расширять его вместо создания дубликата.
- Перед изменением сигнатуры метода, route name, поля БД, enum/string-значения, notification payload или публичного идентификатора найти все использования и сохранить обратную совместимость, если задача явно не требует иного.
- Контроллеры должны валидировать вход, проверять tenant/permission, загружать данные и координировать сценарий. Переиспользуемую или сложную многошаговую бизнес-операцию выносить в `app/Actions`, как `CreateGuestOrder` и `CloseExpiredStays`; не создавать абстракцию ради единственного простого вызова.
- Не проглатывать исключения без обоснования, не добавлять спекулятивные fallback-механизмы и не менять публичные контракты или production-конфигурацию попутно.

## Tenancy, authorization, and validation

- `company_id` — обязательная граница данных Workspace. Не доверять tenant ID из запроса: брать компанию от аутентифицированного пользователя/current middleware context и явно ограничивать все tenant-запросы.
- Guest-доступ ограничен одновременно `company_id` и текущим `guest_stay_id`; идентификатор сессии хранится отдельно для каждой компании. Чужие tenant-ресурсы обычно скрываются через `404`, а не раскрываются через `403`.
- Авторизация сейчас реализована route middleware (`role`, `company`, `guest.hotel`), проверками в контроллерах и приватным broadcast-каналом `company.{id}`. Policies в проекте нет: не считать одного route model binding достаточным для tenant-доступа.
- Owner-only маршруты команды, номеров, фонов и журнала действий должны оставаться внутри `role:company_owner`. Успешные manager-записи по заявкам/проживаниям/услугам логируются middleware `manager.audit`; новые изменяющие маршруты этих областей должны сохранять аудит.
- Использовать фактический стиль в соседнем коде: inline `$request->validate()` для локальных правил, Form Request для более крупных admin company-форм, `Rule::enum()`/`Rule::in()` для закрытых наборов. Валидировать принадлежность связанных room/service/user/stay текущей компании отдельным tenant-scoped query.

## Domain and database rules

- Денежные суммы хранятся целыми в `*_minor`: для IDR число уже является отображаемой суммой, для остальных поддерживаемых валют используется 100 minor units. Форматирование и USD-оценка идут через `App\Support\Money` и `config/concierge.php`.
- Заказ сохраняет исторический snapshot услуги в `service_request_items` (`name_snapshot`, цена, quantity, options). Изменение каталога не должно менять старые заказы или счета; корректировка цены создаёт `service_request_price_adjustments` и audit/history записи.
- Переходы заявки используют `RequestStatus`, синхронно обновляют связанные payment/timestamp-поля и добавляют `service_request_status_histories`. Не менять семантику `room_charge`, `cash`, `pending`, `paid`, `invoiced`, `cancelled` или refund-полей молча.
- Multi-record изменения выполнять в `DB::transaction()`. Для конкурентных подтверждений, отмен, статусов, возвратов и изменения цены повторно читать целевую заявку через `lockForUpdate()` внутри транзакции и повторять tenant/state checks. Внешние side effects запускать только после успешного завершения транзакции.
- Новые миграции должны быть forward-safe для существующей MySQL 8 production БД и одновременно проходить тесты на SQLite in-memory. Предпочитать additive/nullable/backfill-последовательность; data migration делать детерминированной и пакетной при возможном большом объёме.
- Удаление/переименование колонок, изменение типа или значения статуса, каскадное удаление и массовая перезапись production-данных требуют явного обоснования и безопасного плана совместимости/отката. Никогда не использовать `migrate:fresh`, `db:wipe` или destructive seeders на production.
- Не редактировать уже применённую миграцию для новой схемы: добавлять новую. Сохранять FK, составные tenant-индексы и уникальность, соответствующие соседним таблицам.
- Seeders содержат demo-данные и местами привязаны к `nusa-bay-hotel`; не запускать `db:seed` на production без явной задачи и проверки влияния. При изменении повторно запускаемого catalog/background seeder сохранять существующие ID и ссылки заказов.

## Queues, events, schedule, and integrations

- Отдельных Job/Listener-классов сейчас нет. Notifications используют `Queueable`, но не `ShouldQueue`; `ServiceRequestChanged` реализует `ShouldBroadcastNow`. Не предполагать асинхронность существующих mail/push/broadcast side effects.
- Queue infrastructure использует настраиваемое соединение (по умолчанию database), а `failed_jobs` хранится в БД. Если добавляется queued job, задать осмысленные retry/timeout/idempotency правила, не сериализовать хрупкое временное состояние и обеспечить совместимость старых и новых workers во время деплоя.
- `CloseExpiredStays` запускается каждую минуту с `withoutOverlapping`; сохранять идемпотентность и chunked processing. Изменения расписания требуют проверки `php artisan schedule:list` и учёта уже работающего scheduler.
- Внешние каналы: SMTP/mail, Web Push/VAPID, Pusher/Echo broadcasting и PDF через Dompdf. Не выполнять сетевой side effect внутри тестов: использовать `Mail::fake()`, `Notification::fake()` и `Event::fake()` по назначению.
- Cache/queue/session/broadcast drivers выбираются окружением. Redis поддержан конфигурацией, но прямой Redis API в приложении не используется; не вводить зависимость от Redis-специфичного поведения без отдельного требования.
- Секреты и реальные ключи никогда не коммитить. Новые параметры документировать пустыми/безопасными значениями только в `.env.example` и читать через config, не через `env()` вне `config/*`.

## Frontend and localization

- UI — server-rendered Blade с отдельными layouts для Platform, Workspace и Guest; Bootstrap 5/Bootstrap Icons, один `resources/css/app.css`, vanilla JS в `resources/js/app.js`, Vite. Vue/React/Livewire нет; Tailwind-плагин установлен, но текущий UI построен на Bootstrap/custom CSS — не вводить другой UI-стек без задачи.
- Real-time Workspace использует Laravel Echo/Pusher в `resources/js/echo.js`; при недоступности соединения интерфейс также опрашивает status endpoints. PWA/service-worker файлы находятся в `public/guest-sw.js`, `public/workspace-sw.js` и layouts.
- Guest и Workspace локализованы в `lang/{ru,uk,id,en,ar,he,zh,ko}`; `ar` и `he` используют RTL. Изменение пользовательского текста или ключа должно синхронно учитывать все восемь locale-файлов и RTL. Platform исторически содержит русский текст напрямую — следовать соседнему коду, если задача не про его локализацию.
- `public/build` генерируется `npm run build` и игнорируется Git; не добавлять его в коммит.

## Testing and verification

- Тесты — PHPUnit 12: Feature/Unit в `tests`, Feature обычно используют `RefreshDatabase`; тестовое окружение — SQLite `:memory:`, sync queue, array cache/session/mail и null broadcasting. Базовый `TestCase` отключает Vite.
- Проверять поведение и наблюдаемые контракты, а не внутреннюю реализацию. Для tenant/permission изменений обязательны positive и cross-tenant/forbidden cases; для денег/статусов — snapshots, history, payment и повторный/конкурентный вызов; для интеграций — fake и ожидаемый payload/channel.
- Не отключать, не удалять и не ослаблять тесты ради зелёного прогона. Компиляция сама по себе не означает успешную реализацию.
- Точечный тест: `php artisan test tests/Feature/GuestOrderingTest.php` либо `php artisan test --filter=test_name`.
- Полный backend-прогон: `composer test` (очищает config cache и запускает `php artisan test`).
- Проверка форматирования PHP: `./vendor/bin/pint --test`; исправление: `./vendor/bin/pint` только для затронутых project files, не форматировать несвязанный код.
- Frontend build/check: `npm run build`. Отдельных JS lint/static-analysis команд и CI workflow в репозитории нет — не заявлять, что они выполнены.

## Feature workflow

1. Понять требуемое поведение и критерии готовности.
2. Изучить текущую реализацию, тесты, маршруты и связанные данные.
3. Найти существующие patterns/abstractions, которые нужно переиспользовать.
4. Определить минимальное цельное изменение.
5. Реализовать его.
6. Добавить или обновить поведенческие тесты.
7. Запустить targeted tests.
8. Запустить более широкий релевантный прогон, formatting и frontend build, когда затронуты соответствующие области.
9. Просмотреть diff и исключить несвязанные изменения/артефакты.
10. Развернуть по действующей deployment policy ниже.
11. Проверить production после деплоя.

Для существенной функции до реализации проверить затронутые models/relations, миграции и production data, web/API contracts, side effects/integrations, queue compatibility, transaction/locking boundaries, permissions/tenant isolation, failure/idempotency cases и тестовую матрицу. Для тривиальной правки отдельный planning-документ не нужен.

## Git discipline

- Рабочее дерево может содержать пользовательские изменения: не откатывать, не переформатировать и не коммитить их без отношения к задаче.
- Коммитить только релевантные исходники, миграции, тесты и документацию. Не коммитить `.env`, секреты, `vendor/`, `node_modules/`, `public/build`, `.DS_Store`, логи, SQLite-файлы или локальные артефакты.
- Не менять зависимости/lock-файлы без необходимости задачи. Не переписывать публичные интерфейсы и историю Git без прямого запроса.

## Deployment

- Deployment is already fully configured for this project.
- After implementing and successfully verifying requested changes, deploy them to production immediately by default; do not wait for a separate «выкладывай» command.
- When the user says «выкладывай», «на сервер», or otherwise asks to deploy, do not search for deployment configuration and do not ask how to deploy.
- Treat deployment as the established workflow: verify the requested changes, commit only the relevant project files, and push `main` to `origin`.
- Production host: `lalo.craabchee.com`. Application path: `/var/www/luma-concierge`.
- After pushing, connect over SSH as `root`, but run all application commands as the `web` user with `sudo -u web -H`.
- Every deployment that includes or may include database changes must run `php artisan migrate --force` as `web`. Then refresh Laravel caches and restart the queue as `web`.
- For changes without database impact, still refresh Laravel caches and restart the queue as `web`; do not run production seeders.
- Verify the production commit, `php artisan migrate:status`, queue process/status, and an HTTP response from `https://lalo.craabchee.com` before reporting deployment complete.
- Never include local artifact directories such as `output/` or `tmp/` in a deployment commit unless the user explicitly requests them.

## Production safety

- Before deployment review migrations, queued payload compatibility, env/config requirements, external side effects and rollback implications. Never expose secrets in output or Git.
- Do not make manual production data/config changes beyond the requested deployment. Use read-only diagnostics first; run application commands as `web`, not `root`.
- Do not report success until the pushed commit is running, migrations are in the expected state, the queue is running and the public HTTP check succeeds.
