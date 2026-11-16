CHANGELOG
=========

0.1
---

 * Add the AG-UI 1.0 protocol: every event, message, content part and the run agent input, as typed models
 * Validate every event at the point of emission, and the order of an event stream with `EventSequenceVerifier`
 * Add `RunStream` to write a run's events, expanding chunks and attributing subagents' events
 * Add `SseEncoder` and `AgUiSerializer`, checked against the AG-UI conformance fixtures
 * Add JSON Patch, for state deltas
