# Deploy do Portal Candidato — VPS com Docker

Guia para implantar o **portalcandidato** em produção. Use este arquivo quando estiver em outro computador — ele contém todo o contexto necessário.

**Stack:** Laravel 13 · PHP 8.3 · Vue 3 + Inertia v3 · Fortify · PostgreSQL · fila/sessão/cache em `database`

**Produção de referência:** `https://portaldocandidatoproensp.cloud` · VPS `2.25.167.220` · usuário `deploy`

---

## Status — marque conforme avançar

- [x] **Fase 1** — VPS preparada (Ubuntu, Docker, usuário `deploy`, firewall, clone do Git)
- [x] **Fase 2** — Docker de produção + `.env` + build na VPS
- [x] **Fase 3** — Domínio, HTTPS (Caddy), testes, backup do Postgres
- [x] **Fase 4** — Deploy automático (GitHub Actions + `deploy.sh`)

---

## Visão da arquitetura (produção)

```text
Internet (HTTPS :443)
    ↓
Caddy (host Ubuntu — Let's Encrypt automático)
    ↓
127.0.0.1:8080
    ↓
container web (Nginx → volume public_data)
    ↓
container app (PHP 8.3-FPM — Laravel)
    ↓
container postgres (só rede interna Docker)

Paralelo:
  container queue     → php artisan queue:work
  container scheduler → php artisan schedule:work
```

| Caminho na VPS | Conteúdo |
|----------------|----------|
| `/opt/portalcandidato/app` | Clone Git (código) |
| `/opt/portalcandidato/app/.env` | Config produção (**nunca** no Git) |
| `/opt/portalcandidato/deploy.sh` | Script de deploy (copiado de `deploy/vps-deploy.sh`) |
| `/opt/portalcandidato/backup-db.sh` | Backup Postgres |
| `/opt/portalcandidato/backups/` | Arquivos `.sql.gz` |

**Importante:** em produção o frontend é **compilado** (`npm run build` dentro do Dockerfile). Não rode `npm run dev` na VPS.

---

## Regras de ouro

| Faça | Não faça |
|------|----------|
| `git pull` + `docker compose` na VPS | `composer install` / `composer update` na VPS |
| Editar `.env` só na VPS | Commitar `.env` no Git |
| Commit/push no **PC** | Commit/push na VPS (exceto emergência) |
| Sempre `--env-file .env` no compose | Esquecer `--env-file` (quebra `DB_PASSWORD`) |
| Chave SSH pública em `/home/deploy/.ssh/` | Colocar chave em `/root/.ssh/` |
| Chave **privada** só no PC + GitHub Secret | Colocar chave privada na VPS |

---

## Alias recomendado (VPS)

```bash
echo "alias dc='docker compose --env-file .env -f docker/compose.prod.yml'" >> ~/.bashrc
source ~/.bashrc
```

Uso: `dc ps`, `dc logs -f app`, `dc exec app php artisan migrate:status`

---

## Fase 1 — VPS preparada

Confira na VPS antes da Fase 2:

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

Se existir **Nginx do host** na porta 80, pare antes do Docker:

```bash
sudo systemctl stop nginx
sudo systemctl disable nginx
```

---

## Fase 2 — Docker de produção

### 2.1 Arquivos no repositório

```text
portalcandidato/
├── .dockerignore
├── deploy/
│   └── vps-deploy.sh          ← script de deploy (Fase 4)
├── docker/
│   ├── Dockerfile             ← build PHP+Node (Wayfinder)
│   ├── compose.prod.yml
│   ├── docker-entrypoint.sh
│   ├── php-fpm/99-app-env.conf
│   └── nginx/default.conf
├── .github/workflows/deploy.yml
└── docs/DEPLOY-VPS.md
```

Os arquivos completos estão no Git — **não copie versões antigas deste doc**. Use sempre `git pull`.

### 2.2 Destaques do `Dockerfile`

- **Estágio builder:** PHP 8.3-cli + Node 22 + Composer → `wayfinder:generate` + `npm run build`
- **Estágio runtime:** PHP 8.3-fpm + extensões PostgreSQL
- **Não** use estágio Node separado sem PHP (Wayfinder falha com `php: not found`)

### 2.3 Destaques do `compose.prod.yml`

- **`--env-file .env` obrigatório** em todos os comandos (compose lê `.env` da pasta `docker/` por padrão)
- **`.env` montado** em `app`, `queue` e `scheduler`: `../.env:/var/www/html/.env`
- **Volume `public_data`** compartilhado entre `app` e `web` (assets Vite)
- **Porta web:** `127.0.0.1:8080:80` (Caddy usa 80/443 no host — Fase 3)

### 2.4 Destaques do `nginx/default.conf`

- `resolver 127.0.0.11` + `$fastcgi_backend` — evita `host not found in upstream "app"`
- `fastcgi_buffer_size 32k` — evita **502** com cookies/sessão Fortify

### 2.5 Na VPS — `.env` de produção

```bash
cd /opt/portalcandidato/app
cp .env.example .env
nano .env
chmod 644 .env   # www-data precisa ler (volume montado no container)
```

**Valores mínimos (Fase 2 — teste no IP):**

```env
APP_NAME="Portal Candidato ProEns"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://IP-DA-VPS

APP_KEY=
# Gerar na etapa 2.7 — use ASPAS se tiver + / = na key

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=portalcandidato
DB_USERNAME=portal_app
DB_PASSWORD="COLOQUE_SENHA_FORTE_AQUI"

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_ENCRYPT=true
LOG_LEVEL=warning
```

| Variável | Local (dev) | VPS (Docker) |
|----------|-------------|--------------|
| `APP_DEBUG` | `true` | **`false`** |
| `DB_HOST` | `127.0.0.1` | **`postgres`** |
| `APP_URL` | `http://localhost` | `https://domínio` (Fase 3) |

### 2.6 Build e subir containers

```bash
cd /opt/portalcandidato/app

docker compose --env-file .env -f docker/compose.prod.yml build --no-cache
docker compose --env-file .env -f docker/compose.prod.yml up -d
docker compose --env-file .env -f docker/compose.prod.yml ps
```

Todos os serviços devem aparecer como `Up` (postgres `healthy`).

### 2.7 Comandos Laravel (primeira vez)

```bash
cd /opt/portalcandidato/app

# APP_KEY — só funciona com .env montado no container (compose.prod.yml)
docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan key:generate --force

docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan migrate --force
docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan storage:link
docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan config:cache
docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan route:cache
docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan view:cache
```

Confirme a key:

```bash
docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan config:show app.key
```

### 2.8 Teste (Fase 2)

- Abra `http://IP-DA-VPS` no navegador (antes do Caddy, se web ainda em `80:80`)
- Se **502**: `dc logs app --tail=50` e `dc logs web --tail=30`
- Se **500**: verifique `APP_KEY` com aspas e `config:clear` + `config:cache`
- Se **sem CSS/JS**: `dc exec app ls -la public/build/manifest.json`

### 2.9 O que NÃO fazer

- Não rodar `composer install` / `composer update` **na VPS** (altera `composer.lock` e quebra o build)
- Não usar `npm run dev` em produção
- Não `chmod 777` em `storage`
- Não expor Postgres na porta 5432
- Não commitar `.env`

Se rodou `composer` na VPS por engano:

```bash
git checkout -- composer.lock
rm -rf vendor
git pull
```

---

## Fase 3 — HTTPS, testes e backup

### 3.1 DNS

Registro **A**: `portaldocandidatoproensp.cloud` → IP da VPS (ex.: `2.25.167.220`).

```bash
dig +short portaldocandidatoproensp.cloud
```

### 3.2 Docker na porta 8080 (liberar 80/443 para Caddy)

No `docker/compose.prod.yml`, serviço `web`:

```yaml
ports:
  - '127.0.0.1:8080:80'
```

```bash
cd /opt/portalcandidato/app
docker compose --env-file .env -f docker/compose.prod.yml up -d --force-recreate web
curl -I http://127.0.0.1:8080/login
```

### 3.3 Caddy (HTTPS automático)

```bash
sudo apt update
sudo apt install -y caddy
sudo nano /etc/caddy/Caddyfile
```

```caddy
portaldocandidatoproensp.cloud {
    reverse_proxy 127.0.0.1:8080
}
```

```bash
sudo systemctl enable caddy
sudo systemctl restart caddy
sudo systemctl status caddy
```

### 3.4 `.env` após HTTPS

```env
APP_URL=https://portaldocandidatoproensp.cloud
ASSET_URL=https://portaldocandidatoproensp.cloud
SESSION_SECURE_COOKIE=true
APP_KEY="base64:...sua-chave-com-aspas..."
```

```bash
cd /opt/portalcandidato/app
docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan config:clear
docker compose --env-file .env -f docker/compose.prod.yml exec app php artisan config:cache
docker compose --env-file .env -f docker/compose.prod.yml restart app
```

**Página em branco no HTTPS?** Quase sempre assets em `http://` (mixed content). Confira:

```bash
curl -s https://portaldocandidatoproensp.cloud/login | grep -o 'href="http[^"]*build[^"]*"' | head -3
```

Não deve retornar URLs `http://`. Se retornar, ajuste `ASSET_URL` e `APP_URL` com `https://`.

O código também inclui `trustProxies` e `URL::forceScheme('https')` em `bootstrap/app.php` e `AppServiceProvider.php`.

### 3.5 Checklist de testes

- [ ] `https://portaldocandidatoproensp.cloud` abre (cadeado verde)
- [ ] Login / logout (Fortify)
- [ ] Fluxo principal do candidato
- [ ] E-mail (se SMTP configurado)
- [ ] Fila processando (`queue` container ativo)
- [ ] `APP_DEBUG=false` — sem stack trace público
- [ ] HTTPS e cookies de sessão ok (refresh mantém login)

### 3.6 Backup PostgreSQL

Crie `/opt/portalcandidato/backup-db.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail
BACKUP_DIR=/opt/portalcandidato/backups
mkdir -p "$BACKUP_DIR"
FILE="$BACKUP_DIR/portalcandidato-$(date +%Y%m%d-%H%M%S).sql.gz"

docker compose --env-file /opt/portalcandidato/app/.env \
  -f /opt/portalcandidato/app/docker/compose.prod.yml \
  exec -T postgres pg_dump -U "${DB_USERNAME:-portal_app}" "${DB_DATABASE:-portalcandidato}" | gzip > "$FILE"

find "$BACKUP_DIR" -name '*.sql.gz' -mtime +14 -delete
echo "Backup: $FILE"
```

Ajuste `DB_USERNAME` / `DB_DATABASE` se forem diferentes no `.env`.

```bash
chmod +x /opt/portalcandidato/backup-db.sh
/opt/portalcandidato/backup-db.sh
ls -lh /opt/portalcandidato/backups/
```

Cron (usuário `deploy`):

```bash
crontab -e
```

Adicione **dentro do editor** (não no terminal):

```cron
0 3 * * * /opt/portalcandidato/backup-db.sh >> /opt/portalcandidato/backups/backup.log 2>&1
```

Confirme: `crontab -l`

Copie backups para **fora** da VPS (nuvem ou outro servidor).

---

## Fase 4 — Deploy automático via Git

### 4.1 Chave SSH (no PC)

```bash
ssh-keygen -t ed25519 -f ~/deploy_portalcandidato -N ""
```

| Arquivo | Onde vai |
|---------|----------|
| `deploy_portalcandidato.pub` | VPS: `/home/deploy/.ssh/authorized_keys` |
| `deploy_portalcandidato` (privada) | GitHub Secret `DEPLOY_SSH_KEY` — **nunca** na VPS |

**Na VPS (como root ou deploy):**

```bash
sudo mkdir -p /home/deploy/.ssh
sudo nano /home/deploy/.ssh/authorized_keys   # cole a linha pública
sudo chown -R deploy:deploy /home/deploy/.ssh
sudo chmod 700 /home/deploy/.ssh
sudo chmod 600 /home/deploy/.ssh/authorized_keys
```

**Teste no PC:**

```bash
ssh -i ~/deploy_portalcandidato deploy@2.25.167.220
```

Deve entrar **sem senha** como `deploy@srv...`.

### 4.2 Script na VPS

Após `git pull` no repositório:

```bash
cd /opt/portalcandidato/app
cp deploy/vps-deploy.sh /opt/portalcandidato/deploy.sh
chmod +x /opt/portalcandidato/deploy.sh
```

Teste manual: `/opt/portalcandidato/deploy.sh`

O script oficial está em `deploy/vps-deploy.sh` no Git (usa `--env-file .env`, rebuild de app/queue/scheduler).

### 4.3 Secrets no GitHub

Repositório → **Settings → Secrets and variables → Actions**:

| Secret | Valor |
|--------|--------|
| `DEPLOY_HOST` | `portaldocandidatoproensp.cloud` ou IP |
| `DEPLOY_USER` | `deploy` |
| `DEPLOY_SSH_KEY` | conteúdo completo da chave **privada** |

### 4.4 Workflow

Arquivo: `.github/workflows/deploy.yml` (já no repositório).

Fluxo: `git push` em `main` → GitHub Actions → SSH → `/opt/portalcandidato/deploy.sh`

Acompanhe: **GitHub → Actions → deploy**

---

## Solução de problemas

| Sintoma | Causa provável | Solução |
|---------|----------------|---------|
| `DB_PASSWORD is missing` | Compose sem `--env-file .env` | Use `docker compose --env-file .env -f docker/compose.prod.yml ...` |
| `host not found in upstream "app"` | Nginx resolve `app` na subida | `resolver 127.0.0.11` + `$fastcgi_backend` no nginx |
| **502** após login | Headers/cookies grandes | Buffers `fastcgi_*` no nginx |
| **500** `No application encryption key` | `.env` não montado ou key vazia | Monte `.env` no compose; `key:generate`; aspas na key |
| **502** com key ok | `.env` chmod 600, www-data não lê | `chmod 644 .env` |
| Página **branca** no HTTPS | Assets em `http://` | `ASSET_URL` + `APP_URL` https; `config:cache` |
| Porta 80 em uso | Nginx do host | `systemctl stop nginx` |
| `composer.lock` Symfony 8 / PHP 8.4 | `composer update` na VPS | `git checkout -- composer.lock` |
| `git pull` abortado | Edits manuais na VPS | `git checkout -- .` + `git pull` (`.env` não é afetado) |
| Build Wayfinder falha | Node sem PHP | Use `Dockerfile` atual (builder com PHP+Node) |
| **Foto do candidato não aparece** | `storage:link` não executado ou Nginx sem volume `app_storage` | Veja seção abaixo |

### Foto do candidato não aparece

As fotos ficam em `storage/app/public/candidate-photos/` e são servidas via `/storage/...`. Em produção isso exige:

1. **`php artisan storage:link`** — cria o symlink `public/storage` → `storage/app/public` (não confundir com `key:generate`).
2. **Volume `app_storage` montado no container `web`** — o Nginx precisa ler os arquivos reais, não só o symlink em `public`.

**Correção imediata na VPS:**

```bash
cd /opt/portalcandidato/app
C="docker compose --env-file .env -f docker/compose.prod.yml"

git pull   # traz compose com app_storage no web + entrypoint atualizado

$C exec app php artisan storage:link --force --no-interaction

# Recria o web com o volume de storage (obrigatório após atualizar compose.prod.yml)
$C up -d --force-recreate web
```

**Verificar:**

```bash
# Symlink existe dentro do volume public
$C exec app ls -la public/storage

# Foto salva no cadastro (substitua USER_ID pelo id do usuário)
$C exec app ls -la storage/app/public/candidate-photos/

# URL deve retornar 200 (teste no navegador ou curl)
curl -I "https://portaldocandidatoproensp.cloud/storage/candidate-photos/USER_ID/arquivo.jpg"
```

Se a pasta `candidate-photos` estiver vazia, a foto não foi gravada no cadastro — peça ao candidato reenviar em **Configurações → Perfil**.


## Comandos úteis (cola rápida)

```bash
cd /opt/portalcandidato/app
C="docker compose --env-file .env -f docker/compose.prod.yml"

$C ps
$C logs -f app
$C logs -f queue
$C logs -f web
$C exec app php artisan migrate:status
$C exec app php artisan config:show app.url
$C exec app php artisan down
$C exec app php artisan up
$C restart queue
$C up -d --force-recreate web
```

---

## Fluxo do dia a dia

```text
PC: editar código → commit → push origin main
         ↓
GitHub Actions → /opt/portalcandidato/deploy.sh
         ↓
https://portaldocandidatoproensp.cloud atualizado

Alterar .env / SMTP → só na VPS → config:cache → restart app
```

---

## Melhorias opcionais (pós-deploy)

- Copiar backups `.sql.gz` para fora da VPS
- Deploy só após CI verde (ligar workflow `deploy` ao `tests`)
- Monitoramento de uptime (UptimeRobot, etc.)
- Revogar tokens/chaves expostos acidentalmente
- Desativar painéis não usados (ex.: CloudPanel na `:8443`) se não forem necessários

---

## Referências

- [Laravel — Deployment](https://laravel.com/docs/deployment)
- [Docker Compose](https://docs.docker.com/compose/)
- [Caddy — reverse_proxy](https://caddyserver.com/docs/caddyfile/directives/reverse_proxy)
- Versões do projeto: `AGENTS.md` e `composer.json` (PHP 8.3, Node 22)

---

*Documento atualizado após deploy completo em produção (jun/2026). Todas as fases concluídas.*
