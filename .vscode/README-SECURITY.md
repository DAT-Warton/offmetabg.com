# 🔒 VS Code Security Settings - OffMetaBG

Тази папка съдържа конфигурация на VS Code която **БЛОКИРА** leak на credentials.

## Защо е необходимо?

AI асистенти (включително аз) имаме тенденция да:
- ❌ Пишем hardcoded API keys в код
- ❌ Създаваме diagnostic scripts с реални credentials
- ❌ Commit-ваме .env файлове по погрешка
- ❌ Споделяме sensitive данни в примери

## Какво прави тази конфигурация?

### 1. `settings.json`
- ⚠️ Показва **ЖЪЛТИ HIGHLIGHTS** при откриване на:
  - `API_KEY`
  - `PASSWORD`
  - `SECRET`
  - `CREDENTIAL`
  
- 🔒 Прави `do not upload to github/` **READ-ONLY**
- 🚫 Изключва от Search/Watch чувствителни папки
- 📝 Съдържа списък с **ЗАБРАНЕНИ CREDENTIALS** (за reference)

### 2. `extensions.json`
- ✅ Препоръчва security extensions:
  - GitLens (виждаш credentials в Git history)
  - TODO Highlight (показва API_KEY в код)
  - Code Spell Checker (улавя typos в config)

### 3. File Nesting
Скрива backup файлове:
```
.env
├── .env.REAL (hidden)
├── .env.example (visible)
└── .env.local (hidden)
```

## 🚨 ПРАВИЛА ЗА AI АСИСТЕНТИ

### НИКОГА не пиши тези неща директно в код:

#### Cloudflare
```
❌ CLOUDFLARE_API_TOKEN=AjBupKPG-cLElKbURWo1XpKfl6jywu_s6FD2zItN
❌ CLOUDFLARE_API_KEY=2d1332825a952148afba3ad2f378fff5cb0e4
❌ CLOUDFLARE_ZONE_ID=726f6033454c792cbe0ec3de8524e462
```

#### Database
```
❌ DB_PASSWORD=Wuna9988!@#$
❌ DB_NAME=offmetabg_db
```

#### Email
```
❌ datwarton@gmail.com (в код или scripts)
```

### ✅ ВМЕСТО ТОВА използвай:

```php
// ✅ ПРАВИЛНО
$apiKey = $_ENV['CLOUDFLARE_API_KEY'];
$apiKey = getenv('CLOUDFLARE_API_KEY');

// ❌ ГРЕШНО
$apiKey = "2d1332825a952148afba3ad2f378fff5cb0e4";
```

## 📁 Структура на чувствителни данни

```
offmetabg/
├── .env                              # ✅ В .gitignore, ЛОКАЛНО
├── .env.example                      # ✅ В Git, с placeholders
├── do not upload to github/          # ✅ В .gitignore, READ-ONLY
│   ├── .env.REAL                     # Backup
│   ├── database.json.REAL
│   ├── email-config.php.REAL
│   ├── DEPLOYMENT-GUIDE.md
│   └── backups/
└── config/
    ├── database.json                 # ✅ В .gitignore, ЛОКАЛНО
    ├── email-config.php              # ✅ В .gitignore, ЛОКАЛНО
    └── *.example.php                 # ✅ В Git, с placeholders
```

## 🔍 Как да намериш leaked credentials

### В код:
```bash
git grep -E "(726f6033454c792cbe0ec3de8524e462|2d1332825a952148afba3ad2f378fff5cb0e4)"
```

### В история:
```bash
git log -S "2d1332825a952148afba3ad2f378fff5cb0e4" --all
```

### В working directory:
```powershell
Get-ChildItem -Recurse | Select-String -Pattern "726f6033454c792cbe0ec3de8524e462"
```

## ✅ Checklist преди Git commit

- [ ] `.env` НЕ е staged (git status)
- [ ] `do not upload to github/` НЕ е staged
- [ ] Няма hardcoded API keys в кода
- [ ] Test файлове са изтрити или в .gitignore
- [ ] Deployment scripts са локално, не в Git

## 🚀 При deploy

1. **NE** commit-вай `.env`
2. Копирай от `do not upload to github/.env.REAL` към VPS
3. Провери permissions: `chmod 600 .env`
4. Validate config: `php -r "var_dump(getenv('CLOUDFLARE_API_KEY'));"`

---

**Създадено:** 19 февруари 2026  
**Последна актуализация:** След почистване на Git история
