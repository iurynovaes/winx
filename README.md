# Winx API

API REST para gerenciamento de produtos, em Laravel 13. A versão fica em `/api/v1`. As respostas de sucesso vêm em `data`. Os erros de `/api/*` são JSON, com `message`.

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
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan catalog:index-products
```

A API fica em `http://localhost:8080`. O Compose aponta o banco, o Redis, a fila e o Elasticsearch para os serviços internos. O `.env` usa `127.0.0.1` para quem roda o PHP na máquina.

```bash
curl http://localhost:8080/api/v1/health
```

```json
{
  "data": {
    "status": "ok",
    "service": "Winx"
  }
}
```

Em outro terminal, o worker processa o log de produto e a sincronização do índice:

```bash
docker compose exec app php artisan queue:work
```

Sem esse processo, a fila Redis guarda os jobs até alguém consumi-los.

## Subir sem Docker

É preciso PHP 8.3+ com `pdo_pgsql`, Composer e PostgreSQL, nos valores do `.env.example`. O cache fica em arquivo, o bastante para o limite de login. A fila fica `sync`: o log grava na mesma requisição, sem Redis. A busca inteligente fica desligada.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

A API fica em `http://localhost:8000`. Ajuste `APP_URL` se for usar URLs geradas pela aplicação.

## Testes

```bash
php artisan test
```

O PHPUnit usa SQLite em memória, fila síncrona e Elasticsearch desligado.

## Autenticação

O acesso autenticado usa Laravel Sanctum. Envie `Authorization: Bearer {token}`.

| Método | Rota | Acesso | Resultado |
| --- | --- | --- | --- |
| `GET` | `/api/v1/health` | Público | `data.status` `ok` |
| `POST` | `/api/v1/auth/register` | Público | `201`, token |
| `POST` | `/api/v1/auth/login` | Público | token |
| `GET` | `/api/v1/auth/me` | Token | usuário |
| `POST` | `/api/v1/auth/logout` | Token | `204` |

Registro e login devolvem `data.token`, `data.token_type` (`Bearer`) e `data.user`. O logout revoga só o token da requisição. Login e registro aceitam no máximo 5 tentativas por minuto para o mesmo e-mail e IP. E-mail desconhecido e senha errada respondem a mesma mensagem, `401`.

O seeder cria `test@example.com` / `password`, as categorias Camisetas, Calçados e Acessórios, a Camiseta Básica (`CAM-001`) e mais nove produtos.

## Produtos e categorias

Essas rotas exigem o token.

| Método | Rota | Resultado |
| --- | --- | --- |
| `GET` | `/api/v1/products` | lista paginada |
| `POST` | `/api/v1/products` | `201` |
| `GET` | `/api/v1/products/{id}` | detalhe |
| `PUT` ou `PATCH` | `/api/v1/products/{id}` | atualização |
| `DELETE` | `/api/v1/products/{id}` | `204` |
| `GET` | `/api/v1/categories` | lista em ordem de nome |
| `POST` | `/api/v1/categories` | `201` |
| `GET` | `/api/v1/categories/{id}` | detalhe |
| `PUT` ou `PATCH` | `/api/v1/categories/{id}` | renomeia |

Campos do produto: `sku`, `nome`, `descricao`, `preco`, `categoria_id`, `estoque` e `status` (`ativo` ou `inativo`). `descricao` pode ser omitida. Sem `status`, o produto nasce `ativo`. O SKU é único e gravado em maiúsculas. `preco` volta como texto com duas casas, por exemplo `"49.90"`. A resposta traz `categoria` com `id` e `nome`. `PATCH` altera só os campos enviados. Um produto inativo continua no detalhe.

A listagem fica no PostgreSQL, em ordem de nome, com desempate pelo `id`. A página padrão tem 15 itens (`per_page`, de 1 a 50). O número da página é `page`. Filtros opcionais, combinados: `nome` (trecho, sem diferenciar maiúsculas), `categoria_id`, `preco_min`, `preco_max`, `disponivel` (`1` com estoque, `0` sem estoque) e `status`. Sem `status`, ativos e inativos aparecem. A resposta inclui `meta.total`, `meta.per_page`, `meta.current_page` e `meta.last_page`.

O nome da categoria é único sem diferenciar maiúsculas.

## Busca

Com `ELASTICSEARCH_ENABLED=true`:

| Método | Rota | Resultado |
| --- | --- | --- |
| `GET` | `/api/v1/products/search?q=` | página por relevância |
| `GET` | `/api/v1/products/suggestions?q=` | até cinco nomes |

`q` procura em nome, descrição e SKU. O nome pesa mais no ranking. A paginação da busca usa `per_page` e `page`. Criar, atualizar ou excluir um produto enfileira a atualização do documento. Com o Elasticsearch desligado, as duas rotas respondem `503`.

`php artisan catalog:index-products` recria o índice a partir do banco.

## Logs

Criar, atualizar ou excluir um produto grava uma linha em `product_activities`. O controller não faz isso: a ação dispara o evento `ProductChanged` depois que a gravação confirma, um listener enfileira o job e o job persiste o log.

O registro guarda quem fez (`user_id`), a ação (`criado`, `atualizado` ou `excluido`), o estado anterior e o estado novo. Uma atualização que não muda nada não gera log. O log da exclusão permanece depois que o produto some. Não há rota para ler esses registros.

## Contrato de erro

| Situação | Status |
| --- | --- |
| Sucesso de leitura | `200` |
| Criação | `201` |
| Logout ou exclusão | `204` |
| Sem token ou token revogado | `401` |
| Recurso inexistente | `404` |
| Validação | `422`, com `errors` |
| Limite de login ou registro | `429` |
| Busca com Elasticsearch desligado | `503` |
| Falha inesperada | `500` |

Com `APP_DEBUG=true`, a falha inesperada inclui `debug` com a exceção e a mensagem interna.

## Diferenciais

- Busca full-text no Elasticsearch, com ranking e sugestões, separada dos filtros exatos da listagem
- Log assíncrono de criação, atualização e exclusão
- Suíte PHPUnit
- Código separado por assunto: autenticação, catálogo e atividade

## Organização

- `app/Domain/Auth` registra e autentica usuários
- `app/Domain/Catalog` cuida de produtos, categorias e do índice de busca
- `app/Domain/Activity` dispara o log das mudanças de produto
- `app/Http` concentra controllers, Form Requests e API Resources
- `app/Support/Api` centraliza o contrato de erro
- `routes/api/v1.php` é a superfície da versão 1
