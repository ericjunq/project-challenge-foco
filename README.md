# Teste Técnico Foco Multimídia: API de Hotelaria

## Requisitos

- PHP 8.4.0
- Laravel Framework 13.34.0

## Configuração do ambiente

1. Clone o repositório no link https://github.com/ericjunq/projetc-challenge-foco
2. Instale as dependências pelo comando `composer install`
3. Faça uma cópia do arquivo de ambiente com o comando `cp .env.example .env`
4. Crie um banco `foco_challenge` (Para evitar bugs na hora de executar as migrations) no MySQL
5. Faça a configuração do banco:
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=foco_challenge
   DB_USERNAME=seu_usuario
   DB_PASSWORD=sua_senha
6. Gere a chave da aplicação com o comando `php artisan key:generate`
7. Execute as migrations com o comando `php artisan migrate`

## Banco de dados

Criação das tabelas:

- `hotels`
- `rooms`
- `reserves`
- `payments`
- `dailies`
- `guests`

## Importação de arquivos XML

Os arquivos XML de origem estão armazenados em `database/xml/` e simulam os dados de uma API.

Para fazer o import dos arquivos: `php artisan xml:import`

- Lê `hotels.xml`, `rooms.xml` e `reserves.xml`, nesta ordem, pois quartos dependem de hotéis e reservas dependem de hotéis e quartos.
- O comando pode ser executado várias vezes sem duplicar dados: usa o `external_id` como chave no `updateOrCreate`.
- Reservas são ignoradas, com um aviso, quando o hotel ou o quarto não existem, quando o quarto não pertence ao hotel informado, ou quando o check-out é igual ou anterior ao check-in.
- Reservas importadas são mantidas pelo XML: hóspedes, dailies e pagamentos são recriados a cada execução, então alterações feitas diretamente no banco ou pela API nesses registros são sobrescritas na próxima importação.
- Cada reserva é gravada dentro de uma transaction, com rollback em caso de falha.
- Dailies fora do período de estadia (como na reserva 6) geram avisos, mas continuam sendo importadas, para manter os dados financeiros do sistema de origem.

## Comando CRON

O comando `xml:import` foi agendado no Scheduler do Laravel (`routes/console.php`) para rodar uma vez por dia.
Em servidores de produção, basta apenas uma entrada de cron no servidor, que chama o Scheduler a cada minuto: \* \* \* \* \* cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
Em desenvolvimento: `php artisan schedule:work`. Para listar o que está agendado: `php artisan schedule:list`.

## API de quartos

| Verbo     | Rota              | Descrição                | Resposta      |
| --------- | ----------------- | ------------------------ | ------------- |
| GET       | `/api/rooms`      | Lista quartos (paginado) | 200           |
| GET       | `/api/rooms/{id}` | Mostra um quarto         | 200, 404      |
| POST      | `/api/rooms`      | Cria um quarto           | 201, 422      |
| PUT/PATCH | `/api/rooms/{id}` | Atualiza um quarto       | 200, 404, 422 |
| DELETE    | `/api/rooms/{id}` | Remove um quarto         | 204, 404, 409 |

Um quarto com reservas não pode ser removido (409), porque as reservas dependem dele.

Exemplo:

    POST /api/rooms
    {"hotel_id": 1, "name": "Suíte Master"}

    201 Created
    {"data": {"id": 7, "hotel_id": 1, "name": "Suíte Master"}}

## API de reservas

| Verbo | Rota            | Descrição                                           | Resposta |
| ----- | --------------- | --------------------------------------------------- | -------- |
| POST  | `/api/reserves` | Cria uma reserva com hóspedes, dailies e pagamentos | 201, 422 |

Regras:

- O hotel é derivado do quarto informado (`room_id`).
- O `total` é calculado pelo servidor, somando as dailies.
- As dailies devem cobrir cada noite da estadia, uma por data, do check-in até a véspera do check-out.
- Hóspedes são obrigatórios (ao menos um); pagamentos são opcionais.
- A reserva e as filhas são gravadas numa transaction: se algo falha, nada é gravado.

Exemplo:

    POST /api/reserves
    {
      "room_id": 1,
      "check_in": "2026-12-01",
      "check_out": "2026-12-04",
      "guests": [{"name": "Maria", "last_name": "Silva", "phone": "11999999999"}],
      "dailies": [
        {"date": "2026-12-01", "value": 100},
        {"date": "2026-12-02", "value": 100},
        {"date": "2026-12-03", "value": 100}
      ],
      "payments": [{"method": 1, "value": 100}]
    }

    201 Created
    {"data": {"id": 7, "hotel_id": 1, "room_id": 1, "total": "300.00", ...}}
