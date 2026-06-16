# Atualizar produção na VPS

Guia rápido para publicar alterações após **commit em `develop`** e **merge em `main`**.

**Produção:** `https://portaldocandidatoproensp.cloud`  
**VPS:** `deploy@2.25.167.220`  
**Pasta do projeto:** `/opt/portalcandidato/app`

> Guia completo da infraestrutura (primeira instalação, HTTPS, backup): [`DEPLOY-VPS.md`](./DEPLOY-VPS.md)

---

## Importante — leia antes

`git pull` **sozinho não atualiza** o site em produção.

O Laravel e o Vue rodam **dentro das imagens Docker**. O fluxo correto é:

```text
git pull  →  docker build  →  recriar containers  →  copiar assets  →  artisan cache
```

Se você só der `git pull`, o código muda na pasta, mas os containers continuam com a versão antiga (como aconteceu em jun/2026).

---

## Parte 1 — No seu PC

### 1. Commit e push em `develop`

```bash
git checkout develop
git add .
git commit -m "Sua mensagem de commit"
git push origin develop
```

### 2. Merge em `main` e push

```bash
git checkout main
git pull origin main
git merge develop
git push origin main
```

### 3. (Opcional) Aguardar CI no GitHub

Em **GitHub → Actions**, confirme que os workflows `tests` e `lint` passaram em `main`.

---

## Parte 2 — Na VPS

### 1. Conectar na VPS

```bash
ssh deploy@2.25.167.220
```

### 2. (Recomendado) Backup do banco

```bash
/opt/portalcandidato/backup-db.sh
```

### 3. Definir alias do Docker (uma vez por sessão)

```bash
cd /opt/portalcandidato/app
C="docker compose --env-file .env -f docker/compose.prod.yml"
```

### 4. Atualizar código do GitHub

```bash
git fetch origin
git checkout main
git pull --ff-only origin main
git log -1 --oneline
```

O commit exibido deve ser o merge que você acabou de fazer em `main`.

### 5. Rebuild das imagens (obrigatório)

Pode levar **5 a 15 minutos**. Aguarde terminar.

```bash
$C build --no-cache app queue scheduler
```

### 6. Recriar containers com a imagem nova

```bash
$C up -d --force-recreate app queue scheduler web
```

### 7. Copiar assets (CSS/JS) para o volume do Nginx

Sem este passo o backend pode estar novo, mas a interface continua antiga.

```bash
$C exec -T app sh -c 'cp -a /opt/public-seed/. /var/www/html/public/ && chown -R www-data:www-data /var/www/html/public'
```

### 8. Comandos Laravel pós-deploy

```bash
$C exec -T app php artisan storage:link --force --no-interaction
$C exec -T app php artisan config:cache
$C exec -T app php artisan route:cache
$C exec -T app php artisan view:cache
```

---

## Parte 3 — Verificar se atualizou

```bash
cd /opt/portalcandidato/app
C="docker compose --env-file .env -f docker/compose.prod.yml"

# Containers recentes (não "7 days ago")
$C ps

# Backend novo dentro do container
$C exec app test -f /var/www/html/app/Modules/Candidate/Support/ResearchLineCatalog.php && echo "Backend OK" || echo "Backend FALHOU — refaça o build"

# Assets com data de hoje (não semanas atrás)
$C exec web ls -la /var/www/html/public/build/manifest.json
```

**No navegador** (use aba anônima ou Ctrl+Shift+R):

- [ ] `https://portaldocandidatoproensp.cloud` abre com CSS/JS
- [ ] Login funciona
- [ ] Funcionalidade nova está visível

---

## Atalho — script de deploy

Se o script já estiver instalado na VPS:

```bash
ssh deploy@2.25.167.220
/opt/portalcandidato/deploy.sh
```

**Instalar ou atualizar o script** (primeira vez ou após mudanças no repositório):

```bash
cd /opt/portalcandidato/app
cp deploy/vps-deploy.sh /opt/portalcandidato/deploy.sh
chmod +x /opt/portalcandidato/deploy.sh
```

O script faz o mesmo fluxo (build, migrate, up, copiar assets, cache). Se após o script os containers ainda parecerem antigos, use o passo manual com `--force-recreate` (seção acima).

---

## Diagnóstico rápido (quando “não atualizou”)

Rode na VPS:

```bash
cd /opt/portalcandidato/app
C="docker compose --env-file .env -f docker/compose.prod.yml"

echo "=== Commit na VPS ==="
git log -1 --oneline

echo "=== Código na pasta ==="
test -f app/Modules/Candidate/Support/ResearchLineCatalog.php && echo "SIM na pasta" || echo "NAO na pasta"

echo "=== Código no container ==="
$C exec app test -f /var/www/html/app/Modules/Candidate/Support/ResearchLineCatalog.php && echo "SIM no container" || echo "NAO no container — precisa rebuild"

echo "=== Data dos assets ==="
$C exec web ls -la /var/www/html/public/build/manifest.json

echo "=== Idade dos containers ==="
$C ps
```

| Sintoma | Causa | Solução |
|---------|-------|---------|
| SIM na pasta, NAO no container | Faltou `docker build` + `up` | Parte 2, passos 5 e 6 |
| SIM no container, tela antiga | Faltou copiar assets | Parte 2, passo 7 |
| Containers com "7 days ago" | Não recriou containers | `$C up -d --force-recreate app queue scheduler web` |
| `git pull` falhou | Edição manual na VPS | `git checkout -- .` e `git pull --ff-only origin main` |

---

## O que NÃO fazer na VPS

| Evitar | Motivo |
|--------|--------|
| Só `git pull` | Não atualiza containers Docker |
| `composer install` / `composer update` | Quebra o build; dependências vêm do Dockerfile |
| `npm run dev` | Só para desenvolvimento local |
| Commitar código na VPS | Fluxo: PC → GitHub → VPS |
| Alterar `.env` sem necessidade | Só quando mudar config (SMTP, URL, etc.) |

---

## Resumo em uma linha

```text
PC: develop → merge main → push
VPS: ssh deploy@... → build → up --force-recreate → copiar assets → artisan cache
```

---

*Última atualização: jun/2026 — fluxo validado em produção.*
