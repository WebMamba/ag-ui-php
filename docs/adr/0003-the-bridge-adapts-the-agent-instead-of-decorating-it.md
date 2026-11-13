# The bridge adapts the Symfony AI agent instead of decorating it

The Symfony AI bridge exposes an agent through an adapter, the AG-UI agent, whose
`run(RunAgentInput)` produces the run's events, rather than through a decorator that
implements `AgentInterface`. A decorator would have to return an `Execution` of Symfony AI
updates, so something else would still translate them into AG-UI; and a Symfony AI agent
and an AG-UI endpoint are different things — one calls a model, the other answers a run
— so one interface cannot honestly stand for both. Making an agent speak AG-UI without
code is the bundle's job: an endpoint configuration registers the route and controller.

> Amended by [0007](0007-the-route-picks-the-agent.md): the adapter is now `AgUi`, one
> service that runs whichever agent the route gives it; there is no endpoint configuration.

## Considered Options

- **Decorate `AgentInterface`**: drop-in, but the return type cannot carry events, and
  every AG-UI concept would leak into Symfony AI's.

## Consequences

Decoration is still used inside the bridge, around the agent's toolbox and tool
executor, to offer frontend tools and to stop the tool loop: an implementation detail,
not the public shape.
