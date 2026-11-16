# The client owns the conversation

The server keeps nothing between runs: every run agent input carries the whole thread,
and the bridge rebuilds the agent's message bag from it. Interrupts and frontend tools
need no storage either — an interrupt's id is derived from its tool call and signed
(ADR 0009), and the open tool call is in the messages the client sends back. It is what the protocol
assumes, so it works with any AG-UI client, and a run can land on any server instance.

## Considered Options

- **A server-side thread store** (the client sends only the new message and the thread
  id): smaller requests and a server-controlled history, but state to persist, expire
  and secure, and a protocol variant no standard client speaks.

## Consequences

The client controls the history the model sees, so the bridge distrusts what it must:
system and developer messages from the client are dropped unless the route opts in (`trustClientInstructions`).
A thread store can come later as an optional layer, without changing the bridge.
