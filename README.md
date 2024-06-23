# Prova tecnica para l5 network, SP/Brasil.

#### Requirements

- PHP ^8.1
- Codeigniter ^4.5
- Mysql ^8
- Composer ^2.7

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

- .env.example file
- Jwt secret
- Jwt expiration
- Database connection
- App url

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
Recomendavel instalar Rest Client de HUACHAO no vscode \
Os archivos rest de teste estão na pasta tests > HTTP_TESTS

Reference   
Name: REST Client  
Id: humao.rest-client  
Description: REST Client for Visual Studio Code  
Version: 0.25.1  
Publisher: Huachao Mao  
VS Marketplace Link: https://marketplace.visualstudio.com/items?itemName=humao.rest-client  


Dentro da pasta tests > HTTP_TESTS há um arquivo .env que são as variaveis de ambiente do próprio REST CLIENT

Usar o endpoint de registro para registrar um usuario com o qual depois pode fazer login recebe um JWT TOKEN para fazer as requisições protegidas.

## Endpoints
### AUTH
#### PUBLIC

```
/api/auth/register
/api/auth/login
```
#### PROTECTED
```
/api/auth/user-info
```
### CLIENTES
```
GET /api/v1/clientes
GET /api/v1/clientes/(:num)
POST /api/v1/clientes
PUT /api/v1/clientes/(:num)
DELETE /api/v1/clientes/(:num)
```

### PRODUTOS
```
GET /api/v1/produtos
GET /api/v1/produtos/(:num)
POST /api/v1/produtos
PUT /api/v1/produtos/(:num)
DELETE /api/v1/produtos/(:num)
```

### PEDIDOS
```
GET /api/v1/pedidos
GET /api/v1/pedidos/(:num)
POST /api/v1/pedidos
PUT /api/v1/pedidos/(:num)
DELETE /api/v1/pedidos/(:num)
```

