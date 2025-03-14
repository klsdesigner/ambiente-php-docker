# AMBIENTE LARAVEL(php) COM DOCKER
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
```shell
mkdir meu_projeto_docker                # cria uma pasta
cd meu_projeto_docker                   # entra na pasta

rmdir /s /q "C:\caminho\para\a\pasta"   # deleta uma pasta 
```
Dentro dessa pasta, vamos organizar os arquivos:

# Um docker-compose.yml por cliente (Recomendado para isolamento total)
Cada cliente tem seu próprio docker-compose.yml, o que garante que os ambientes sejam completamente isolados e independentes. Isso é útil quando:

* Cada cliente tem requisitos específicos (versões diferentes de PHP, banco de dados, etc.).
* Você precisa rodar os projetos de clientes diferentes simultaneamente.
* Você quer evitar conflitos de portas, redes ou volumes.

Vantagens:
* Isolamento total entre clientes.
* Fácil de gerenciar individualmente.
* Menos risco de conflitos de configuração.

Desvantagens:
* Pode haver redundância de configurações (por exemplo, se vários clientes usam a mesma versão do PHP ou Nginx).
* Maior número de arquivos para gerenciar.

# Abordagem 1: Um docker-compose.yml por cliente
#### Estrutura de pastas para dockerizar projetos
```
meus_projetos_docker/
│── clientA/
|   | docker-compose.yml
|   │── frontend/
|   |   | Dockerfile
|   |   | nginx.conf
|   |   │── src/
|   │── backend/
|   |   | Dockerfile
|   |   │── src/
|   │── nginx/
|   |   | Dockerfile
|   |   | nginx.conf
|   │── postgres/
|   |   | Dockerfile
|   |   | init.sql
|-----------------------
│── clientB/
|   | docker-compose.yml
|   │── frontend/
|   |   | Dockerfile
|   |   │── src/
|   │── backend/
|   |   | Dockerfile
|   |   │── src/
|   │── nginx/
|   |   | Dockerfile
|   |   | nginx.conf
|   │── postgres/
|   |   | Dockerfile
|   |   | init.sql
```

# Passo 2: Criar o `docker-compose.yml` para ClientA
Este arquivo define todos os serviços (containers) que vamos rodar.

Crie um arquivo `docker-compose.yml` na raiz do projeto:

```shell
version: '3.8'

services:
  frontend:
    build: ./frontend
    networks:
      - clientA_network

  backend:
    build: ./backend
    networks:
      - clientA_network
    environment:
      DB_HOST: postgres
      DB_DATABASE: clientA_db
      DB_USERNAME: user
      DB_PASSWORD: password
    depends_on:
      - postgres

  nginx:
    build: ./nginx
    ports:
      - "8080:80"
    networks:
      - clientA_network
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/conf.d/default.conf
      - ./backend:/var/www/html
    depends_on:
      - frontend
      - backend

  postgres:
    build: ./postgres
    environment:
      POSTGRES_DB: clientA_db
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
    volumes:
      - ./postgres/data:/var/lib/postgresql/data
    networks:
      - clientA_network

networks:
  clientA_network:
    driver: bridge
```

# Abordagem híbrida (Recomendado para flexibilidade)
Você pode combinar as duas abordagens. Por exemplo:

* Use um docker-compose.yml por cliente para projetos que precisam de isolamento.
* Use um docker-compose.yml central para projetos que compartilham configurações semelhantes.

# Abordagem 2: Um único docker-compose.yml para todos os clientes
#### Estrutura de Pastas
```
meus_projetos_docker/
|── clientA/
|   |── frontend/
|   |   | Dockerfile
|   |   |── src/
|   |── backend/
|   |   Dockerfile
|   |   |── src/
|   |── nginx/
|   |   | Dockerfile
|   |   | nginx.conf
|   |── postgres/
|   |   | Dockerfile
|   |   | init.sql
|------------------------
|── clientB/
|   |── frontend/
|   |   | Dockerfile
|   |   |── src/
|   |── backend/
|   |   | Dockerfile
|   |   |── src/
|   |── nginx/
|   |   | Dockerfile
|   |   | nginx.conf
|   |── postgres/
|   |   | Dockerfile
|   |   | init.sql
| docker-compose.yml
```

# Exemplo de `docker-compose.yml` central
```shell
version: '3.8'

services:
  clientA_frontend:
    build: ./clients/clientA/frontend
    ports:
      - "8081:80"  # Porta exposta para o frontend do ClientA
    volumes:
      - ./clients/clientA/frontend/src:/app/src
    networks:
      - clientA_network

  clientA_backend:
    build: ./clients/clientA/backend
    volumes:
      - ./clients/clientA/backend/src:/var/www/html
    environment:
      DB_HOST: clientA_postgres
      DB_DATABASE: clientA_db
      DB_USERNAME: user
      DB_PASSWORD: password
    networks:
      - clientA_network
    depends_on:
      - clientA_postgres

  clientA_nginx:
    build: ./clients/clientA/nginx
    ports:
      - "80:80"  # Porta exposta para o Nginx do ClientA
    volumes:
      - ./clients/clientA/nginx/nginx.conf:/etc/nginx/nginx.conf
    networks:
      - clientA_network
    depends_on:
      - clientA_frontend
      - clientA_backend

  clientA_postgres:
    build: ./clients/clientA/postgres
    environment:
      POSTGRES_DB: clientA_db
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
    volumes:
      - ./clients/clientA/postgres/data:/var/lib/postgresql/data
    networks:
      - clientA_network

  clientB_frontend:
    build: ./clients/clientB/frontend
    ports:
      - "8082:80"  # Porta exposta para o frontend do ClientB
    volumes:
      - ./clients/clientB/frontend/src:/app/src
    networks:
      - clientB_network

  clientB_backend:
    build: ./clients/clientB/backend
    volumes:
      - ./clients/clientB/backend/src:/var/www/html
    environment:
      DB_HOST: clientB_postgres
      DB_DATABASE: clientB_db
      DB_USERNAME: user
      DB_PASSWORD: password
    networks:
      - clientB_network
    depends_on:
      - clientB_postgres

  clientB_nginx:
    build: ./clients/clientB/nginx
    ports:
      - "81:80"  # Porta exposta para o Nginx do ClientB
    volumes:
      - ./clients/clientB/nginx/nginx.conf:/etc/nginx/nginx.conf
    networks:
      - clientB_network
    depends_on:
      - clientB_frontend
      - clientB_backend

  clientB_postgres:
    build: ./clients/clientB/postgres
    environment:
      POSTGRES_DB: clientB_db
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
    volumes:
      - ./clients/clientB/postgres/data:/var/lib/postgresql/data
    networks:
      - clientB_network

networks:
  clientA_network:
    driver: bridge
  clientB_network:
    driver: bridge
```

# Diferenças entre as abordagens
### Isolamento:
* Na abordagem 1, cada cliente tem seu próprio docker-compose.yml, garantindo isolamento total.
* Na abordagem 2, todos os serviços estão no mesmo arquivo, o que pode levar a conflitos de portas ou redes se não for configurado corretamente.

### Facilidade de gerenciamento:
* A abordagem 1 é mais fácil de gerenciar individualmente, mas pode resultar em mais arquivos.
* A abordagem 2 centraliza a configuração, mas pode ficar complexa com muitos clientes.

### Escalabilidade:
* A abordagem 1 é mais escalável para muitos clientes com requisitos diferentes.
* A abordagem 2 é mais adequada para projetos semelhantes ou poucos clientes.


📌 
# Dockerfile para o Backend (Laravel)
Este Dockerfile é para construir a imagem do backend usando PHP e Composer.

```shell
# Usa uma imagem base com PHP e Composer
FROM php:8.2-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql zip mbstring exif pcntl bcmath gd

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . .

# Instala as dependências do Composer
RUN composer install --optimize-autoloader --no-dev

# Define permissões para o Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expõe a porta 9000 (padrão do PHP-FPM)
EXPOSE 9000

# Comando para iniciar o PHP-FPM
CMD ["php-fpm"]
```
### Explicação:
Instala as extensões PHP necessárias para o Laravel.

* Usa o Composer para instalar as dependências do projeto.
* Define permissões corretas para os diretórios storage e bootstrap/cache.
* Expõe a porta 9000, que é usada pelo PHP-FPM.

📌
# Dockerfile para o Nginx
Este Dockerfile é para construir a imagem do Nginx, que atua como um proxy reverso para o frontend e o backend.

```shell
# Usa a imagem oficial do Nginx
FROM nginx:alpine

# Copia a configuração personalizada do Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expõe a porta 80
EXPOSE 80

# Comando para iniciar o Nginx
CMD ["nginx", "-g", "daemon off;"]
```
### Explicação:
* Copia o arquivo de configuração nginx.conf para o contêiner.*
* Expõe a porta 80, que é usada para servir o tráfego HTTP.

📌
# Dockerfile para o PostgreSQL
Este Dockerfile é para construir a imagem do PostgreSQL com um script de inicialização.
```shell
# Usa a imagem oficial do PostgreSQL
FROM postgres:14

# Copia o script de inicialização do banco de dados
COPY init.sql /docker-entrypoint-initdb.d/

# Expõe a porta 5432
EXPOSE 5432
```
### Explicação:
* Copia o arquivo init.sql para o diretório /docker-entrypoint-initdb.d/, que é executado automaticamente quando o contêiner é iniciado.
* Expõe a porta 5432, que é usada pelo PostgreSQL.





📌 
# Criando o Dockerfile para Vue.js
Crie a pasta `docker/vue` e dentro dela um arquivo `Dockerfile`:

```shell
FROM node:20 as build-stage
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Estágio de produção: Serve os arquivos estáticos com Nginx
FROM nginx:alpine as production-stage
COPY --from=build-stage /app/dist /usr/share/nginx/html
COPY ./nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

# Passo 4: Configurar o Nginx
Crie a pasta `docker/nginx/` e dentro dela um arquivo `nginx.conf`:

```shell
server {
    listen 80;
    server_name localhost;

    # Frontend (Vue.js)
    location / {
        proxy_pass http://frontend:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api {
        try_files $uri $uri/ /api/index.php?$query_string;  # Corrigindo a lógica do try_files
    }

    location ~ \.php$ {
        fastcgi_pass backend:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/html/public$fastcgi_script_name;  # Ajustando o caminho para o script PHP
        include fastcgi_params;
    }

    # Configuração de erro
    error_page 500 502 503 504 /50x.html;
    location = /50x.html {
        root /usr/share/nginx/html;
    }
}
```

# Passo 5: Subir os Containers
Agora que tudo está configurado, suba os containers:

bash
```shell
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
```shell
docker exec -it php72 bash
composer install
php artisan migrate --seed
php artisan serve
```

bash
```shell
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


# ALGUNS COMANDOS DOKER

### Imagens
+ `docker images`: Lista todas as imagens locais.
+ `docker pull <imagem>`: Baixa uma imagem do Docker Hub.
+ `docker rmi <imagem>`: Remove uma imagem local.
+ `docker build -t <nome_da_imagem>` .: Constrói uma imagem a partir de um Dockerfile.

### Contêineres
+ `docker ps`: Lista contêineres em execução.
+ `docker ps -a`: Lista todos os contêineres (em execução e parados).
+ `docker run <imagem>`: Executa um contêiner a partir de uma imagem.
+ `docker start <contêiner>`: Inicia um contêiner parado.
+ `docker stop <contêiner>`: Para um contêiner em execução.
+ `docker restart <contêiner>`: Reinicia um contêiner.
+ `docker rm <contêiner>`: Remove um contêiner.
+ `docker exec -it <contêiner> <comando>`: Executa um comando dentro de um contêiner em execução.
+ `docker logs <contêiner>`: Exibe os logs de um contêiner.

### Redes
+ `docker network ls`: Lista todas as redes.
+ `docker network create <nome_da_rede>`: Cria uma nova rede.
+ `docker network inspect <rede>`: Exibe detalhes de uma rede.

### Volumes
+ `docker volume ls`: Lista todos os volumes.
+ `docker volume create <nome_do_volume>`: Cria um novo volume.
+ `docker volume inspect <volume>`: Exibe detalhes de um volume.

### Sistema
+ `docker info`: Exibe informações sobre o Docker.
+ `docker version`: Exibe a versão do Docker.
+ `docker system prune`: Remove todos os contêineres, redes e imagens não utilizadas.

# Comandos Docker Compose
### Execução
+ `docker-compose up`: Inicia todos os serviços definidos no docker-compose.yml.
+ `docker-compose up -d`: Inicia os serviços em segundo plano (detached mode).
+ `docker-compose down`: Para e remove todos os contêineres, redes e volumes definidos no docker-compose.yml.
+ `docker-compose start`: Inicia os serviços.
+ `docker-compose stop`: Para os serviços.
+ `docker-compose restart`: Reinicia os serviços.

### Logs e Status
+ `docker-compose logs`: Exibe os logs dos serviços.
+ `docker-compose ps`: Lista os contêineres dos serviços.
+ `docker-compose top`: Exibe os processos em execução nos contêineres.

### Execução de Comandos
+ `docker-compose exec <serviço> <comando>`: Executa um comando dentro de um contêiner de um serviço.

### Build e Imagens
+ `docker-compose build`: Constrói ou reconstrói as imagens dos serviços.
+ `docker-compose pull`: Baixa as imagens dos serviços.

### Configuração
+ `docker-compose config`: Valida e exibe a configuração do docker-compose.yml.

Esses são os comandos mais comuns e úteis para trabalhar com Docker e Docker Compose. Eles cobrem a maioria das operações 
diárias que você pode precisar realizar ao gerenciar contêineres e serviços.





















