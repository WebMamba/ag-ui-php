# AG-UI specification, frozen

A copy of the parts of the [AG-UI repository](https://github.com/ag-ui-protocol/ag-ui)
this SDK is held to, at the commit in `COMMIT`:

- `spec-1.0/schema.json`: the protocol's JSON Schema. The model classes are generated
  from it (`php tools/generate-model.php`).
- `spec-1.0/fixtures/`: one valid or invalid instance per protocol type.
- `spec-1.0/conformance/streams/`: whole event streams with the outcome a client must
  reach.
- `sdk-fixtures/`: expectations shared by every SDK, such as null omission.

Update with `tools/sync-spec.sh <sha>`: never edit these files by hand.
