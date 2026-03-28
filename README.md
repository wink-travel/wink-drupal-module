# wink-drupal-module

Drupal 10 / 11 module that integrates [Wink Travel](https://wink.travel) web components into Drupal sites as native blocks.

## What this repo contains

```
wink/                   # The Drupal module (drupal/wink on Packagist / drupal.org)
  src/
    Form/               # Admin settings form
    Plugin/Block/       # 6 block plugins (Content, Lookup, Search, Account, Itinerary, Cart)
    Service/            # OAuth2 API service (server-side inventory fetch)
  config/               # Default configuration and schema
  templates/            # Twig templates for block output
  tests/
    src/Unit/           # PHPUnit unit tests
    src/Kernel/         # Drupal KernelTestBase integration tests
  wink.info.yml
  wink.libraries.yml    # CDN script / style declarations
  wink.routing.yml
  composer.json
```

## Quick start

Full developer setup, installation instructions, block reference, and deployment guide are in the module README:

➜ **[wink/README.md](wink/README.md)**

## Requirements

- PHP 8.1+
- Drupal 10.0+ or 11.x
- Composer

## Install in a Drupal project

```bash
composer require drupal/wink
drush en wink -y
```

## Run tests

```bash
# From your Drupal project root (after symlinking / requiring this module):
vendor/bin/phpunit web/modules/contrib/wink
```

## Contributing

1. Fork this repo and create a feature branch.
2. Follow [Drupal coding standards](https://www.drupal.org/docs/develop/standards).
3. Ensure all tests pass before opening a PR.
4. Open a pull request against `main`.

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html).
