AG-UI for PHP
=============

The [AG-UI protocol](https://docs.ag-ui.com) for PHP: its events, the run agent input,
SSE encoding, and the verification of an event stream — every event is validated at
the point of emission, against AG-UI 1.0.

This is the protocol alone, for any PHP agent. To run a Symfony AI agent, see
[`webmamba/ag-ui-symfony-ai-bridge`](https://github.com/WebMamba/ag-ui-symfony-ai-bridge); in a Symfony
application, [`webmamba/ag-ui-bundle`](https://github.com/WebMamba/ag-ui-bundle).

Installation
------------

```bash
composer require webmamba/ag-ui
```

Usage
-----

```php
use AgUi\Encoder\SseEncoder;
use AgUi\Run\RunAgentInput;

$input = RunAgentInput::fromArray(json_decode($body, true));
$encoder = new SseEncoder();

foreach ($myAgent->run($input) as $event) { // your events: RunStartedEvent, TextMessageContentEvent…
    echo $encoder->encode($event);
}
```

Resources
---------

 * [AG-UI protocol](https://docs.ag-ui.com)
 * [Glossary](https://github.com/WebMamba/ag-ui-php/blob/main/CONTEXT.md) and [design decisions](https://github.com/WebMamba/ag-ui-php/tree/main/docs/adr)
 * [Report issues and send pull requests](https://github.com/WebMamba/ag-ui-php) in the main repository
