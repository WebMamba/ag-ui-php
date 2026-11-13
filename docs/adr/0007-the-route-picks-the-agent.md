# The route picks the agent; the bundle prepares every agent

A controller runs a Symfony AI agent over AG-UI with one service, `AgUi`, and the agent
it chooses: `$agUi->run($input, $navigator)`, where `AgentInterface $navigator` is
autowired by its name under `ai.agent`. Platform and model stay where Symfony AI keeps
them, in the agent's configuration, so a route that needs another model injects another
agent. The shared state and whether to trust the client's instructions are arguments
of the run, since they belong to the page the route serves, not to the agent.

The bridge still adapts the agent rather than decorating it (0003), and the agent still
needs three pieces sharing the run scope: the frontend toolbox, the tool executor and
the instructions processor. They cannot be added to an agent at run time — Symfony AI's
`Agent` is final and keeps its toolbox and executor private — so the bundle wires them
into every agent of type `Agent` under `ai.agent` at compile time. Each piece knows its
agent's name and acts only while a run is open for that agent: outside AgUi, or called
as another agent's subagent during a run, the agent works exactly as configured.

## Considered Options

- **Endpoint configuration** (`ag_ui.endpoints.<name>: {agent, state}`, injected as
  `#[Target('<name>')] AgUiAgent`), the previous design: the agent was bound to a name
  that stood for a route, and a controller could neither see nor choose it.
- **An opt-in list** (`ag_ui.agents: [navigator]`): explicit, but configuration for a
  fact the code already states by calling `run()` with that agent.
- **Building the agent in AgUi** (`$agUi->agent($platform, $model, $tools)`): duplicates
  Symfony AI's own agent configuration.

## Consequences

- No `ag_ui` configuration at all. Every agent is prepared; an unused one only carries
  three dormant objects.
- The agent components are offered only during a run, so an agent called elsewhere is
  never offered tools that render to an AG-UI client.
- The agent context is filtered per run by the agent's name (`#[AsAgentContext(agent:)]`).
- The state class is no longer registered by the bundle: the application's service
  autoloading does it, and the route injects the instance it hands to `run()`.
- An agent built by hand takes the pieces from `AgUi::toolbox()`, `toolExecutor()` and
  `inputProcessor()`; one without them fails its first run with a message that says so.
