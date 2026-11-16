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

It brings [`webmamba/ag-ui`](https://github.com/WebMamba/ag-ui) (the protocol) and
[`webmamba/ag-ui-symfony-ai-bridge`](https://github.com/WebMamba/ag-ui-symfony-ai-bridge).
There is no configuration: every agent declared under `ai.agent` is ready to run over
AG-UI, and unchanged when called anywhere else.

Interrupt ids are signed with `kernel.secret` (`APP_SECRET`): every server that may run
a thread needs the same one.

Usage
-----

### The route picks the agent

Inject the agent by its name, and run it with `AgUi`:

```php
use AgUi\Bridge\SymfonyAi\AgUi;
use AgUi\Bundle\Http\AgUiResponse;
use AgUi\Run\RunAgentInput;
use Symfony\AI\Agent\AgentInterface;

#[Route('/agent', methods: ['POST'])]
public function __invoke(RunAgentInput $input, AgentInterface $navigator, AgUi $agUi): AgUiResponse
{
    return new AgUiResponse($agUi->run($input, $navigator));
}
```

`AgUiResponse` streams one Server-Sent Event per AG-UI event, and stops producing them
once the client is gone. The server keeps nothing between runs: the client sends the
whole thread every time.

### Shared state

Pass an object as `state`: it is filled from the run agent input before the run, tools
inject it like any service, and every change they make is sent to the client as a state
delta.

```php
final class PassagePlan
{
    /** @var list<string> */
    public array $waypoints = [];
}

#[AsTool('add_waypoint', 'Adds a waypoint to the passage.')]
final readonly class AddWaypoint
{
    public function __construct(private PassagePlan $plan) {}

    public function __invoke(string $name): string
    {
        $this->plan->waypoints[] = $name;

        return 'Added.';
    }
}

return new AgUiResponse($agUi->run($input, $navigator, state: $plan));
```

The model reads the state as JSON; implement `DescribesItself` to write what it reads
instead. A state the client sent that does not fit the class throws an
`InvalidStateException`, to turn into a 422.

### What the server knows

`#[AsAgentContext]` states a fact to the agent at the start of every run; it is read
again after each tool, and the model is told when it changed. `null` leaves it out.

```php
#[AsAgentContext('Interface theme')]
final class Theme
{
    public function __invoke(): ?string { /* ... */ }
}
```

On a method, it reads that method; `agent: 'navigator'` tells only that agent.

### Agent components

`#[AsAgentComponent]` exposes a Twig component to the model as a tool: its public
properties are the tool's parameters, and calling it renders the component on the
server and shows it in the conversation. `#[AgentIgnore]` keeps a property out.

```php
#[AsTwigComponent]
#[AsAgentComponent('Shows a mooring the skipper can reserve.')]
final class MooringCard
{
    public string $marina;
    public int $nights = 3;
}
```

A tool can return such an activity too, with `ComponentActivityFactory::create()`.

### Interrupts

A tool that needs a human decision implements `ResumableTool` and returns an
`Interrupt`: the run finishes with an interrupt outcome, and `resume()` is called in the
next run with the answer.

```php
#[AsTool('reserve_mooring', 'Reserves a mooring.')]
final readonly class ReserveMooring implements ResumableTool
{
    public function __construct(private ComponentActivityFactory $activities) {}

    public function __invoke(string $marina, int $nights): Interrupt
    {
        return Interrupt::confirmation("Reserve a mooring at $marina?")
            ->withActivity($this->activities->create('MooringCard', compact('marina', 'nights'), 'Asked the skipper.'));
    }

    public function resume(array $arguments, ResumeEntry $resume): string
    {
        return true === ($resume->payload['approved'] ?? false) ? 'Reserved.' : 'Declined.';
    }
}
```

The interrupt's id is signed: a client cannot resume a tool call the model never made,
nor change its arguments. `resume()` checks authorization as `__invoke()` would.

### More

* `RunContext` gives a tool the thread id, the run id and the props the client forwarded.
* A `ToolResultMapping` service adds events after a tool result, such as a `CUSTOM` event.

### In the browser

The bundle ships Stimulus controllers for AssetMapper. The `ag-ui` controller is the
AG-UI client: every event of the stream is dispatched as an `ag-ui:*` DOM event, which
your controllers listen to; `ag-ui-activities` shows the agent components.

```twig
<div {{ stimulus_controller('ag-ui', {endpoint: path('agent')}) }}
     data-action="ag-ui:text-message-content->chat#append ag-ui:state-changed->chat#refresh">
    <form data-action="submit->ag-ui#send">
        <textarea data-ag-ui-target="input"></textarea>
    </form>
    <div data-controller="ag-ui-activities"></div>
</div>
```

* **Events**: `ag-ui:<event-type>` for each event (`ag-ui:tool-call-start`),
  `ag-ui:custom:<name>` for a `CUSTOM` event, `ag-ui:state-changed` and
  `ag-ui:activity-changed`, and `ag-ui:connect`, `ag-ui:error`, `ag-ui:idle`. The
  element has `aria-busy` while the agent runs.
* **Answering an interrupt**: in the component the interrupt shows, a button with
  `data-action="ag-ui#resume"`; its other params are the payload
  (`data-ag-ui-approved-param="true"`).
* **Frontend tools**: an element with `data-ag-ui-tool="pick_date"`,
  `data-ag-ui-tool-description` and `data-ag-ui-tool-parameters` (a JSON Schema) is a
  tool the browser runs, offered while the element is on the page; answer its
  `ag-ui:tool-call:pick_date` event with `event.detail.respond(result)`. Or register
  one with `client.registerTool()` on `ag-ui:connect`.
* **Shared state from the page**: dispatch an event with the new state as its detail,
  to `ag-ui#setState`.

Resources
---------

 * [AG-UI protocol](https://docs.ag-ui.com)
 * [Glossary](https://github.com/WebMamba/ag-ui-php/blob/main/CONTEXT.md) and [design decisions](https://github.com/WebMamba/ag-ui-php/tree/main/docs/adr)
 * [Passage Agent](https://github.com/WebMamba/turbo-agent), a demo application
 * [Report issues and send pull requests](https://github.com/WebMamba/ag-ui-php) in the main repository
