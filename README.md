## Instalacja

Sklonuj repozytorium lokalnie:

```sh
  git clone https://github.com/pszemokoziniak/nikard_zadanie
  cd nikard_zadanie
```

Zainstaluj zależności PHP:

```sh
  composer install
```

Zainstaluj zależności NPM:

```sh
  npm ci
```


## Konfiguracja:

- Skopiuj plik środowiskowy:

```sh
  cp .env.example .env
```

- Wygeneruj klucz aplikacji:

```sh
  php artisan key:generate
```

## Baza danych:

###### Utwórz bazę SQLite. Możesz też użyć innej bazy (MySQL, Postgres) – po prostu zaktualizuj konfigurację zgodnie z potrzebami.

Uruchom migracje i seed bazy danych:


- Baza znajduje się w pliku: database/database.sqlite. SQLite tworzy ten plik w locie, gdy uruchamiasz migracje, gdy plik nie utworzy się użyj:

```sh
  touch database/database.sqlite
```

```sh
  php artisan migrate --seed
```

Chcesz przejść na MySQL/PostgreSQL?

- Ustaw w .env odpowiednie DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- Utwórz fizycznie bazę w DB (np. CREATE DATABASE ...)
- Popraw plik .env
- Wyczyść cache konfiguracji: 

```sh 
  php artisan config:clear 
```
- Uruchom migracje:
```sh 
  php artisan migrate:fresh --seed
```

Zbuduj zasoby i uruchom tryb deweloperski:

```sh
  npm run dev
```

Uruchom serwer deweloperski:

```sh
  php artisan serve
```

- **Username:** johndoe@example.com
- **Password:** secret

## Testy jednostkowe

Aby uruchomić testy:

```
php artisan test
```
