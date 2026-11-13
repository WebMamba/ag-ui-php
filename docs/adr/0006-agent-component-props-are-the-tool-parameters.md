# An agent component's public properties are its tool's parameters

A Twig component marked `#[AsAgentComponent]` becomes a tool whose parameters are the
component's public properties — the props it is hydrated with in a template — so a
component behaves the same whether a template or the model renders it, and exposing one
takes no tool class. A property the model must not fill is marked `#[AgentIgnore]`.

## Considered Options

- **`mount()` arguments**: closer to an explicit signature, but components without
  `mount()` would expose nothing, and the two hydration paths would diverge.
- **A hand-written render tool per component** (what the demo did): full control, and
  one class of boilerplate per component.

## Consequences

The container fails to compile when a component cannot be exposed — a property type
with no JSON Schema, or a `mount()` that needs arguments — naming the property and
suggesting `#[AgentIgnore]`. The model reads a sentence, never the markup.
