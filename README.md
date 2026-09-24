# Winx API

API REST para gerenciamento de produtos, construída com Laravel 13.

A API já cobre a fundação (rotas `/api/v1`, JSON e erros padronizados) e a autenticação por token Bearer.

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

É preciso PHP 8.3+ com a extensão `pdo_pgsql`, Composer e PostgreSQL nos valores do `.env.example`. Nesse modo o cache fica em arquivo, o que basta para o limite de tentativas de login. Com Docker, o cache usa Redis.

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

## Autenticação

O acesso autenticado usa Laravel Sanctum. Envie o token no header `Authorization: Bearer {token}`.

| Método | Rota | Acesso |
| --- | --- | --- |
| `POST` | `/api/v1/auth/register` | Público |
| `POST` | `/api/v1/auth/login` | Público |
| `GET` | `/api/v1/auth/me` | Token |
| `POST` | `/api/v1/auth/logout` | Token |

Registro e login devolvem `data.token`, `data.token_type` (`Bearer`) e `data.user`. O logout responde `204` e revoga só o token da requisição. Login e registro aceitam no máximo 5 tentativas por minuto para o mesmo e-mail e IP.

O seeder cria um usuário de demonstração: `test@example.com` / `password`.

```bash
php artisan migrate --seed
```

As próximas rotas de produto entram no mesmo grupo `auth:sanctum`.

## Contrato de erro

Rotas em `/api/*` respondem JSON mesmo sem o header `Accept`. O corpo de erro tem `message`. Validação (`422`) também traz `errors`. Com `APP_DEBUG=true`, uma falha inesperada inclui `debug` com a exceção e a mensagem interna.

## Organização

- `app/Domain/Auth` registra e autentica usuários; `Catalog` e `Activity` entram nas próximas etapas
- `app/Http` concentra controllers, Form Requests e API Resources
- `app/Support/Api` centraliza o contrato de erro
- `routes/api/v1.php` é a superfície da versão 1
