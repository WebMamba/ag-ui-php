import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { AgUiClient } from '../src/client.js';
import { fakeServer } from './sse.js';

/**
 * The AG-UI conformance corpus (resources/ag-ui/spec-1.0/conformance), replayed through
 * the client's public interface. See the corpus README for what each `expect` key means.
 */
const STREAMS = fileURLToPath(new URL('../../../../resources/ag-ui/spec-1.0/conformance/streams/', import.meta.url));

/** Fixtures the v0.1 client does not claim, and why: the gap stays visible. */
const SKIPPED = {
    'rejects malformed streams (the server validates at emission, ADR 0002)': [
        'chunk-conflicting-role-fatal', 'chunk-conflicting-tool-name-fatal', 'content-without-start-fatal',
        'custom-closes-chunk-stream-fatal', 'event-after-run-finished-fatal', 'first-chunk-missing-id-fatal',
        'first-event-not-run-started-fatal', 'first-tool-chunk-missing-name-fatal', 'malformed-known-field-fatal',
        'nested-run-started-fatal', 'open-message-at-run-finished-fatal', 'reasoning-discipline-verified',
        'reasoning-message-wrong-role-fatal', 'step-finished-without-start-fatal', 'unknown-enum-value-role-fatal',
        'unknown-outcome-array-fatal',
    ],
    'subagents': [
        'run-finished-with-active-subagent-fatal', 'subagent-attribution-mismatch-fatal',
        'subagent-finished-unstarted-fatal', 'subagent-started-missing-name-fatal',
        'subagent-terminal-closes-open-chunk-stream', 'subagent-unannounced-attribution-accepted',
    ],
    'older protocol versions and version negotiation': [
        'current-peer-declares-protocol-version', 'era-0-0-39-flattens-content', 'era-0-0-45-thinking-translated',
        'era-0-0-47-upgrades-binary-content', 'era-0-0-57-subagent-dropped-with-warning',
        'era-current-peer-keeps-modern-content', 'era-pinned-peer-omits-protocol-version',
        'producer-declares-newer-version-warns', 'producer-declares-same-version-quiet',
        'producer-declares-uninterpretable-version-warns',
    ],
    'schema enforcement (stripping undeclared material)': [
        'unknown-outcome-stripped', 'unknown-part-in-message-list', 'unknown-property-stripped',
    ],
};

const skipReason = (name) => Object.entries(SKIPPED).find(([, names]) => names.includes(name))?.[0];

const fixtures = readdirSync(STREAMS)
    .filter((file) => file.endsWith('.json'))
    .map((file) => JSON.parse(readFileSync(STREAMS + file, 'utf8')));

describe('conformance', () => {
    for (const fixture of fixtures) {
        const reason = skipReason(fixture.name);
        const test = reason ? it.skip : it;

        test(`${fixture.area}: ${fixture.name}${reason ? ` (skipped: ${reason})` : ''}`, async () => {
            const server = fakeServer(fixture.stream);
            const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
            client.messages = structuredClone(fixture.input?.messages ?? []);

            const events = [];
            const warnings = [];
            client.addEventListener('event', ({ detail }) => events.push(detail));
            client.addEventListener('warning', ({ detail }) => warnings.push(detail.message));

            let error = null;
            try {
                await client.run();
            } catch (e) {
                error = e;
            }

            assertExpectations(fixture.expect, { error, events, warnings, client, request: server.requests[0]?.input });
        });
    }
});

function assertExpectations(expectation, { error, events, warnings, client, request }) {
    if (expectation.outcome === 'completed') expect(error).toBeNull();
    if (expectation.outcome === 'failed') expect(error).not.toBeNull();
    if (expectation.errorContains) expect(error?.message).toContain(expectation.errorContains);

    if (expectation.runError !== undefined) {
        const runError = events.find((event) => event.type === 'RUN_ERROR');
        expect(runError).toBeDefined();
        if (typeof expectation.runError === 'string') expect(runError.message).toContain(expectation.runError);
    }

    if (expectation.eventTypes) expect(events.map((event) => event.type)).toEqual(expectation.eventTypes);
    for (const type of expectation.eventTypesAbsent ?? []) {
        expect(events.map((event) => event.type)).not.toContain(type);
    }
    for (const [path, value] of Object.entries(expectation.eventPaths ?? {})) {
        expect(at(events, path), path).toEqual(value);
    }
    for (const path of expectation.eventAbsentPaths ?? []) {
        expect(at(events, path), path).toBeUndefined();
    }

    for (const warning of expectation.warnings ?? []) {
        expect(warnings.some((message) => message.includes(warning)), `a warning containing "${warning}" in ${JSON.stringify(warnings)}`).toBe(true);
    }
    if (expectation.noWarnings) expect(warnings).toEqual([]);

    if (expectation.messageCount !== undefined) expect(client.messages).toHaveLength(expectation.messageCount);
    if (expectation.messages) expectSubset(client.messages, expectation.messages);
    for (const path of expectation.messageAbsentPaths ?? []) {
        expect(at(client.messages, path), path).toBeUndefined();
    }

    if (expectation.state !== undefined) expectSubset(client.state, expectation.state);
    if (expectation.request) expectSubset(request, expectation.request);
    for (const path of expectation.requestAbsentPaths ?? []) {
        expect(at(request, path), path).toBeUndefined();
    }
}

/** Arrays must be the same length; objects are compared on the expected keys only. */
function expectSubset(actual, expected, path = '') {
    if (Array.isArray(expected)) {
        expect(actual, path).toBeInstanceOf(Array);
        expect(actual, path).toHaveLength(expected.length);
        expected.forEach((item, i) => expectSubset(actual[i], item, `${path}[${i}]`));
    } else if (expected !== null && typeof expected === 'object') {
        expect(actual, path).toBeTypeOf('object');
        expect(actual, path).not.toBeNull();
        for (const [key, value] of Object.entries(expected)) expectSubset(actual[key], value, `${path}.${key}`);
    } else {
        expect(actual, path).toEqual(expected);
    }
}

/** A dotted path ("4.outcome.interrupts.0.id") inside a value; undefined when absent. */
function at(value, path) {
    return path.split('.').reduce((current, key) => (current == null ? undefined : current[key]), value);
}
