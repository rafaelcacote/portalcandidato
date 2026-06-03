# Deploy do Portal Candidato — VPS com Docker

Guia para implantar o **portalcandidato** em produção. Use este arquivo quando estiver em outro computador — ele contém todo o contexto necessário.

**Stack:** Laravel 13 · PHP 8.3 · Vue 3 + Inertia v3 · Fortify · PostgreSQL · fila/sessão/cache em `database`

---

## Status — marque conforme avançar

- [x] **Fase 1** — VPS preparada (Ubuntu, Docker, usuário `deploy`, firewall, clone do Git)
- [ ] **Fase 2** — Arquivos `docker/` no repositório + build na VPS + `.env` produção
- [ ] **Fase 3** — Domínio, HTTPS, testes, backup do Postgres
- [ ] **Fase 4** — Deploy automático (GitHub Actions + `deploy.sh`)

---

## Fase 1 — O que você já fez (referência)

Confira na VPS se tudo está ok antes da Fase 2:

```bash
docker --version
docker compose version
whoami          # deploy
groups          # deve incluir docker
sudo ufw status # SSH, 80, 443 permitidos — NÃO 5432
ls /opt/portalcandidato/app
```

| Item | Detalhe |
|------|---------|
| Usuário | `deploy` (não usar root no dia a dia) |
| App | Clone em `/opt/portalcandidato/app` |
| Firewall | Só **22**, **80**, **443** públicos |
| Não expor | Postgres 5432, pgAdmin, Portainer, Vite 5173 |

---

## Visão da arquitetura

```text
Internet (HTTPS :443)
    ↓
Caddy ou Nginx + certificado TLS
    ↓
container web (Nginx → pasta public/)
    ↓
container app (PHP 8.3-FPM — Laravel)
    ↓
container postgres (só rede interna Docker)

Paralelo:
  container queue     → php artisan queue:work
  container scheduler → php artisan schedule:work
```

**Importante:** em produção o frontend é **compilado** (`npm run build` dentro do Dockerfile). Não rode `npm run dev` na VPS.

---

## Fase 2 — Docker de produção

### 2.1 Arquivos que devem existir no Git

Crie na **raiz do projeto** (no PC de casa):

```text
portalcandidato/
├── .dockerignore
├── docker/
│   ├── Dockerfile
│   ├── compose.prod.yml
│   └── nginx/
│       └── default.conf
└── docs/
    └── DEPLOY-VPS.md   ← este arquivo
```

> **Atalho:** no Cursor, modo **Agent**, peça: *“Crie os arquivos docker/ da Fase 2 conforme docs/DEPLOY-VPS.md”* se ainda não existirem.

### 2.2 `.dockerignore` (raiz do projeto)

```dockerignore
.git
.github
node_modules
vendor
.env
.env.*
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
tests
.phpunit.result.cache
```

### 2.3 `docker/Dockerfile`

```dockerfile
# Estágio 1: build do frontend (Vite + Vue + Inertia)
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Estágio 2: aplicação PHP
FROM php:8.3-fpm-bookworm AS app
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo_pgsql pgsql mbstring zip gd opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
```

### 2.4 `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 2.5 `docker/compose.prod.yml`

O `context: ..` aponta para a **raiz do projeto** (pasta acima de `docker/`).

```yaml
name: portalcandidato

services:
  postgres:
    image: postgres:16-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE:-portalcandidato}
      POSTGRES_USER: ${DB_USERNAME:-portal_app}
      POSTGRES_PASSWORD: ${DB_PASSWORD:?Defina DB_PASSWORD no .env}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - portal
    healthcheck:
      test: ['CMD-SHELL', 'pg_isready -U "$${POSTGRES_USER}" -d "$${POSTGRES_DB}"']
      interval: 10s
      timeout: 5s
      retries: 5

  app:
    build:
      context: ..
      dockerfile: docker/Dockerfile
    restart: unless-stopped
    env_file: ../.env
    environment:
      DB_HOST: postgres
    volumes:
      - app_storage:/var/www/html/storage/app
    depends_on:
      postgres:
        condition: service_healthy
    networks:
      - portal

  web:
    image: nginx:alpine
    restart: unless-stopped
    ports:
      - '80:80'
    volumes:
      - ../public:/var/www/html/public:ro
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app
    networks:
      - portal

  queue:
    build:
      context: ..
      dockerfile: docker/Dockerfile
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    env_file: ../.env
    environment:
      DB_HOST: postgres
    depends_on:
      - app
    networks:
      - portal

  scheduler:
    build:
      context: ..
      dockerfile: docker/Dockerfile
    restart: unless-stopped
    command: php artisan schedule:work
    env_file: ../.env
    environment:
      DB_HOST: postgres
    depends_on:
      - app
    networks:
      - portal

networks:
  portal:
    driver: bridge

volumes:
  postgres_data:
  app_storage:
```

### 2.6 No PC de casa — commit e push

```bash
cd portalcandidato
git add docker/ .dockerignore docs/DEPLOY-VPS.md
git commit -m "Adiciona configuração Docker de produção"
git push origin main
```

### 2.7 Na VPS — atualizar código e `.env`

```bash
cd /opt/portalcandidato/app
git pull

cp .env.example .env
nano .env
chmod 600 .env
```

**`.env` de produção (ajuste valores reais):**

```env
APP_NAME="Portal Candidato"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://SEU-DOMINIO.br

APP_KEY=
# Gerar na etapa 2.9

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=portalcandidato
DB_USERNAME=portal_app
DB_PASSWORD=COLOQUE_SENHA_FORTE_AQUI

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_ENCRYPT=true

LOG_LEVEL=warning

MAIL_MAILER=smtp
# ... configurar SMTP institucional quando tiver

LGPD_DATA_CONTROLLER="Universidade do Estado do Amazonas (UEA) — ProEnSP"
LGPD_CONTACT_EMAIL=privacidade@seudominio.br
```

| Variável | Local (dev) | VPS (Docker) |
|----------|---------------|--------------|
| `APP_DEBUG` | `true` | **`false`** |
| `DB_HOST` | `127.0.0.1` | **`postgres`** |
| `APP_URL` | `http://localhost` | `https://domínio` |

### 2.8 Build e subir containers

```bash
cd /opt/portalcandidato/app

docker compose -f docker/compose.prod.yml build --no-cache
docker compose -f docker/compose.prod.yml up -d
docker compose -f docker/compose.prod.yml ps
```

Todos os serviços devem aparecer como `Up` (postgres `healthy`).

### 2.9 Comandos Laravel (primeira vez)

```bash
cd /opt/portalcandidato/app

docker compose -f docker/compose.prod.yml exec app php artisan key:generate --force
docker compose -f docker/compose.prod.yml exec app php artisan migrate --force
docker compose -f docker/compose.prod.yml exec app php artisan storage:link
docker compose -f docker/compose.prod.yml exec app php artisan config:cache
docker compose -f docker/compose.prod.yml exec app php artisan route:cache
docker compose -f docker/compose.prod.yml exec app php artisan view:cache
```

### 2.10 Teste

- Abra `http://IP-DA-VPS` no navegador.
- Se **502**: `docker compose -f docker/compose.prod.yml logs app --tail=100`
- Se **sem CSS/JS**: confira build — `docker compose -f docker/compose.prod.yml exec app ls -la public/build`

### 2.11 O que NÃO fazer (tutorial antigo incorreto)

- Não rodar `composer create-project laravel/laravel`
- Não instalar Breeze (o projeto usa **Fortify**)
- Não usar `npm run dev` em produção
- Não `chmod 777` em `storage`
- Não expor Postgres na porta 5432 para a internet
- Não usar senhas como `secret123` ou `admin123`

---

## Fase 3 — HTTPS, testes e backup

### 3.1 DNS

Registro **A**: `portal.seudominio.br` → IP da VPS.

### 3.2 HTTPS

Opções:

- **Caddy** na VPS com reverse proxy para `localhost:80` (Let's Encrypt automático)
- **Certbot** + Nginx no host

Após HTTPS, atualize `.env`:

```env
APP_URL=https://portal.seudominio.br
```

```bash
docker compose -f docker/compose.prod.yml exec app php artisan config:cache
```

### 3.3 Checklist de testes

- [ ] Login / logout (Fortify)
- [ ] Fluxo principal do candidato
- [ ] E-mail (se SMTP configurado)
- [ ] Fila processando (`queue` container ativo)
- [ ] `APP_DEBUG=false` — sem stack trace público
- [ ] HTTPS e cookies de sessão ok

### 3.4 Backup PostgreSQL

Crie `/opt/portalcandidato/backup-db.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail
BACKUP_DIR=/opt/portalcandidato/backups
mkdir -p "$BACKUP_DIR"
FILE="$BACKUP_DIR/portalcandidato-$(date +%Y%m%d-%H%M%S).sql.gz"

docker compose -f /opt/portalcandidato/app/docker/compose.prod.yml \
  exec -T postgres pg_dump -U portal_app portalcandidato | gzip > "$FILE"

find "$BACKUP_DIR" -name '*.sql.gz' -mtime +14 -delete
```

```bash
chmod +x /opt/portalcandidato/backup-db.sh
crontab -e
# Linha: 0 3 * * * /opt/portalcandidato/backup-db.sh
```

Copie backups para **fora** da VPS (outro servidor ou nuvem).

---

## Fase 4 — Deploy automático via Git

### 4.1 Chave SSH (PC de casa)

```bash
ssh-keygen -t ed25519 -f deploy_portalcandidato -N ""
```

- **Pública** → VPS: `~deploy/.ssh/authorized_keys`
- **Privada** → GitHub Secret `DEPLOY_SSH_KEY`

Teste: `ssh -i deploy_portalcandidato deploy@IP-DA-VPS`

### 4.2 Script na VPS — `/opt/portalcandidato/deploy.sh`

```bash
#!/usr/bin/env bash
set -euo pipefail

APP_DIR=/opt/portalcandidato/app
COMPOSE="docker compose -f docker/compose.prod.yml"

cd "$APP_DIR"

git fetch origin
git checkout main
git pull --ff-only

$COMPOSE build app
$COMPOSE run --rm app php artisan migrate --force
$COMPOSE up -d --remove-orphans

$COMPOSE exec -T app php artisan config:cache
$COMPOSE exec -T app php artisan route:cache
$COMPOSE exec -T app php artisan view:cache

docker image prune -f
```

```bash
chmod +x /opt/portalcandidato/deploy.sh
```

### 4.3 Secrets no GitHub

Repositório → **Settings → Secrets and variables → Actions**:

| Secret | Valor |
|--------|--------|
| `DEPLOY_HOST` | IP ou domínio da VPS |
| `DEPLOY_USER` | `deploy` |
| `DEPLOY_SSH_KEY` | chave privada (conteúdo completo) |

### 4.4 Workflow — `.github/workflows/deploy.yml`

```yaml
name: deploy

on:
  push:
    branches:
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to VPS
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_SSH_KEY }}
          script: /opt/portalcandidato/deploy.sh
```

Fluxo: `git push` em `main` → Actions executa testes (se configurado) → SSH → `deploy.sh`.

**Nunca** commite o `.env` com senhas.

---

## Comandos úteis (cola rápida)

```bash
cd /opt/portalcandidato/app
C="docker compose -f docker/compose.prod.yml"

$C ps
$C logs -f app
$C logs -f queue
$C exec app php artisan migrate:status
$C exec app php artisan down
$C exec app php artisan up
$C restart queue
$C down
$C up -d
```

---

## Ordem sugerida em casa (outro PC)

1. Clonar o repositório: `git clone ...`
2. Abrir `docs/DEPLOY-VPS.md` (este arquivo)
3. Criar pasta `docker/` e arquivos das seções 2.2–2.5 (se ainda não existirem no repo)
4. `git push` → na VPS: `git pull`
5. Configurar `.env` na VPS (seção 2.7)
6. `docker compose build` + `up` + artisan (seções 2.8–2.9)
7. Testar no IP → Fase 3 (domínio, HTTPS, backup)
8. Fase 4 quando o site estiver estável

---

## Referências

- [Laravel — Deployment](https://laravel.com/docs/deployment)
- [Docker Compose](https://docs.docker.com/compose/)
- Versões do projeto: `AGENTS.md` e `composer.json` (PHP 8.3, Node 22 no CI)

---

*Documento gerado para continuidade do deploy. Fase 1 concluída; Fases 2–4 pendentes.*
