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
