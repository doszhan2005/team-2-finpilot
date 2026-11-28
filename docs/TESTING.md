# FinPilot — Тестілеу (QA & DevOps)

## 1. Тестілеу стратегиясы

| Түрі | Құрал | Қорытынды талап | Тексеріс |
|---|---|---|---|
| Unit | PHPUnit | ≥ 40 | Қаржылық есептеу, шекаралық мәндер, валидация |
| Integration | Laravel Testing + MySQL | ≥ 10 | REST API, рөлдер, токен, CRUD, деректерді оқшаулау |
| E2E / UI | Android instrumentation / Espresso | ≥ 2 | Нақты мобильді қосымшаның негізгі пайдаланушы жолдары |
| Coverage | PCOV + PHPUnit | ≥ 60% | Service/domain қабатының орындалған жолдары |

Тесттер оқшауланған және анық assertions қолданады. Integration тесттеріне жеке тест дерекқоры қолданылады; production дерекқорында тесттерді іске қосуға болмайды.

## 2. Автоматтандырылған тесттер

| Файл | Тест сценарийлері | Мақсаты |
|---|---:|---|
| [FinanceCalculatorTest.php](../backend/tests/Unit/FinanceCalculatorTest.php) | 10 | Кіріс, шығыс, баланс, бюджет, статистика |
| [FinanceCalculatorEdgeCasesTest.php](../backend/tests/Unit/FinanceCalculatorEdgeCasesTest.php) | 30 | Бос тізім, шекаралық сома, артық шығыс, қате түр, күнтізбелік шекаралар |
| [AuthenticationApiTest.php](../backend/tests/Feature/AuthenticationApiTest.php) | 10 | Тіркелу, пароль хэші, токен, рөлдер, рұқсат |
| [TransactionApiTest.php](../backend/tests/Feature/TransactionApiTest.php) | 13 | CRUD, санаттар, сома валидациясы, басқа пайдаланушының жазбасын қорғау |
| [ExampleTest.php](../backend/tests/Feature/ExampleTest.php) | 1 | Web түбірлік маршруттың қолжетімділігі |

## 3. Расталған нәтиже

[CI run №46165515147](https://github.com/doszhan2005/team-2-finpilot/actions/runs/46165515147), commit `e96ff45`:

| Көрсеткіш | Нәтиже |
|---|---|
| Unit | 40 passed, 55 assertions |
| Feature | 24 passed, 86 assertions (23 API integration + 1 web smoke) |
| Service line coverage | 100% (59/59), минимум 60% |
| Laravel Pint | PASS |
| MySQL integration | PASS |
| Coverage report | GitHub Actions artifact: `backend-service-coverage` |

Coverage тек қазіргі `backend/app/Services` (FinanceCalculator) кодына қатысты. Ол бүкіл Laravel жүйесінің немесе Android қосымшасының coverage-і емес. Жаңа service/domain модульдері қосылғанда coverage scope және тесттер толықтырылады.

## 4. Тесттерді іске қосу

Жобаның backend қалтасында:

```bash
composer install
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
vendor/bin/pint --test
```

Жергілікті Feature тесттері әдепкіде SQLite in-memory қолданады; CI арнайы MySQL 8.4 тест базасын іске қосады.

PCOV немесе Xdebug қолжетімді болғанда:

```bash
vendor/bin/phpunit --testsuite=Unit --coverage-text --coverage-clover build/coverage.xml
```

CI: [workflow](../.github/workflows/ci.yml) — build → linter → unit → MySQL integration → coverage gate. Coverage 60%-дан төмен болса, CI сәтсіз аяқталады.

## 5. Docker және серверлік орта

Жергілікті іске қосу (репозиторий түбірінде, Docker Desktop/Engine және Compose қажет):

```bash
docker compose up --build -d --wait
docker compose ps
```

- Health: http://localhost:8000/up
- Swagger: http://localhost:8000/swagger.html
- API: http://localhost:8000/api
- MySQL сыртқы портқа шығарылмайды; дерекқор volume ішінде сақталады.
- Қолданба Apache/PHP арқылы іске қосылады. Жергілікті demo параметрлерін production-да қолдануға болмайды.
- `docker compose down` контейнерлерді тоқтатады; `down -v` дерекқорды жоятындықтан тек бір реттік CI ортасында қолданылады.

Production конфигурациясы: [compose.production.yaml](../compose.production.yaml). Қашықтағы серверге орналастыру үшін Docker орнатылған VPS, сол IP-ге бағытталған домен және ашық 80/443 порттары керек.

```bash
cp .env.production.example .env.production
chmod 600 .env.production
```

`.env.production` ішінде нақты доменді, кездейсоқ APP_KEY және екі бөлек күшті MySQL паролін орнату керек. APP_KEY үшін `openssl rand -base64 32` нәтижесінің алдына `base64:` қосылады. Парольдер үшін `openssl rand -hex 32` екі рет қолдануға болады. Бұл мәндер GitHub-қа жіберілмейді.

```bash
docker compose --env-file .env.production -f compose.production.yaml up --build -d --wait
docker compose --env-file .env.production -f compose.production.yaml ps
```

Caddy доменге HTTPS сертификатын автоматты алуға арналған; Laravel `APP_DEBUG=false`, JSON stderr логтары және ішкі MySQL желісі қолданылады. Деплойдың нақты нәтижесі VPS, DNS және HTTPS тексерілгеннен кейін ғана расталады.

## 6. E2E / UI қабылдау сценарийлері

Android қосымшасы дайын болғаннан кейін автоматтандыру керек:

| Сценарий | Қадамдар | Күтілетін нәтиже |
|---|---|---|
| E2E-01 | Тіркелу → кіру → кіріс қосу → тарихты ашу | Сома API мен MySQL-да сақталып, мобильді экранда көрінеді |
| E2E-02 | Кіру → шығыс қосу → өзгерту → жою және болдырмау | Экран мен сервер деректері сәйкес, операцияның нәтижесі сақталады |

Бұл екі сценарий әзірге қабылдау жоспары; автоматтандырылған UI тест ретінде есептелмейді. APK және нақты құрылғыдағы интеграциялық демо Android коды мен қашықтағы сервер дайын болғанда тексеріледі.
