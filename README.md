# Portal Candidato

Plataforma web para gerenciamento de **processos seletivos**, com fluxos separados para:

- **Admin**: cria e configura processos, critérios, documentos e avaliadores.
- **Candidato**: encontra vagas/processos, envia inscrição e documentos.
- **Avaliador**: analisa documentos, pontua candidatos e apoia a classificação final.

O projeto foi construído com Laravel + Inertia + Vue, focando em uma experiencia moderna no frontend sem abrir mao da estrutura robusta do backend Laravel.

---

## Principais funcionalidades

- Autenticacao com Fortify (login, cadastro, verificacao de e-mail e 2FA).
- Controle de acesso por papel com `spatie/laravel-permission`:
  - `admin`
  - `avaliador`
  - `candidato`
- Cadastro e configuracao de processos seletivos.
- Fluxo de inscricao em etapas para candidatos.
- Upload e validacao de documentos.
- Avaliacao e atribuicao de notas por avaliadores.
- Base para relatorios e ranking dos processos.

---

## Tecnologias utilizadas

### Backend

- **PHP 8.3**
- **Laravel 13**
- **Inertia Laravel v3**
- **Laravel Fortify** (autenticacao)
- **Spatie Laravel Permission** (RBAC)
- **Laravel Wayfinder**
- **DomPDF** (geracao de PDF)

### Frontend

- **Vue 3**
- **Inertia.js v3**
- **TypeScript**
- **Vite**
- **Tailwind CSS v4**
- **PrimeVue + PrimeIcons**

### Qualidade e testes

- **Pest**
- **PHPUnit**
- **Laravel Pint**
- **ESLint**
- **Prettier**

---

## Requisitos

Antes de iniciar, garanta que voce tenha instalado:

- PHP 8.3+
- Composer
- Node.js 20+ e npm
- Banco de dados configurado no `.env` (MySQL, PostgreSQL ou SQLite)

---

## Instalacao local

```bash
# 1) Clonar o projeto
git clone https://github.com/rafaelcacote/portalcandidato.git
cd portalcandidato

# 2) Instalar dependencias PHP
composer install

# 3) Instalar dependencias JS
npm install

# 4) Configurar ambiente
cp .env.example .env
php artisan key:generate

# 5) Configurar banco (ajuste o .env) e rodar migracoes + seed
php artisan migrate --seed

# 6) Subir ambiente de desenvolvimento (Laravel + queue + logs + Vite)
composer run dev
```

Se preferir, em dois terminais:

```bash
php artisan serve
npm run dev
```

---

## Usuario inicial (seed)

Ao executar os seeders, um usuario admin e criado:

- **E-mail**: `admin@portalcandidato.local`
- **Senha**: `password`

> Recomendado alterar a senha apos o primeiro acesso.

---

## Comandos uteis

```bash
# Rodar testes
php artisan test --compact

# Verificar tipos no frontend
npm run types:check

# Lint frontend
npm run lint:check

# Formatar codigo PHP
vendor/bin/pint --dirty --format agent
```

---

## Estrutura de modulos

- `app/Modules/Admin`: fluxos administrativos
- `app/Modules/Candidate`: fluxos dos candidatos
- `app/Modules/Evaluator`: fluxos dos avaliadores
- `resources/js/pages`: paginas Inertia/Vue
- `database/migrations`: estrutura de banco
- `database/seeders`: dados iniciais e papeis

---

## Licenca

Este projeto segue a licenca **MIT**.
