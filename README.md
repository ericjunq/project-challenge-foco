# Teste Técnico Foco Multimídia: API de Hotelaria

API REST em Laravel para gestão de hotéis, quartos e reservas, com importação de dados a partir de arquivos XML.

## Requisitos

- PHP 8.4
- Composer
- MySQL
- Laravel (versão definida no `composer.json`)

## Configuração do ambiente

1. Clone o repositório e instale as dependências:

```bash
   git clone https://github.com/ericjunq/project-challenge-foco
   cd project-challenge-foco
   composer install
   cp .env.example .env
```

2. Crie o banco `foco_challenge` (collation `utf8mb4_unicode_ci`) no MySQL **antes** de rodar as migrations.
3. No `.env`, configure a conexão com o banco:

```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=foco_challenge
   DB_USERNAME=seu_usuario
   DB_PASSWORD=sua_senha
```

4. Gere a chave da aplicação e crie as tabelas:

```bash
   php artisan key:generate
   php artisan migrate
```

5. Importe os dados dos XMLs:

```bash
   php artisan xml:import
```

6. Suba o servidor:

```bash
   php artisan serve
```

A API fica em `http://127.0.0.1:8000/api`.

As mensagens de validação estão em português (`APP_LOCALE=pt_BR`).

## Banco de dados

Tabelas: `hotels`, `rooms`, `reserves`, `guests`, `dailies` e `payments`.

O diagrama ER e as decisões da modelagem estão em [docs/database.md](docs/database.md).

- `external_id` (em `hotels`, `rooms` e `reserves`) guarda o id do registro no XML de origem. Ele permite que a importação rode várias vezes sem duplicar dados e é nulo para registros criados pela API.
- Todas as chaves estrangeiras usam `ON DELETE CASCADE`. Por isso a API recusa (409) a remoção de um quarto que tenha reservas.
- A chave estrangeira `reserves.hotel_id` é criada pela migration `add_hotel_foreign_key_to_reserves_table`. A migration original de `reserves` tinha um erro de digitação (`contrained`) que fazia o Laravel ignorar a chave sem avisar.

## Importação de arquivos XML

Os arquivos XML de origem estão armazenados em `database/xml/` e simulam os dados de uma API.

Para importar: `php artisan xml:import`

- Lê `hotels.xml`, `rooms.xml` e `reserves.xml`, nesta ordem, pois quartos dependem de hotéis e reservas dependem de hotéis e quartos.
- Pode ser executado várias vezes sem duplicar dados: usa o `external_id` como chave no `updateOrCreate`.
- Reservas são ignoradas, com um aviso, quando o hotel ou o quarto não existem, quando o quarto não pertence ao hotel informado, ou quando o check-out é igual ou anterior ao check-in.
- Guests, dailies e payments são recriados a cada execução.
- Cada reserva é gravada dentro de uma transaction, com rollback em caso de falha.
- Dailies fora do período de estadia (como na reserva 6) geram avisos, mas continuam sendo importadas, para manter os dados financeiros do sistema de origem.
- A regra de conflito de períodos das reservas vale para a API; a importação grava o que vem do XML.

## Agendamento (cron)

O comando `xml:import` está agendado no Scheduler do Laravel (`routes/console.php`) para rodar uma vez por dia.

Em produção, basta uma entrada de cron no servidor, que chama o Scheduler a cada minuto:

```
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

Em desenvolvimento: `php artisan schedule:work`. Para listar o que está agendado: `php artisan schedule:list`.

## API

Todas as respostas são em JSON, inclusive os erros. As rotas não exigem autenticação.

Códigos de erro: **422** quando o payload é inválido; **409** quando o payload é válido, mas o estado atual do sistema não permite a operação.

### Quartos

| Verbo     | Rota              | Descrição                | Resposta      |
| --------- | ----------------- | ------------------------ | ------------- |
| GET       | `/api/rooms`      | Lista quartos (paginado) | 200           |
| GET       | `/api/rooms/{id}` | Mostra um quarto         | 200, 404      |
| POST      | `/api/rooms`      | Cria um quarto           | 201, 422      |
| PUT/PATCH | `/api/rooms/{id}` | Atualiza um quarto       | 200, 404, 422 |
| DELETE    | `/api/rooms/{id}` | Remove um quarto         | 204, 404, 409 |

Os hotéis não têm endpoint próprio: são criados pela importação dos XMLs. Depois do `php artisan xml:import`, existem os hotéis de id 1, 2 e 3.

Um quarto com reservas não pode ser removido (409), porque as reservas dependem dele.

### Reservas

| Verbo | Rota            | Descrição                                           | Resposta      |
| ----- | --------------- | --------------------------------------------------- | ------------- |
| POST  | `/api/reserves` | Cria uma reserva com hóspedes, dailies e pagamentos | 201, 409, 422 |

Regras:

- O hotel é derivado do quarto informado (`room_id`).
- O `total` é calculado pelo servidor, somando as dailies.
- As dailies devem cobrir cada noite da estadia, uma por data, do check-in até a véspera do check-out (422).
- Um quarto não pode ter duas reservas com períodos que se sobreponham, o que inclui repetir uma reserva idêntica (409). O dia do check-out não conta como ocupado, então uma reserva pode começar no dia em que outra termina.
- É obrigatório informar ao menos um hóspede (`name`, `last_name` e `phone`); pagamentos são opcionais.
- O `method` do pagamento é o código numérico que vem do XML de origem (por exemplo `1`); o projeto não define uma tabela de formas de pagamento.
- A reserva e as filhas são gravadas numa transaction: se algo falha, nada é gravado.

## Exemplos de requisições

Para colar no Postman ou no Thunder Client. Use sempre o header `Accept: application/json`. Os exemplos assumem o banco logo depois de `php artisan migrate` e `php artisan xml:import`.

### Quartos

**Listar** (aceita `?page=2`)

```
GET /api/rooms
```

**Mostrar**

```
GET /api/rooms/1
```

**Criar** (201)

```
POST /api/rooms
```

```json
{
    "hotel_id": 1,
    "name": "Suíte Master"
}
```

Resposta:

```json
{
    "data": {
        "id": 7,
        "hotel_id": 1,
        "name": "Suíte Master"
    }
}
```

**Criar com hotel inexistente** (422, erro em `hotel_id`)

```json
{
    "hotel_id": 999,
    "name": "Suíte Master"
}
```

**Atualizar** (200, aceita PUT e PATCH)

```
PUT /api/rooms/7
```

```json
{
    "name": "Suíte Presidencial"
}
```

**Remover um quarto sem reservas** (204): o quarto criado acima.

```
DELETE /api/rooms/7
```

**Remover um quarto com reservas** (409): os quartos 1 a 4 têm reservas vindas do XML.

```
DELETE /api/rooms/1
```

### Reservas

**Criar** (201)

```
POST /api/reserves
```

```json
{
    "room_id": 1,
    "check_in": "2026-12-01",
    "check_out": "2026-12-04",
    "guests": [
        { "name": "Maria", "last_name": "Silva", "phone": "11999999999" }
    ],
    "dailies": [
        { "date": "2026-12-01", "value": 100 },
        { "date": "2026-12-02", "value": 100 },
        { "date": "2026-12-03", "value": 100 }
    ],
    "payments": [{ "method": 1, "value": 100 }]
}
```

Resposta (o `total` é calculado pelo servidor):

```json
{
    "data": {
        "id": 7,
        "hotel_id": 1,
        "room_id": 1,
        "check_in": "2026-12-01",
        "check_out": "2026-12-04",
        "total": "300.00",
        "guests": [
            { "name": "Maria", "last_name": "Silva", "phone": "11999999999" }
        ],
        "dailies": [
            { "date": "2026-12-01", "value": "100.00" },
            { "date": "2026-12-02", "value": "100.00" },
            { "date": "2026-12-03", "value": "100.00" }
        ],
        "payments": [{ "method": 1, "value": "100.00" }]
    }
}
```

**Criar sem pagamentos** (201): os pagamentos são opcionais. Datas diferentes das do exemplo anterior, para não conflitar com ele.

```json
{
    "room_id": 1,
    "check_in": "2026-12-10",
    "check_out": "2026-12-12",
    "guests": [
        { "name": "João", "last_name": "Souza", "phone": "11988888888" }
    ],
    "dailies": [
        { "date": "2026-12-10", "value": 150 },
        { "date": "2026-12-11", "value": 150 }
    ]
}
```

**Reenviar a reserva do primeiro exemplo** (409): o quarto 1 já está ocupado de 01/12 a 04/12. O mesmo vale para qualquer período que se sobreponha a ele. Resposta:

```json
{
    "message": "Já existe uma reserva para este quarto no período informado"
}
```

**Dailies que não cobrem a estadia** (422, erro em `dailies`): faltou a noite de 03/12.

```json
{
    "room_id": 2,
    "check_in": "2026-12-01",
    "check_out": "2026-12-04",
    "guests": [
        { "name": "Maria", "last_name": "Silva", "phone": "11999999999" }
    ],
    "dailies": [
        { "date": "2026-12-01", "value": 100 },
        { "date": "2026-12-02", "value": 100 }
    ]
}
```

**Hóspede sem telefone** (422, erro em `guests.0.phone`)

```json
{
    "room_id": 2,
    "check_in": "2026-12-01",
    "check_out": "2026-12-02",
    "guests": [{ "name": "Maria", "last_name": "Silva" }],
    "dailies": [{ "date": "2026-12-01", "value": 100 }]
}
```

**Quarto inexistente** (422, erro em `room_id`)

```json
{
    "room_id": 999,
    "check_in": "2026-12-01",
    "check_out": "2026-12-02",
    "guests": [
        { "name": "Maria", "last_name": "Silva", "phone": "11999999999" }
    ],
    "dailies": [{ "date": "2026-12-01", "value": 100 }]
}
```

**Check-out anterior ao check-in** (422, erro em `check_out`): troque `check_out` por `"2026-11-30"` no payload de criação.

## Testes

Os testes usam um banco separado (`foco_challenge_test`), configurado no `phpunit.xml`, para nunca tocar nos dados de desenvolvimento. Crie o banco antes de rodar:

```sql
CREATE DATABASE foco_challenge_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Depois, para rodar todos os testes:

```bash
php artisan test
```

Cobertura atual:

- **Criação de reserva:** sucesso com total calculado e validações (dailies que não cobrem a estadia, data repetida, check-out anterior ao check-in, hóspede sem telefone, sem hóspedes, quarto inexistente). Nas falhas, os testes confirmam que nada é gravado.
- **Conflito de períodos:** reserva repetida e períodos sobrepostos são recusados (409); reserva que começa no dia do check-out anterior e mesmo período em quartos diferentes são aceitos.
- **Importação dos XMLs:** dados importados e execução repetida sem duplicar.
- **CRUD de quartos:** listar, mostrar, criar, atualizar e remover, incluindo a recusa (409) de remover um quarto com reservas.

## Logs

- `storage/logs/laravel.log`: eventos da API (reserva criada, reserva recusada por conflito de período ou por dailies que não cobrem a estadia, quartos criados, atualizados e removidos) e as exceções não tratadas.
- `storage/logs/import-AAAA-MM-DD.log`: execuções do `xml:import` (avisos e falhas). Mantém os últimos 14 dias.

Os logs registram apenas ids e valores de negócio, nunca dados pessoais dos hóspedes.
