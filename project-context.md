# Project Context — Boda (Laravel + Inertia.js)

> **Auto-generated:** 2026-07-12  
> **Purpose:** Read-only snapshot of the current project state. Do not modify project files based on this document alone.

---

## 1. Framework & Frontend Stack

| Component | Version / Detail |
|---|---|
| **PHP** | `^8.2` |
| **Laravel** | `^12.0` (`laravel/framework`) |
| **Inertia.js (server)** | `^2.0` (`inertiajs/inertia-laravel`) |
| **Frontend adapter** | **Vue 3** (`@inertiajs/vue3` `^2.0`) |
| **Vue** | `^3.3.13` |
| **Ziggy** (route helper) | `^2.0` (`tightenco/ziggy`) |

---

## 2. Authentication & Admin Area

### Authentication setup
- **Jetstream `^5.5`** with the **Inertia** stack (`config/jetstream.php` → `'stack' => 'inertia'`).
- Under the hood, Jetstream brings **Fortify** for auth backend and **Sanctum** (`^4.0`) as the API guard.
- The `config/jetstream.php` guard is set to `'sanctum'`.

### Enabled Jetstream features
```php
// config/jetstream.php
'features' => [
    // Features::termsAndPrivacyPolicy(),
    // Features::profilePhotos(),
    // Features::api(),
    // Features::teams(['invitations' => true]),
    Features::accountDeletion(),
],
```
- **Account deletion**: ✅ enabled
- **Profile photos**: ❌ disabled (commented out)
- **API tokens**: ❌ disabled (commented out)
- **Teams**: ❌ disabled (commented out)
- **Terms & Privacy Policy**: ❌ disabled (commented out), though the pages exist as static Vue components

### Two-Factor Authentication
- The `users` table has `two_factor_secret`, `two_factor_recovery_codes`, and `two_factor_confirmed_at` columns (migration `2026_07_12_190738`).
- The `User` model uses `Laravel\Fortify\TwoFactorAuthenticatable`.
- 2FA challenge/login pages exist in `resources/js/Pages/Auth/`.

### Passkeys (WebAuthn)
- A `passkeys` table exists (migration `2026_07_12_190739`) powered by `Laravel\Passkeys\Passkeys`.

### Dashboard / Admin area
- A `/dashboard` route exists, protected by `auth:sanctum` + `verified` middleware.
- Renders `resources/js/Pages/Dashboard.vue`.
- **No separate admin panel or role-based admin area exists yet.**

---

## 3. Directory Listings

### `app/Http/Controllers`

```
Controller.php
```

Only the base abstract `Controller` class — no custom controllers have been created yet.

### `app/Models`

```
User.php
```

The `User` model includes:
- `HasApiTokens` (Sanctum)
- `HasFactory`
- `HasProfilePhoto` (Jetstream)
- `Notifiable`
- `TwoFactorAuthenticatable` (Fortify)

Fillable: `name`, `email`, `password`.  
Casts: `email_verified_at` → `datetime`, `password` → `hashed`.

### `database/migrations`

| Migration | Tables Created |
|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches` |
| `2026_07_12_190738_add_two_factor_columns_to_users_table` | Adds 2FA columns to `users` |
| `2026_07_12_190739_create_passkeys_table` | `passkeys` |
| `2026_07_12_190841_create_personal_access_tokens_table` | `personal_access_tokens` |

---

## 4. Routes

### `routes/web.php`

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
```

### `routes/admin.php`
**Does not exist.**

---

## 5. Database Configuration

| Setting | Value |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `boda` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | *(empty)* |

Other session/queue/cache stores:
- `SESSION_DRIVER` = `database`
- `CACHE_STORE` = `database`
- `QUEUE_CONNECTION` = `database`

---

## 6. File Storage

### Disks defined in `config/filesystems.php`

| Disk | Driver | Root |
|---|---|---|
| `local` | local | `storage/app/private` |
| `public` | local | `storage/app/public` |
| `s3` | s3 | *(env-configured)* |

Default disk: `local` (`FILESYSTEM_DISK=local`).

### Symbolic link (`storage:link`)
- **Not created.** `public/storage` does not exist.
- The link target is configured as: `public/storage` → `storage/app/public`.

### Media / image handling packages
- **None installed.** No `spatie/laravel-medialibrary`, `intervention/image`, or similar package is present in `composer.json`.

---

## 7. Installed Packages (composer.json)

### Production dependencies (`require`)

| Package | Version |
|---|---|
| `php` | `^8.2` |
| `inertiajs/inertia-laravel` | `^2.0` |
| `laravel/framework` | `^12.0` |
| `laravel/jetstream` | `^5.5` |
| `laravel/sanctum` | `^4.0` |
| `laravel/tinker` | `^2.10.1` |
| `tightenco/ziggy` | `^2.0` |

### Dev dependencies (`require-dev`)

| Package | Version |
|---|---|
| `fakerphp/faker` | `^1.23` |
| `laravel/pail` | `^1.2.2` |
| `laravel/pint` | `^1.24` |
| `laravel/sail` | `^1.41` |
| `mockery/mockery` | `^1.6` |
| `nunomaduro/collision` | `^8.6` |
| `phpunit/phpunit` | `^11.5.50` |

### Notable absences
- ❌ No CSV/Excel import package (`maatwebsite/excel`, `openspout/openspout`, etc.)
- ❌ No roles/permissions package (`spatie/laravel-permission`, etc.)
- ❌ No file upload / media library package (`spatie/laravel-medialibrary`, `intervention/image`, etc.)

---

## 8. Frontend Build Tooling

### Package manager
**npm** (no `yarn.lock`, `pnpm-lock.yaml`, or `bun.lockb` present).

### `package.json` scripts
```json
{
  "build": "vite build",
  "dev": "vite"
}
```

### Dev dependencies (frontend)

| Package | Version |
|---|---|
| `@inertiajs/vue3` | `^2.0` |
| `@tailwindcss/forms` | `^0.5.7` |
| `@tailwindcss/typography` | `^0.5.10` |
| `@tailwindcss/vite` | `^4.0.0` |
| `@vitejs/plugin-vue` | `^6.0.4` |
| `autoprefixer` | `^10.4.16` |
| `axios` | `^1.11.0` |
| `concurrently` | `^9.0.1` |
| `laravel-vite-plugin` | `^2.0.0` |
| `postcss` | `^8.4.32` |
| `tailwindcss` | `^3.4.0` |
| `vite` | `^7.0.7` |
| `vue` | `^3.3.13` |

### Vite config (`vite.config.js`)
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
```

- Entry point: `resources/js/app.js`
- Vite dev server is currently running (confirmed by `public/hot` containing `http://[::1]:5173`).

### CSS
- **Tailwind CSS `^3.4.0`** with `@tailwindcss/forms` and `@tailwindcss/typography` plugins.
- Font: **Figtree** (configured as the default sans-serif family in `tailwind.config.js`).
- PostCSS with `tailwindcss` + `autoprefixer` plugins.

---

## 9. Frontend Page Structure

### `resources/js/Pages/`

```
API/
  Index.vue
  Partials/
Auth/
  ConfirmPassword.vue
  ForgotPassword.vue
  Login.vue
  Register.vue
  ResetPassword.vue
  TwoFactorChallenge.vue
  VerifyEmail.vue
Profile/
  Partials/
  Show.vue
Dashboard.vue
PrivacyPolicy.vue
TermsOfService.vue
Welcome.vue
```

### `resources/js/Layouts/`

```
AppLayout.vue
```

### `resources/js/Components/` (26 Jetstream components)

Includes standard Jetstream UI components: `ActionMessage`, `ActionSection`, `ApplicationLogo`, `AuthenticationCard`, `Banner`, `Checkbox`, `ConfirmationModal`, `ConfirmsPassword`, `DangerButton`, `DialogModal`, `Dropdown`, `DropdownLink`, `FormSection`, `InputError`, `InputLabel`, `Modal`, `NavLink`, `PrimaryButton`, `ResponsiveNavLink`, `SecondaryButton`, `SectionBorder`, `SectionTitle`, `TextInput`, `Welcome`, etc.

---

## 10. Quick Summary

This is a **freshly scaffolded Laravel 12 + Jetstream 5.5 + Inertia 2.0 + Vue 3** project with:
- ✅ Authentication fully set up (login, register, password reset, 2FA, passkeys)
- ✅ Dashboard page behind auth
- ✅ MySQL database configured and migrated
- ✅ Vite dev server running
- ❌ No custom models, controllers, or business logic yet
- ❌ No admin/user roles or permissions
- ❌ No file/image upload packages
- ❌ No CSV/Excel import packages
