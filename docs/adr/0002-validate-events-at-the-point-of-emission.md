# Events are validated where they are emitted, not in their constructors

Event classes are plain data carriers: their rules are Validator constraints, and the
run stream writer — the single point every event passes through before reaching the
network — validates each event and verifies the order of the whole event stream, then
turns any violation into a `RUN_ERROR`. The Ruby and Python SDKs validate in
constructors, but a constructor can only judge one event: most AG-UI mistakes are about
sequence (content before its start, an event after `RUN_FINISHED`), so one choke point
checks both, and nobody producing events can forget it.

## Consequences

- The verifier follows the official TypeScript client exactly, including expanding
  `*_CHUNK` events before verifying: the server must never reject a stream the official
  client accepts, nor the reverse. The `spec/1.0/conformance` fixtures arbitrate.
- Verification always runs, in production too: an invalid event stream is a bug, and a
  clear `RUN_ERROR` is better than a client stuck on a half-open message.
- An event object can be invalid while it is being built; only what is emitted is
  guaranteed valid.
