# Shared state is a typed class, injected as itself

A route's shared state is an application class (a cart, a passage plan) that tools
inject directly, like any service. Before each run the bridge populates that very
instance from the run agent input with the Serializer, after each tool it diffs it into
a `STATE_DELTA`, and after the run it resets it. Tools depend on their own domain class
and never on an SDK type, which is what "the agent needs no AG-UI code" means for state.

## Considered Options

- **Raw JSON** (`get('/path')`, `set('/path', $value)`): covers every case, but every
  tool manipulates untyped arrays, and validation is left to each one.
- **An SDK wrapper** (`SharedState::get(Cart::class)`): typed, but every tool that
  touches the state depends on the SDK.

## Consequences

- One state class per endpoint, handed to `AgUi::run()` by its route (it was declared in
  the endpoint configuration until [0007](0007-the-route-picks-the-agent.md)).
- A state the class cannot be built from, or that breaks its Validator constraints, is
  bad input: the request is rejected with a 422 before the stream starts.
- The class is a shared service filled per run, so it must be reset after each one —
  the bridge does it — for workers (FrankenPHP, RoadRunner) serving several runs.
- The model reads the state as JSON unless the class implements `DescribesItself`.
