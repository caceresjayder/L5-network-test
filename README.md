# Prova tecnica para l5 network, SP/Brasil.

Requirements

-PHP ^8.1
-Codeigniter ^4.5
-Mysql ^8
-Composer ^2.7

### Clone repository

```
git clone https://github.com/caceresjayder/L5-network-test.git
```

### Install dependencies

```
composer install
```

### Generate Key

```
php spark key:generate
```

### Sets enviroment

-.env.example file
-Jwt secret
-Jwt expiration
-Database connection
-App url

### Create DB
```
php spark db:create
```

### Run migrations
```
php spark migrate
```

### Run seeder
```
php spark db:seed SeederDB
```

### Testes foram feitos usando http templates
Recomendavel instalar Rest Client de HUACHAO no vscode
\n Os archivos rest de teste estão na pasta tests > HTTP_TESTS

\n Reference 
\n Name: REST Client
\n Id: humao.rest-client
\n Description: REST Client for Visual Studio Code
\n Version: 0.25.1
\n Publisher: Huachao Mao
\n VS Marketplace Link: https://marketplace.visualstudio.com/items?itemName=humao.rest-client


Dentro da pasta tests > HTTP_TESTS está um arquivo .env que são as variaveis de ambiente do próprio REST CLIENT

\n Usar o endpoint de registro para registrar um usuario com o qual depois pode fazer login recebe um JWT TOKEN para fazer as requisições protegidas.