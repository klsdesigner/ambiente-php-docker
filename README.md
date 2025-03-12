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
│   ├── postgres/          # Configuração do PostgreSQL (ou MySQL)
│── docker-compose.yml     # Arquivo principal do Docker Compose
|── nginx/                 # Configuração do Nginx
```

# Passo 2: Criar o `docker-compose.yml`
Este arquivo define todos os serviços (containers) que vamos rodar.

Crie um arquivo `docker-compose.yml` na raiz do projeto:

```
version: '3.9'

services:
  nginx:
    image: nginx:latest
    container_name: nginx_server
    ports:
      - "8080:80"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/conf.d/nginx.conf:ro        
      - ./lara-7.2:/var/www/lara-7.2
      - ./lara-8.2:/var/www/lara-8.2
    depends_on:
      - php72
      - php82
    networks:
      - app_network

  php72:
    build:
      context: ./docker/php72
      dockerfile: Dockerfile
    container_name: php72
    volumes:
      - ./lara-7.2:/var/www/lara-7.2
    networks:
      - app_network

  php82:
    build:
      context: ./docker/php82
      dockerfile: Dockerfile
    container_name: php82
    volumes:
      - ./lara-8.2:/var/www/lara-8.2    
    networks:
      - app_network

  postgres:
    image: postgres:15 ##latest
    container_name: postgres
    restart: always
    environment:
      POSTGRES_USER: root
      POSTGRES_PASSWORD: 
      POSTGRES_DB: laravel
    ports:
      - "5432:5432"
    volumes:
      - ./docker/postgres/data:/var/lib/postgresql/data      
    networks:
      - app_network

  vue:
    build:
      context: ./frontend
      dockerfile: ../Docker/vue/Dockerfile
    container_name: vue
    ports:
      - "5173:5173"
    volumes:
      - ./vue:/frontend
    depends_on:
      - php72
      - php82
    networks:
      - app_network
    command: ["npm", "run", "dev"]

volumes:
  postgres_data:
networks:
  app_network:
    driver: bridge
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
WORKDIR /var/www/lara-7.2
COPY . .
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
WORKDIR /var/www/lara-8.2
COPY . .
CMD ["php-fpm"]

```

📌 
# Criando o Dockerfile para Vue.js
Crie a pasta `docker/vue` e dentro dela um arquivo `Dockerfile`:

```
FROM node:18
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
EXPOSE 5173
CMD ["npm", "run", "dev"]
```

# Passo 4: Configurar o Nginx
Crie a pasta `docker/nginx/` e dentro dela um arquivo `nginx.conf`:

```
server {
    listen 80;
    server_name localhost;
    location /lara-7.2 {
        root /var/www/lara-7.2/public;
        index index.php index.html index.htm;
        try_files $uri $uri/ /index.php?$query_string;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass php72:9000;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
    }

    location /lara-8.2 {
        root /var/www/lara-8.2/public;
        index index.php index.html index.htm;
        try_files $uri $uri/ /index.php?$query_string;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass php82:9000;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
    }

    location / {
        root /app;
        index index.html;
        try_files $uri /index.html;
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

# ALGUNS COMANDOS DOKER

Faça login em um registro (como o Docker Hub) a partir do CLI. Isso salva credenciais localmente.
```
docker login
docker login -u myusername
```

### Para Docker Hub
```
docker push myuser/myimage:v1   # Enviar imagem personalizada para Docker Hub
docker pull someimage           # Puxar imagem compartilhada
docker images                   # Listar imagens baixadas
docker rmi <image>              # Remover/apagar imagem
```

### No Docker
```
docker version
docker --help
```

```
docker run        # Iniciar novo contêiner a partir da imagem
docker ps         # Listar contêineres em execução
docker logs       # Imprimir logs do contêiner
docker stop       # Parar de executar o contêiner
docker rm         # Remover/excluir contêiner
```

Cria uma imagem Docker lendo instruções de construção de um Dockerfile.
```
docker build                         # Construir imagem a partir do Dockerfile
docker build --platform=linux/amd64  # Construir para arquitetura específica
```

Cria um volume persistente e gerenciado que pode sobreviver aos contêineres. docker run -v- Monta um volume em um contêiner 
específico para permitir a persistência de dados após o ciclo de vida do contêiner.
```
docker volume create         # Criar volume
docker run -v <vol>:/data    # Montar volume no contêiner
```

Cria uma rede virtual personalizada para comunicação de contêineres. docker run --network=<name>- Conecta um contêiner em 
execução a uma rede definida pelo usuário do Docker.
```
docker network create           # Criar rede definida pelo usuário
docker run --network=<name>     # Conectar contêiner
```

Executa um comando em um contêiner já em execução. Útil para depurar/inspecionar contêineres:
```
docker exec
docker exec mycontainer ls -l /etc     # Listar arquivos no contêiner
```





















