# Modelagem do banco de dados

```mermaid
erDiagram
    HOTELS ||--o{ ROOMS : possui
    HOTELS ||--o{ RESERVES : recebe
    ROOMS ||--o{ RESERVES : "é reservado em"
    RESERVES ||--o{ GUESTS : possui
    RESERVES ||--o{ DAILIES : possui
    RESERVES ||--o{ PAYMENTS : possui

    HOTELS {
        bigint id PK
        int external_id UK "id no XML de origem, nulo se criado fora do import"
        string name "100"
    }
    ROOMS {
        bigint id PK
        int external_id UK
        bigint hotel_id FK
        string name "255"
    }
    RESERVES {
        bigint id PK
        int external_id UK
        bigint hotel_id FK
        bigint room_id FK
        date check_in
        date check_out
        decimal total "10,2"
    }
    GUESTS {
        bigint id PK
        bigint reserve_id FK
        string name "100"
        string last_name "100"
        string phone "15, obrigatório"
    }
    DAILIES {
        bigint id PK
        bigint reserve_id FK
        date date "unique com reserve_id"
        decimal value "8,2"
    }
    PAYMENTS {
        bigint id PK
        bigint reserve_id FK
        tinyint method "sem sinal"
        decimal value "10,2"
    }
```

## Decisões da modelagem

- Valores monetários em `DECIMAL`, para evitar erro de arredondamento do ponto flutuante.
- `guests`, `dailies` e `payments` são tabelas separadas porque o XML traz essas coleções dentro da reserva.
- `dailies` e `payments` pertencem à reserva: um pagamento não está atrelado a uma noite específica.
- `unique(reserve_id, date)` em `dailies` impede duas diárias na mesma noite da mesma reserva.
- Índice em `reserves(room_id, check_in, check_out)` para acelerar a consulta de conflito de datas.
- `external_id` guarda o id do registro no XML de origem, permite que o import rode várias vezes sem duplicar dados (`updateOrCreate`) e é nulo para registros criados pela API.
- Todas as chaves estrangeiras usam `ON DELETE CASCADE`. Por isso a API recusa (409) a remoção de um quarto que tenha reservas, em vez de apagá-las em silêncio.
