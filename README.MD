# Traçabilitat

Aplicació web per registrar i seguir mostres de laboratori d'anatomia patològica, amb circuits de **citologia** i **biòpsia**.

> **Estat:** projecte en desenvolupament. Només s'han d'utilitzar dades fictícies fins que s'implementin i es validin els controls de seguretat i traçabilitat necessaris.

## Tecnologies

| Component | Tecnologia |
| --- | --- |
| Backend | Laravel 13 (PHP 8.4 en l'entorn local actual) |
| Interfície | Livewire 4, Blade, Flux UI i Tailwind CSS |
| Autenticació | Autenticació integrada del starter kit Laravel |
| Base de dades local | SQLite |
| Gestió de dependències | Composer i npm |
| Control de versions | Git; GitHub previst |

El projecte s'ha creat amb el starter kit **Livewire**, amb components PHP i vistes Blade en fitxers separats.

## Requisits

- PHP 8.3 o superior i les extensions requerides per Laravel, incloses `fileinfo` i `pdo_sqlite`.
- Composer 2.
- Node.js i npm.
- Git (opcional per executar en local).


## Execusió en local (Windows)

```cmd
php artisan migrate
npm run build
```

Per iniciar el servidor:

```cmd
php artisan serve
```

Obre **http://127.0.0.1:8000**. Durant el desenvolupament de les vistes, pots obrir una segona terminal i executar `npm run dev`.

## Usuaris de prova

El seeder `database/seeders/UsuarisProvaSeeder.php` crea o actualitza dos comptes de prova:

| Nom | Correu electrònic | Contrasenya |
| --- | --- | --- |
| Administrador Prova | `admin@tracabilitat.test` | `password` |
| Tècnic Prova | `tecnic@tracabilitat.test` | `password` |