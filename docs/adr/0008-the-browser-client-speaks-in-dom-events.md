# The browser client speaks in DOM events

The bundle's browser client handles the protocol and nothing else: it posts run agent
inputs, reads the event stream, keeps messages and shared state, and runs the frontend
tool and resume loop. It renders nothing. Every event of the stream becomes an
`ag-ui:`-prefixed DOM event — `TOOL_CALL_START` is `ag-ui:tool-call-start`, a `CUSTOM`
event is also `ag-ui:custom:<name>`, and derived changes (`ag-ui:state-changed`,
`ag-ui:messages-changed`, `ag-ui:activity-changed`) spare listeners from replaying JSON
Patches. The application renders with its own Stimulus controllers and plain
`data-action`s, the way a Symfony developer already wires a page.

The client is a plain class (`AgUiClient`) that emits on an `EventTarget`; the `ag-ui`
Stimulus controller only creates it and re-dispatches its events into the DOM, so the
protocol logic is tested without a DOM and stays usable outside Stimulus.

## Considered Options

- **A bare event bus** (parse the stream, dispatch, nothing more): the smallest client,
  but every application would rewrite the frontend tool loop, the interrupt resumes
  and the state patches — the hard part, with no business value in it.
- **A ready-made chat** (bubbles, markdown, activities): working in one line, but it
  imposes a design, and every application would end up fighting or forking it.
- **`@ag-ui/client`**: the reference client, but it pulls RxJS in through AssetMapper
  and exposes subscribers, not DOM events; the stream parsing it would save is a few
  lines.
- **A declarative attribute** (`data-ag-ui-on="RUN_STARTED"` plus a reaction): no
  controller for small reactions, but a second wiring language next to `data-action`;
  `aria-busy` on the controller's element covers the common case in CSS.

## Consequences

Stimulus actions cannot filter on `detail`, so events are split by name where filtering
is needed: `CUSTOM` events by name, and frontend tool calls as
`ag-ui:tool-call:<name>`, answered with `detail.respond(promise)`. Other events are not
split until a real need shows.

Frontend tools are registered in JavaScript (`client.registerTool()`, from
`ag-ui:connect`) when no element stands for them, or declared by an element
(`data-ag-ui-tool`) and offered only while it is on the page; the client reads both
before each run.

Since agent components would otherwise stop at the browser, the bundle ships one
renderer after all: a small `ag-ui-activities` controller for `twig_component`
activities, which fills an activity slot or appends. Every other kind of rendering is
the application's. DOM events bubble up, never down, so it subscribes itself to the
`ag-ui` element around it rather than through a `data-action`. An interrupt's
activity carries the interrupt's id, which it sets as `data-ag-ui-interrupt-id`: a
button inside the component answers with `ag-ui#resume` and nothing else.

The controller adds what only a page needs: `aria-busy` while the agent runs, then
`ag-ui:error` if the run failed — a rejected promise in an action would reach no one —
and `ag-ui:idle` once it is over. The client hands what it emits to an `onEmit`
callback, which is how the controller relays it: the client stays an `EventTarget`
any code can listen to without the DOM.
