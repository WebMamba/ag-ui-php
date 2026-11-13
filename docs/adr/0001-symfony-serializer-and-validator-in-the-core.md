# The core depends on Symfony Serializer and Validator

The core package (`webmamba/ag-ui`) decodes the run agent input and encodes events with
Symfony Serializer, and checks both with Symfony Validator, instead of being a
dependency-free protocol library like the other AG-UI SDKs. The SDK is built for Symfony
applications first, and those components already solve polymorphic decoding (messages by
`role`), null omission on the wire and declarative constraints; hand-writing them would
be code to maintain for no Symfony user's benefit.

## Considered Options

- **No dependencies** (hand-written `fromArray()` / `JsonSerializable`): usable from any
  framework, but duplicates what the Serializer and Validator do, and makes the
  constraints on events imperative code instead of attributes.

## Consequences

The core still runs outside the full-stack framework (the components are standalone),
but swapping them out later would change every event and type class: treat it as
permanent.
