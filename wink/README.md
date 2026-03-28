# Wink Travel — Drupal Module

Integrates [Wink Travel](https://wink.travel) web components ([elements.wink.travel](https://elements.wink.travel)) into Drupal 10 and 11 sites.

## What this module provides

| Feature | Details |
|---|---|
| CDN library | Loads `elements.js` + `styles.css` from the configured Wink environment on every page. |
| App loader | Injects `<wink-app-loader client-id="...">` once in the page footer. |
| 6 block plugins | Wink Content, Lookup, Search Button, Account Button, Itinerary Button, Shopping Cart Button. |
| Settings form | `admin/config/wink` — configure Client ID, environment, and server-side OAuth2 credentials. |
| Server-side API | Fetches available layouts from the Wink API to populate the Content block dropdown. |

---

## Requirements

- PHP 8.1+
- Drupal 10.0+ or 11.x
- Composer

---

## Developer setup — local Drupal environment

### Option A: DDEV (recommended)

```bash
mkdir my-drupal-site && cd my-drupal-site
ddev config --project-type=drupal10 --docroot=web
ddev start
ddev composer create drupal/recommended-project .
ddev composer require drush/drush
ddev drush site:install --account-pass=admin -y
ddev launch
```

### Option B: Lando

```bash
mkdir my-drupal-site && cd my-drupal-site
lando init --recipe drupal10
lando start
lando composer create-project drupal/recommended-project .
lando drush site:install --account-pass=admin -y
```

### Option C: plain Composer + PHP built-in server

```bash
composer create-project drupal/recommended-project my-drupal-site
cd my-drupal-site
cp web/sites/default/default.settings.php web/sites/default/settings.php
# Create a database and fill in settings.php, then:
php -S localhost:8080 -t web
```

---

## Installation

### Via Composer (recommended)

```bash
composer require drupal/wink
```

### Enable the module

```bash
drush en wink -y
# or via the Drupal UI: admin/modules
```

---

## Configuration

1. Navigate to **Administration › Configuration › Wink Travel** (`admin/config/wink`).
2. Enter your **Wink Client ID** (provided by your Wink account dashboard).
3. Select the **Environment**: Production, Staging, or Development.
4. Optionally expand **OAuth2 API Credentials** and enter your server-side Client ID and Secret to enable the layout picker dropdown in the Wink Content block.
5. Save configuration.

### Placing blocks

1. Go to **Administration › Structure › Block layout** (`admin/structure/block`).
2. Find the desired Wink block in the **Wink Travel** category.
3. Click **Place block**, choose a region, and configure as needed.

---

## Block reference

### Wink Content Block

Renders `<wink-content-loader layout="..." id="...">`.

Configuration fields:
- **Layout** — Select from available layouts (requires OAuth2 credentials) or enter an ID manually.
- **Content ID** — The Wink content identifier for the specific module to display.

### Wink Lookup Block

Renders `<wink-lookup>`. No configuration required.

### Wink Search Button

Renders `<wink-search-button>`. No configuration required.

### Wink Account Button

Renders `<wink-account-button>`. No configuration required.

### Wink Itinerary Button

Renders `<wink-itinerary-button>`. No configuration required.

### Wink Shopping Cart Button

Renders `<wink-shopping-cart-button>`. No configuration required.

---

## Running tests

### Prerequisites

```bash
# From your Drupal project root:
composer require --dev drupal/core-dev
```

### Run all module tests

```bash
vendor/bin/phpunit web/modules/contrib/wink
```

### Run only unit tests

```bash
vendor/bin/phpunit web/modules/contrib/wink/tests/src/Unit
```

### Run only kernel tests

```bash
vendor/bin/phpunit web/modules/contrib/wink/tests/src/Kernel
```

### With coverage report

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit web/modules/contrib/wink --coverage-text
```

### PHPUnit configuration

The tests rely on Drupal's standard `phpunit.xml.dist` located at the project root.
If your Drupal root is not `web/`, adjust the `SIMPLETEST_BASE_URL` environment
variable and the bootstrap path accordingly:

```bash
SIMPLETEST_BASE_URL=http://localhost \
SIMPLETEST_DB=mysql://root:root@localhost/drupal \
vendor/bin/phpunit web/modules/contrib/wink
```

---

## Permissions

| Permission | Description |
|---|---|
| `administer wink` | Access the settings form at `admin/config/wink`. Restrict to trusted roles only — this form stores OAuth2 secrets. |

---

## Uninstallation

```bash
drush pmu wink -y
```

All module configuration (`wink.settings`) is removed automatically on uninstall.

---

## Packaging for drupal.org

### Recommended: `git archive`

Drupal.org packaging is handled by the drupal.org automated packaging system
when you tag a release in the project repository. For local testing:

```bash
git archive --format=zip --prefix=wink/ HEAD > wink.zip
```

### Composer dist (for testing package resolution)

Add a path repository in your project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "/path/to/wink-drupal-module/wink"
    }
  ]
}
```

Then:

```bash
composer require drupal/wink:*@dev
```

---

## Environment URLs reference

| Environment | CDN JS/CSS | IAM (OAuth2) | API |
|---|---|---|---|
| Production | `https://elements.wink.travel` | `https://iam.wink.travel` | `https://api.wink.travel` |
| Staging | `https://staging-elements.wink.travel` | `https://staging-iam.wink.travel` | `https://staging-api.wink.travel` |
| Development | `https://dev.traveliko.com:8011` | `https://staging-iam.wink.travel` | `https://staging-api.wink.travel` |

---

## Maintainers

- [Wink Travel](https://wink.travel)
