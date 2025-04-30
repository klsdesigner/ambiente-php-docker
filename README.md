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
mkdir myAmbiente                # cria uma pasta
cd myAmbiente                   # entra na pasta

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
myAmbiente/
│── frontend/
|   | vueJs
|   | Dockerfile
|   |
|── backend/
|   | Laravel
|   | Dockerfile
|── nginx/
|   | nginx.conf
|── postgres/
|   docker-compose.yml
```

# Passo 2: Criar o `docker-compose.yml` na raiz do projeto:
Este arquivo define todos os serviços (containers) que vamos rodar.


```shell
# version: "3.9" 

# INICIA OS SERVIÇOS ##########################################################
services:

    # IMAGE DO BANCO DE DADOS #################################################
    postgres:
        image: postgres:14
        restart: always
        container_name: klsPostgres
        volumes:
            - ./postgres_data:/var/lib/postgresql/data
        environment:
            POSTGRES_DB: laravel_db
            POSTGRES_USER: root
            POSTGRES_PASSWORD: secret # Recomendo adicionar senha
        ports:
            - "5432:5432"
        networks:
            - laravel

    # IMAGE DO NGINX ###########################################################
    nginx:
        image: nginx:latest
        restart: always
        container_name: klsNginx
        ports:
            - "8080:80"            
        volumes:
            - ./nginx/nginx.conf:/etc/nginx/conf.d/default.conf
            - ./backend:/var/www/backend
        networks:
            - laravel

    # IMAGE DO LARAVEL 11 #######################################################
    backend:
        build:
            context: ./backend
            dockerfile: Dockerfile
            args:
                user: laraDocker
                uid: '1000'
        restart: always
        container_name: klsLaravel  
        working_dir: /var/www/backend
        volumes:
            - ./backend:/var/www/backend
        networks:
            - laravel
        depends_on:
            - postgres
            - nginx 
            - redis
        environment:
            DB_CONNECTION: pgsql
            DB_HOST: postgres
            DB_PORT: 5432
            DB_DATABASE: laravel_db
            DB_USERNAME: root
            DB_PASSWORD: secret 


    # IMAGE DO REDIS #############################################################
    redis:
        image: redis:latest
        restart: always
        container_name: klsRedis
        networks:
            - laravel           

networks:
    laravel:
        driver: bridge
```

📌 
# Dockerfile para o Backend (Laravel)
Este Dockerfile é para construir a imagem do backend usando PHP e Composer.

```shell
FROM php:8.3-fpm

ARG user
ARG uid

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpq-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    vim \
    && pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug \
    && docker-php-ext-install pdo pdo_pgsql\
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Criando um usuário administrador para acessar o git, Composer e Artisan
RUN useradd -G www-data,root -u $uid -d /home/$user $user && \
    mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

WORKDIR /var/www/backend

COPY .env . 

USER $user

CMD [ "php-fpm" ]    
```
### Explicação:
Instala as extensões PHP necessárias para o Laravel.

* Usa o Composer para instalar as dependências do projeto.
* Define permissões corretas para os diretórios storage e bootstrap/cache.
* Expõe a porta 9000, que é usada pelo PHP-FPM.


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
Crie a pasta `nginx/` e dentro dela um arquivo `nginx.conf`:

```shell
server {
    listen 80;
    index index.php;
    root /var/www/backend/public;

    client_max_body_size 51g;
    client_body_buffer_size 512k;
    client_body_in_file_only clean;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass backend:9000;
        fastcgi_index index.php;
        include fastcgi_params;        
        fastcgi_param SCRIPT_FILENAME /var/www/backend/public$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }

    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
}
```

# Passo 5: Subir os Containers
Agora que tudo está configurado, suba os containers:

bash
```shell
docker-compose up -d --build
```
Isso irá:

* Criar os containers do Nginx, PHP 8.2, PostgreSQL e Vue.js.
* Criar volumes persistentes para os projetos Laravel.
* Rodar o ambiente completo.

# Passo 6: Acessar a Aplicação
Depois que os containers estiverem rodando:

* Laravel rodando em PHP 8.2:
```
👉 http://localhost:8080

```
* Vue.js (caso precise acessar diretamente):
```
👉 http://localhost:5173

```

🔹 Passo 7: Rodar Laravel
Dentro do container PHP, rode os seguintes comandos para cada versão do Laravel:

bash
```shell
docker exec -it php72 bash
composer update
php artisan migrate --seed
```

🎯 # Conclusão

Agora você tem: 

* ✅ Laravel rodando em PHP 8.2
* ✅ Nginx servindo 
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





















