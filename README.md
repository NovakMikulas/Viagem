# Parcely okresu Jičín

Webová aplikace, která zobrazuje katastrální parcely okresu Jičín na mapě. Po kliknutí na parcelu se zobrazí její detail (číslo, katastrální území, výměra, druh pozemku, datum poslední změny).

- **Data:** ČÚZK – INSPIRE katastrální parcely (CPX, GML) pro jednotlivá katastrální území, seznam k.ú. z RÚIAN.
- **Backend:** PHP 8 bez frameworku, PostgreSQL + PostGIS. Parcely posílá jako vektorové dlaždice (MVT), detail jako JSON.
- **Frontend:** React (Create React App), MapLibre GL přes react-map-gl, axios.

## Struktura

```
server/
  public/index.php   vstupní bod API
  scripts/import.php import parcel z ČÚZK do databáze
  sql/schema.sql     struktura databáze
  src/               Controller → Service → Dao, Http (Request, Response, Router), Import
client/
  src/               React aplikace (components, api/services, constants, styles)
```

## API

| Adresa | Popis |
|---|---|
| `GET /api/tiles/{z}/{x}/{y}.pbf` | vektorová dlaždice s parcelami (vrstva `parcels`), od zoomu 14 |
| `GET /api/parcels/{id}` | detail parcely jako JSON |

## Prerekvizity

- **PHP 8.1+** s rozšířeními `pdo_pgsql`, `xmlreader`, `dom`, `zip` a `openssl` (zapnout v `php.ini`)
- **Composer**
- **PostgreSQL** s rozšířením **PostGIS** (vyvíjeno na PostgreSQL 18 + PostGIS 3.6)
- **Node.js** a npm (vyvíjeno na Node 23)

## Spuštění

Příkazy jsou pro PowerShell a spouští se z kořenové složky projektu.

### 1. Databáze

Vytvoř databázi `viagem` a spusť v ní `server/sql/schema.sql` (třeba přes pgAdmin → Query Tool, nebo psql):

```powershell
psql -U postgres -c "CREATE DATABASE viagem"
psql -U postgres -d viagem -f server/sql/schema.sql
```

### 2. Backend

```powershell
cd server
composer install
copy .env.example .env
```

V `server/.env` doplň přístup k databázi.

### 3. Import parcel

Ve složce `server`:

```powershell
php scripts/import.php obec 572659
```

Typy importu:

| Příkaz | Co naimportuje |
|---|---|
| `php scripts/import.php ku 659541` | jedno katastrální území (k.ú. Jičín) |
| `php scripts/import.php obec 572659` | všechna k.ú. obce (Jičín = 5 k.ú., ~17 000 parcel) |
| `php scripts/import.php okres 3604` | všechna k.ú. okresu Jičín (240 k.ú.) |

Stažené ZIPy se ukládají do `server/data/` a při dalším importu se použijí znovu. Přepínač `--force` je stáhne znovu. Import je možné spouštět opakovaně – parcely daného k.ú. se vždy nahradí.

### 4. Spuštění backendu

Ve složce `server`:

```powershell
php -S localhost:8000 public/index.php
```

### 5. Spuštění frontendu

V druhém terminálu:

```powershell
cd client
npm install
copy .env.example .env
npm start
```

Aplikace běží na http://localhost:3000. V `client/.env` je adresa backendu (`REACT_APP_API_URL`). Bez souboru `.env` se použije `http://localhost:8000`.
