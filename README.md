# Parcely okresu Jičín

Webová aplikace, která zobrazuje katastrální parcely okresu Jičín na mapě. Po kliknutí na parcelu se zobrazí její detail (číslo, katastrální území, výměra, druh pozemku, datum poslední změny).

Při oddálení (pod zoom 14) se místo parcel zobrazí hranice a názvy katastrálních území – z takové dálky by parcela měla jen pár pixelů a nedalo by se na ni kliknout. Kliknutím na k.ú. se mapa přiblíží na něj a zobrazí jeho parcely.

- **Data:** ČÚZK – INSPIRE katastrální parcely (CPX, GML) pro jednotlivá katastrální území, seznam k.ú. z RÚIAN.
- **Backend:** PHP 8 bez frameworku, PostgreSQL + PostGIS. Parcely posílá jako vektorové dlaždice (MVT), detail jako JSON.
- **Frontend:** React (Create React App), MapLibre GL přes react-map-gl, axios.

Rozhodnutí, překvapení a co bych dělal jinak: [ZAPISNIK.md](ZAPISNIK.md).

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
| `GET /api/cadastral-units` | hranice naimportovaných k.ú. jako GeoJSON (polygon + bod pro popisek) |

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

Stažené ZIPy se ukládají do `server/data/` a při dalším importu se použijí znovu. Přepínač `--force` je stáhne znovu. Import je možné spouštět opakovaně – parcely daného k.ú. se vždy nahradí. Spolu s parcelami se ukládá i oficiální hranice k.ú. (`CadastralZoning` ze stejného GML).

Databázi založenou před přidáním hranic k.ú. stačí aktualizovat: znovu spustit `server/sql/schema.sql` (doplní sloupec) a import (použije už stažené ZIPy).

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

## Zápisník

### Rozsah

Naimportovaný je celý okres Jičín: 240 k.ú. a 272 001 parcel. Import trval 3,5 minuty, ZIPy z ČÚZK mají 153 MB a parcely zabírají v databázi 109 MB. Import umí i jedno k.ú. nebo jednu obec, viz Spuštění.

### Hlavní rozhodnutí

- **Data předem stažená, ne živě z WFS.** WFS má limity na počet prvků a pro celý okres by byl pomalý. Katastr se mění pomalu, takže stačí jednorázový import do PostGIS.
- **Vektorové dlaždice (MVT).** Prohlížeč stáhne jen to, co je zrovna vidět, ne všechny parcely najednou.
- **Parcely až od zoomu 14, pod ním hranice k.ú.** Z větší dálky se na parcelu nedá kliknout. Kliknutím na k.ú. se mapa přiblíží na jeho parcely.
- **Čisté PHP bez frameworku.** Pár endpointů nepotřebuje framework. Kód je rozdělený na Controller → Service → Dao.
- **Frontend:** React a MapLibre GL, podklad OpenStreetMap.

### Co mě překvapilo

- **Jak je katastr členěný.** S katastrem jsem nikdy nepracoval. Nevěděl jsem, že mezi obcí a parcelou je ještě katastrální území, že obec jich může mít víc (Jičín má 5) a že data ČÚZK jsou rozdělená právě po k.ú.
- **Souřadnicové systémy.** Jedno místo jde zapsat různými čísly podle systému: český katastr (S-JTSK), GPS (WGS84) a webové mapy (Web Mercator). Porovnávat jde jen souřadnice ve stejném systému, proto jsou v SQL dotazech převody.
- **Parcely na mapě nejde stylovat přes CSS.** MapLibre je kreslí přes WebGL do jednoho canvasu, takže vzhled se nastavuje jeho vlastními výrazy v JavaScriptu.

### Co bych s víc časem řešil jinak

- **Cache dlaždic** (soubory na disku nebo tabulka v DB), mazaná při importu.
- **Testy**
- **Vyhledávání** parcely podle čísla a katastrálního území. Popřípadě i nějaké filtry.

### Čas a nástroje

Přibližně 9 hodin. Nejvíc času zabralo zorientovat se v datech ČÚZK.

Kód za mě převážně psal Claude Code. Já jsem rozhodoval o architektuře a o tom, co aplikace bude dělat. Výstupy jsem pak kontroloval a popřípadě trochu upravoval.
