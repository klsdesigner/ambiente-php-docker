# AMBIENTE DESENVOLVIMENTO PHP COM DOCKER
Passos para criar container de ambiente de desenvolvimento php

## Servidor Web (Nginx vs Apache):

Nginx é mais leve e performático para aplicações modernas e é a escolha mais comum com Laravel.
Apache é mais tradicional, fácil de configurar com .htaccess, mas pode ser mais pesado.
Recomendação: Nginx, pois é mais otimizado para Laravel.
Banco de Dados (PostgreSQL vs MySQL):

PostgreSQL tem melhor suporte para consultas avançadas e é mais robusto para dados estruturados complexos.
MySQL é mais simples e amplamente utilizado em aplicações Laravel.
Recomendação: Depende do seu caso, mas se não houver necessidade de recursos avançados, MySQL pode ser mais fácil de configurar.
Passo a passo para criar o ambiente Docker
Teremos:

Um container para o Nginx
Dois containers para PHP (um para PHP 7.2 e outro para PHP 8.2)
Um container para o banco de dados (PostgreSQL ou MySQL)
Um container para o frontend Vue.js
Vou gerar um docker-compose.yml e as configurações necessárias. Você quer que eu inclua ambas as versões do PHP rodando simultaneamente no mesmo ambiente ou vai alternar entre elas conforme necessário?

# Passo 1: Estrutura do Projeto
Crie uma pasta para o seu ambiente de desenvolvimento:

#### bash
```
mkdir meu_projeto_docker
cd meu_projeto_docker
Dentro dessa pasta, vamos organizar os arquivos:
```

#### bash
```
meu_projeto_docker/
│── laravel_php72/         # Laravel rodando em PHP 7.2
│── laravel_php82/         # Laravel rodando em PHP 8.2
│── vue_frontend/          # Projeto Vue.js
│── docker/
│   ├── php72/             # Configuração do PHP 7.2
│   ├── php82/             # Configuração do PHP 8.2
│   ├── nginx/             # Configuração do Nginx
│   ├── postgres/          # Configuração do PostgreSQL (ou MySQL)
│── docker-compose.yml     # Arquivo principal do Docker Compose
│── .env                   # Configurações do ambiente
```

# Passo 2: Criar o `docker-compose.yml`
Este arquivo define todos os serviços (containers) que vamos rodar.

Crie um arquivo `docker-compose.yml` na raiz do projeto:

```
version: '3.8'

services:
  nginx:
    image: nginx:latest
    container_name: nginx_server
    restart: always
    ports:
      - "8080:80"
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./laravel_php72:/var/www/laravel_php72
      - ./laravel_php82:/var/www/laravel_php82
    depends_on:
      - php72
      - php82

  php72:
    build: ./docker/php72
    container_name: php72
    volumes:
      - ./laravel_php72:/var/www/laravel_php72
    depends_on:
      - db

  php82:
    build: ./docker/php82
    container_name: php82
    volumes:
      - ./laravel_php82:/var/www/laravel_php82
    depends_on:
      - db

  db:
    image: postgres:15
    container_name: postgres_db
    restart: always
    environment:
      POSTGRES_DB: meu_banco
      POSTGRES_USER: meu_usuario
      POSTGRES_PASSWORD: minha_senha
    volumes:
      - ./docker/postgres/data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  vue:
    image: node:18
    container_name: vue_frontend
    working_dir: /app
    volumes:
      - ./vue_frontend:/app
    command: sh -c "npm install && npm run dev"
    ports:
      - "5173:5173"
    depends_on:
      - nginx
```

# Passo 3: Configurar o PHP (7.2 e 8.2)
Agora, precisamos criar os arquivos de configuração do PHP.

📌 
# Criando o Dockerfile para PHP 7.2
Crie a pasta `docker/php72` e dentro dela um arquivo `Dockerfile`:

```
FROM php:7.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip unzip \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/laravel_php72

CMD ["php-fpm"]

```


📌 
# Criando o Dockerfile para PHP 8.2

Crie a pasta `docker/php82` e dentro dela um arquivo `Dockerfile`:

```
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip unzip \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/laravel_php82

CMD ["php-fpm"]

```

# Passo 4: Configurar o Nginx
Crie a pasta `docker/nginx/` e dentro dela um arquivo `nginx.conf`:


```
worker_processes 1;

events {
    worker_connections 1024;
}

http {
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    server {
        listen 80;

        location /php72 {
            root /var/www/laravel_php72/public;
            index index.php index.html;
            try_files $uri /index.php?$query_string;
            location ~ \.php$ {
                fastcgi_pass php72:9000;
                fastcgi_index index.php;
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                include fastcgi_params;
            }
        }

        location /php82 {
            root /var/www/laravel_php82/public;
            index index.php index.html;
            try_files $uri /index.php?$query_string;
            location ~ \.php$ {
                fastcgi_pass php82:9000;
                fastcgi_index index.php;
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                include fastcgi_params;
            }
        }
    }
}

```

# Passo 5: Subir os Containers
Agora que tudo está configurado, suba os containers:

bash
```
docker-compose up -d --build
```
Isso irá:

* Criar os containers do Nginx, PHP 7.2, PHP 8.2, PostgreSQL e Vue.js.
* Criar volumes persistentes para os projetos Laravel.
* Rodar o ambiente completo.

# Passo 6: Acessar a Aplicação
Depois que os containers estiverem rodando:

* Laravel rodando em PHP 7.2:
👉 http://localhost:8080/php72

* Laravel rodando em PHP 8.2:
👉 http://localhost:8080/php82

* Vue.js (caso precise acessar diretamente):
👉 http://localhost:5173

🔹 Passo 7: Rodar Laravel
Dentro do container PHP, rode os seguintes comandos para cada versão do Laravel:

bash
```
docker exec -it php72 bash
composer install
php artisan migrate --seed
php artisan serve
```

bash
```
docker exec -it php82 bash
composer install
php artisan migrate --seed
php artisan serve
```

🎯 # Conclusão

Agora você tem: 

* ✅ Laravel rodando em PHP 7.2 e 8.2
* ✅ Nginx servindo ambas as versões
* ✅ PostgreSQL armazenando os dados
* ✅ Vue.js rodando no frontend




