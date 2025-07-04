# 🐾 Pet Care App – Developer Onboarding Guide

Welcome to the project! Here's everything you need to get up and running.

---

# 📦 Project Setup

1. **Install dependencies**

    ```bash
    composer install
    npm install
    ```

# 🧩 Environment Variables
Create a .env file inside the includes folder:
    DB_HOST=localhost
    DB_USER=youruser
    DB_PASS=yourpass
    DB_BD=pet_care_app

# 🗄️ Database Management
We use a custom PHP-based migration and seeding system to manage the database.

## 🛠️ Run Database Migrations

    Create the DB manually (with utf8mb4_general_ci collation), then run:

    ## ✅ How to run migrations

    1. Make sure your `.env` is configured and DB is created
    2. Run:

```bash
    php database/migrate.php
```

    This will:

    * Create the migrations table (if it doesn’t exist)

    * Run all pending migrations in database/migrations/

    * Track executed files to avoid duplicates

## 🔁 Rollback Database Migrations
    Rolls back the latest executed migration:

```bash
    php database/rollback.php
```


## ✅ How to update the composer file
    Run:

```bash
    composer dump-autoload
```

## 🌱 Data Seeding
    To insert starter/default data:

```bash
    php database/seed.php
```

    Seeders are stored in database/seeders/, and are only run once (tracked in the seeds table).

    Example seeders:

    * Roles

    * Permissions

    * Role-Permission mapping

## 🛠️ Composer Autoload
    If you add a new class, run:

```bash
    composer dump-autoload
```
    This regenerates the autoloader to recognize new files/classes.


## 🔧 Migrations Structure
    All migration files are class-based and named with this pattern:
    20250407_00_create_roles_table.php

    The structure is:
        class Migration_20250407_00_create_roles_table {
            public function up() {
                DB::statement("CREATE TABLE roles (...);");
            }

            public function down() {
                DB::statement("DROP TABLE roles;");
            }
        }


Re-run all migrations with:

```bash
    php database/migrate.php
```
# 🎨 SCSS Design System
    We use a SASS-based design system with a fully customizable theme, utility generator, and design tokens.

    ### Tokens Directory (/scss/tokens):
    _colors.scss → Color palettes with :root + dark mode support (auto & manual)

    _fonts.scss → Font families, weights, sizes

    _spacing.scss → Global spacing tokens ($gap, $separate, etc.)

    _radius.scss → Border radius values

    ### Base (/scss/base):
    _reset.scss → Clean reset (incl. 62.5% font-size trick for rem)

    _globals.scss → Body, headings, global font styles

    _utilities.scss → Auto-generated spacing utilities:

        * m-1, mt-4, p-20, -mx-10 up to 250

        * Negative margins supported

        * Based on rem

    ### Mixins (/scss/mixins)
    media.scss → Mobile-first responsive helpers: @include tablet { ... }

    grid.scss → @include grid(3, 2rem)

    buttons.scss, cards.scss, etc.

## 🌗 Dark Mode
    Dark theme is supported using:

    @media (prefers-color-scheme: dark) for auto switch

    [data-theme="dark"] for manual override

    color($name, $state) SASS function auto-resolves the theme

    ### ✅ Example:

```css
    body {
        background-color: color(brand-8);
        color: color(brand-8, text);
    }
```

## 🧠 CSS Utilities
    Generated in base/_utilities.scss:

```html 
<div class="m-8 px-10 -mb-5">...</div>
```

    * Full margin & padding shorthands

    * 0rem to 250rem, steps 1rem

    * Can be purged if needed

## 🖋 Font Tokens
    Set in tokens/_fonts.scss

    Family, weight, size, and line-height variables available

    Use font-size: font(size, md);, font-weight: font(weight, bold);, etc.

## 📐 Spacing Utilities
    Auto-generated via loop from 0rem to 250rem in 1rem steps

    Margin & padding utilities (m-10, pt-5, -mb-20, etc.)

    Add spacing directly to class names in HTML

# 🧩 JS & Assets
(Coming soon)

# 🧰 Available Helpers
## 🔒 Auth & Session Helpers
### Located in: helpers/AuthHelper.php

    AuthHelper::isLoggedIn() → Checks if user is logged in

    AuthHelper::userId() → Returns the current user's ID

    AuthHelper::userRole() → Returns the role slug (admin, vet, pet-owner)

    AuthHelper::isAdmin(), isVet(), isPetOwner() → Role checks

    AuthHelper::logout() → Destroys session and redirects

### Located in: includes/functions.php

    * isAuth() – Checks if user is logged in

    * getDashboardRedirect() – Returns the correct dashboard path based on the role

    * isOwner($userId) – Ownership check (coming soon)

## 📤 API Helpers 
    * ApiResponseHelper::success($data)

    * ApiResponseHelper::error()

    * ApiResponseHelper::validate()

    * ApiResponseHelper::unauthorized()

    * ApiResponseHelper::forbidden()

    * ApiResponseHelper::notFound()

## 🛡️ CSRF Protection
### Located in: helpers/CsrfHelper.php

    To protect forms and POST requests:

    * CsrfHelper::generateToken() → Generates a unique token and stores it in session

    * CsrfHelper::getToken() → Returns the current CSRF token for form usage

    * CsrfHelper::checkToken($token) → Validates submitted token

    ✅ Example (Inside HTML Form):

    ```html
        <input type="hidden" name="csrf_token" value="<?php echo \Helpers\CsrfHelper::getToken(); ?>">
    ```

    ✅ Example (In Controller):

    ```php
        if (!\Helpers\CsrfHelper::checkToken($_POST['csrf_token'])) {
        echo ApiResponseHelper::forbidden('Invalid CSRF token');
        exit;
    }
    ```
    CSRF protection is enabled by default — just include a token in your form or API request and validate it server-side when needed.

# 📂 Folder Structure
    /controllers         
    /Core               → Base classes (DB, Router, ActiveRecord)
    /database
    ├── /migrations       → Table creation & schema changes
    └── /seeders          → Default data insertions
    /helpers
    /includes           → Core helpers (DB.php, app.php, functions.php)
    /models
    /public             → Public index.php + assets
    ├───/build
    │   ├── /css
    │   ├── /img
    │   ├── /js
    └── /uploads
        ├── /documents
        ├── /owners
        ├── /pets
        └── /vets
    /resources
    ├── /APIResources
    ├── /contracts
    ├── /emails
    ├── /pdf
    └── /placeholders
    /src
    ├── /images
    ├── /js
    └── /scss
            ├── base/
            │   ├── _globals.scss          → Body, html, headings, etc.
            │   ├── _index.scss            → Imports everything from base
            │   ├── _reset.scss            → Clean base styles (if you want to replace normalize)
            │   └── _utilities.scss        → Auto-generated helpers (.m-2, .text-center)
            │
            ├── components/
            │   ├── _alerts.scss
            │   ├── _buttons.scss
            │   ├── _cards.scss
            │   ├── _forms.scss
            │   ├── _index.scss
            │   └── _modals.scss
            │
            ├── layout/
            │   ├── _admin-header.scss
            │   ├── _admin-footer.scss
            │   ├── _admin-sidebar.scss
            │   ├── _footer.scss
            │   ├── _header.scss
            │   ├── _index.scss
            │   ├── _pets-header.scss
            │   ├── _pets-footer.scss
            │   ├── _pets-sidebar.scss
            │   ├── _vets-header.scss
            │   ├── _vets-footer.scss
            │   └── _vets-sidebar.scss
            │
            ├── main-pages/
            │   ├── _home.scss             → Custom Home styles
            │   └── _index.scss   
            │
            ├── mixins/
            │   ├── _buttons.scss          → Button generator
            │   ├── _cards.scss            → Cards generator
            │   ├── _grid.scss             → Grid system
            │   ├── _index.scss            → Button generator
            │   ├── _media.scss            → All media query mixins
            │   ├── _modals.scss           → Modal mixin
            │   └── _text.scss             → All text styles
            │
            │
            ├── pet-pages/
            │   ├── _dashboard.scss             → Custom pet-owners dashboard styles
            │   └── _index.scss   
            │
            ├── tokens/
            │   ├── _breakpoints.scss      → Breakpoints for media queries
            │   ├── _colors.scss           → All colors and state colors
            │   ├── _fonts.scss            → Font families and weights
            │   ├── _index.scss            → Imports all token files
            │   ├── _radius.scss           → Border radius tokens
            │   └── _spacing.scss          → Padding, margins, etc.
            │
            ├── layout/
            │   ├── _header.scss
            │   ├── _footer.scss
            │   ├── _sidebar.scss
            │   └── _index.scss
            │
            ├── vet-pages/
            │   ├── _dashboard.scss             → Custom vets dashboard styles
            │   └── _index.scss   
            │
            │
            └── app.scss                   → Imports all index.scss files
    /views
    ├── /auth
    │   ├── account-created.php
    │   └── register.php
    ├── /layouts
    │   └── main-layout.php
    ├── /pages
    │   └── index.php
    └── /templates
    │   ├── footer.php
    │   └── header.php


# 🧠 Tips for Team Members
    * Migrations are tracked in the DB, safe to rerun.

    * Seeders are smart and won't duplicate entries.

    * Use helpers for files, language, API, and auth to avoid duplication.

    * Prefer fetch() calls for frontend and toArray() for API responses.


### Welcome to the team! 🐱🐶💻