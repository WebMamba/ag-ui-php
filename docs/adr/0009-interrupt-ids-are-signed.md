# Interrupt ids are signed

An interrupt's id names its tool call and carries an HMAC-SHA256 of the thread, the
call's id, the tool's name and its arguments: `int_<toolCallId>.<signature>`. On a
resume, the bridge recomputes the id of every open tool call and compares it in
constant time; a resume that matches none is refused before any tool runs. The secret
is `AgUi`'s one required argument — `kernel.secret` in the bundle.

The client owns the conversation (ADR 0004), so the open tool call a resume answers
comes from the client too. With an id derived from the call alone, a modified client
could resume a call the model never made, with arguments the human was never shown,
and skip the confirmation the interrupt stands for.

## Considered Options

- **A server-side record of open interrupts**: the strongest binding, but state to
  store, expire and share between servers — what ADR 0004 chose to do without.
- **Signing the tool call id only**: proves the call came from the server, not that its
  arguments are the ones the human confirmed.

## Consequences

Every server that may run a thread needs the same secret, and rotating it voids the
interrupts open at that moment: their resumes are refused, as they would be after a
server restart with stored interrupts. A resume does not re-check who resumes; a tool
whose resume needs an authorization checks it in `resume()`, as it would in `__invoke()`.
