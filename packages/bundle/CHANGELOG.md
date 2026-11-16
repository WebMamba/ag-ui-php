CHANGELOG
=========

0.1
---

 * Prepare every Symfony AI agent for AG-UI, with no configuration: a route injects the agent and runs it with `AgUi`
 * Add `AgUiResponse`, and a value resolver for `RunAgentInput`
 * Sign interrupt ids with `kernel.secret`
 * Add `#[AsAgentContext]`, to state what the server knows to the agents
 * Add `#[AsAgentComponent]`, which exposes a Twig component as a tool rendering it as an activity
 * Add the browser client: `AgUiClient`, and the `ag-ui` and `ag-ui-activities` Stimulus controllers
