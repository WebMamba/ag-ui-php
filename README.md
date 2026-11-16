AG-UI for PHP
=============

A server-side PHP implementation of the [AG-UI protocol](https://docs.ag-ui.com) (1.0):
it lets a PHP application expose an agent to any AG-UI client.

| Package | What it is |
|---|---|
| [`webmamba/ag-ui`](packages/core) | The protocol: events, run agent input, SSE encoding, stream verification |
| [`webmamba/ag-ui-symfony-ai-bridge`](packages/symfony-ai-bridge) | Runs any Symfony AI agent over AG-UI, without a framework |
| [`webmamba/ag-ui-bundle`](packages/bundle) | The Symfony integration: agents, routes, Twig components as generative UI, and a Stimulus client |

In a Symfony application, install the bundle; it brings the other two.

See it running in [Passage Agent](https://github.com/WebMamba/demo-ag-ui), an
ocean-passage navigator built on it.

Development
-----------

This is a monorepo; the packages are split into read-only repositories on release.

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
(cd packages/bundle/assets && npm ci && npm test)
```

The words used across the code are defined in [CONTEXT.md](CONTEXT.md), and the
design decisions recorded in [docs/adr](docs/adr).

License
-------

MIT, see [LICENSE](LICENSE).
