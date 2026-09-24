# Winx API

API REST para gerenciamento de produtos, construída com Laravel 13.

Esta etapa entrega a fundação do projeto: ambiente local, rotas versionadas em `/api/v1`, respostas JSON e tratamento de erros padronizado.

## Stack

- PHP 8.3+
- Laravel 13
- PostgreSQL 16
- Redis 7
- Elasticsearch 8.17

## Subir com Docker

Requisito: Docker e Docker Compose. No Linux, o Elasticsearch pede `vm.max_map_count` de pelo menos `262144`.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

A API fica em `http://localhost:8080`.

```bash
curl http://localhost:8080/api/v1/health
```

Resposta esperada:

```json
{
  "data": {
    "status": "ok",
    "service": "Winx"
  }
}
```

Dentro do Compose, a aplicação fala com `postgres`, `redis` e `elasticsearch`. O `.env` usa `127.0.0.1` para quem roda o PHP na máquina e acessa as portas publicadas.

## Subir sem Docker

É preciso PHP 8.3+ com as extensões `pdo_pgsql` e `redis`, Composer, PostgreSQL e Redis nos valores do `.env.example`.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Nesse modo a API fica em `http://localhost:8000`. Ajuste `APP_URL` se for usar URLs geradas pela aplicação.

## Testes

```bash
php artisan test
```

O PHPUnit usa SQLite em memória e não depende de PostgreSQL, Redis ou Elasticsearch.

## Contrato de erro

Rotas em `/api/*` respondem JSON mesmo sem o header `Accept`. O corpo de erro tem `message`. Validação (`422`) também traz `errors`. Com `APP_DEBUG=true`, uma falha inesperada inclui `debug` com a exceção e a mensagem interna.

## Organização

- `app/Domain/Auth`, `app/Domain/Catalog` e `app/Domain/Activity` reservam os módulos de autenticação, produtos e logs
- `app/Http` concentra controllers, Form Requests e API Resources
- `app/Support/Api` centraliza o contrato de erro
- `routes/api/v1.php` é a superfície da versão 1
