AG-UI Bundle
============

Provides a tight integration of [AG-UI](https://docs.ag-ui.com) into the Symfony
full-stack framework: every [Symfony AI](https://symfony.com/doc/current/ai/index.html)
agent speaks AG-UI, Twig components become generative UI, and a Stimulus client
renders it all.

Installation
------------

```bash
composer require webmamba/ag-ui-bundle
```

Usage
-----

The route picks the agent; everything AG-UI comes from the bundle:

```php
#[Route('/agent', methods: ['POST'])]
public function __invoke(RunAgentInput $input, AgentInterface $navigator, AgUi $agUi): AgUiResponse
{
    return new AgUiResponse($agUi->run($input, $navigator));
}
```

A Twig component becomes a tool the model calls to show it:

```php
#[AsTwigComponent]
#[AsAgentComponent('Shows a mooring the skipper can reserve.')]
final class MooringCard
{
    public string $marina;
    public int $nights;
}
```

In the browser, the `ag-ui` controller is the AG-UI client: every event of the stream
is dispatched as an `ag-ui:*` DOM event, which your controllers listen to.

```twig
<div {{ stimulus_controller('ag-ui', {endpoint: path('agent')}) }}
     data-action="ag-ui:text-message-content->chat#append">
    <form data-action="submit->ag-ui#send">
        <textarea data-ag-ui-target="input"></textarea>
    </form>
    <div data-controller="ag-ui-activities"></div>
</div>
```

Resources
---------

 * [AG-UI protocol](https://docs.ag-ui.com)
 * [Glossary](../../CONTEXT.md) and [design decisions](../../docs/adr)
 * [Report issues](https://github.com/WebMamba/ag-ui-php/issues)
