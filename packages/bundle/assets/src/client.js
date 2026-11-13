import { applyPatch } from 'fast-json-patch';

const EVENT_TYPES = new Set([
    'RUN_STARTED', 'RUN_FINISHED', 'RUN_ERROR', 'STEP_STARTED', 'STEP_FINISHED',
    'TEXT_MESSAGE_START', 'TEXT_MESSAGE_CONTENT', 'TEXT_MESSAGE_END', 'TEXT_MESSAGE_CHUNK',
    'TOOL_CALL_START', 'TOOL_CALL_ARGS', 'TOOL_CALL_END', 'TOOL_CALL_CHUNK', 'TOOL_CALL_RESULT',
    'REASONING_START', 'REASONING_MESSAGE_START', 'REASONING_MESSAGE_CONTENT', 'REASONING_MESSAGE_END',
    'REASONING_MESSAGE_CHUNK', 'REASONING_END', 'REASONING_ENCRYPTED_VALUE',
    'STATE_SNAPSHOT', 'STATE_DELTA', 'MESSAGES_SNAPSHOT', 'ACTIVITY_SNAPSHOT', 'ACTIVITY_DELTA',
    'RAW', 'CUSTOM', 'SUBAGENT_STARTED', 'SUBAGENT_FINISHED', 'SUBAGENT_ERROR',
]);

const PATCH_OPS = new Set(['add', 'remove', 'replace', 'move', 'copy', 'test']);

/**
 * The AG-UI client: sends each run agent input, reads the event stream, keeps the
 * thread's messages and shared state. It renders nothing (ADR 0008).
 *
 * Emits `event` for every event delivered to the application (chunks arrive expanded),
 * and `warning` for material it dropped.
 */
export class AgUiClient extends EventTarget {
    /**
     * @param {object}   options
     * @param {string}   options.endpoint  the AG-UI endpoint's URL
     * @param {string}   [options.threadId]
     * @param {Function} [options.fetch]
     * @param {Function} [options.tools]   frontend tools declared elsewhere (the page), read
     *                                     before each run: () => [{ name, description?, parameters?, handler }]
     * @param {Function} [options.onEmit]  also called with everything the client emits,
     *                                     (name, detail) => void: to relay it elsewhere, such as the DOM
     */
    constructor({ endpoint, threadId = crypto.randomUUID(), fetch = globalThis.fetch.bind(globalThis), tools = () => [], onEmit = () => {} }) {
        super();
        this.onEmit = onEmit;
        this.endpoint = endpoint;
        this.threadId = threadId;
        this.fetch = fetch;
        this.declaredTools = tools;
        this.registeredTools = new Map();
        this.messages = [];
        this.state = {};
        this.interrupts = [];
        this.resumes = [];
    }

    /** A frontend tool: offered in every run, and run here when the agent calls it. */
    registerTool(name, { description, parameters } = {}, handler) {
        this.registeredTools.set(name, { name, description, parameters, handler });
    }

    /** The application changed the shared state: the agent reads it in the next run. */
    setState(state) {
        this.state = state;
        this.emit('state-changed', { state: this.state });
    }

    async send(content) {
        if (this.interrupts.length > 0) {
            throw new Error('The agent is waiting for an answer to its interrupts: resume them first.');
        }
        this.messages.push({ id: crypto.randomUUID(), role: 'user', content });
        await this.run();
    }

    /**
     * Answers one interrupt; once all of them are answered, the run resumes.
     *
     * @param {string} interruptId
     * @param {{ status?: 'resolved'|'cancelled', payload?: *, metadata?: object }} [answer]
     */
    async resume(interruptId, { status = 'resolved', ...answer } = {}) {
        if (!this.interrupts.some((interrupt) => interrupt.id === interruptId)) {
            throw new Error(`The agent is not waiting on the interrupt "${interruptId}".`);
        }

        this.interrupts = this.interrupts.filter((interrupt) => interrupt.id !== interruptId);
        this.resumes.push({ interruptId, status, ...answer });

        if (this.interrupts.length === 0) await this.run();
    }

    /** Stops the run in progress; what already arrived is kept. */
    stop() {
        this.abort?.abort();
    }

    /** Runs the agent until it is done, answering its frontend tool calls along the way. */
    async run() {
        this.abort = new AbortController();
        try {
            await this.loop(this.abort.signal);
        } catch (error) {
            if (error?.name !== 'AbortError') throw error;
        } finally {
            this.abort = null;
        }
    }

    async loop(signal) {
        while (true) {
            const tools = this.tools();
            const calls = await this.stream(tools, signal);
            if (this.interrupts.length > 0) return;

            const pending = calls.filter((call) => tools.has(call.function.name) && !this.answered(call.id));
            if (pending.length === 0) return;

            for (const call of pending) {
                this.push(await this.runTool(tools.get(call.function.name), call));
            }
        }
    }

    tools() {
        const tools = new Map(this.registeredTools);
        for (const tool of this.declaredTools()) {
            if (tools.has(tool.name)) throw new Error(`Two frontend tools are named "${tool.name}".`);
            tools.set(tool.name, tool);
        }
        return tools;
    }

    answered(toolCallId) {
        return this.messages.some((message) => message.role === 'tool' && message.toolCallId === toolCallId);
    }

    async runTool(tool, call) {
        const message = { id: crypto.randomUUID(), role: 'tool', toolCallId: call.id, content: '' };
        try {
            const result = await tool.handler(call.function.arguments === '' ? {} : JSON.parse(call.function.arguments));
            message.content = typeof result === 'string' ? result : JSON.stringify(result ?? null);
        } catch (error) {
            message.error = error?.message ?? String(error);
        }
        return message;
    }

    /** One run: posts the run agent input and applies its event stream. Returns the tool calls it made. */
    async stream(tools, signal) {
        const response = await this.fetch(this.endpoint, {
            method: 'POST',
            signal,
            headers: { 'Content-Type': 'application/json', Accept: 'text/event-stream' },
            body: JSON.stringify({
                threadId: this.threadId,
                runId: crypto.randomUUID(),
                messages: this.messages,
                tools: [...tools.values()].map(({ name, description, parameters }) => ({ name, description, parameters })),
                state: this.state,
                context: [],
                forwardedProps: {},
                resume: this.resumes.splice(0),
            }),
        });
        if (!response.ok || !response.body) {
            throw new Error(`The AG-UI endpoint answered ${response.status} ${response.statusText}`.trim());
        }

        const calls = [];
        this.addEventListener('tool-call-start', collect);
        function collect({ detail }) {
            calls.push(detail.toolCallId);
        }

        try {
            await this.consume(response.body);
        } finally {
            this.removeEventListener('tool-call-start', collect);
            this.emit('messages-changed', { messages: this.messages });
        }

        return calls.map((id) => this.toolCall(id)).filter(Boolean);
    }

    async consume(body) {
        const chunks = new ChunkExpander();
        for await (const event of readEvents(body)) {
            if (!EVENT_TYPES.has(event.type)) {
                this.warn(`Dropped an event of unknown type ${event.type}.`);
                continue;
            }
            for (const expanded of chunks.expand(event)) this.deliver(expanded);
        }
        for (const expanded of chunks.close()) this.deliver(expanded);
    }

    deliver(event) {
        this.apply(event);

        this.emit('event', event);
        this.emit(event.type.toLowerCase().replaceAll('_', '-'), event);
        if (event.type === 'CUSTOM') this.emit(`custom:${event.name}`, event);

        if (event.type.startsWith('STATE_')) this.emit('state-changed', { state: this.state });
        if (event.type.startsWith('ACTIVITY_')) {
            const activity = this.message(event.messageId);
            if (activity) this.emit('activity-changed', { messageId: activity.id, activityType: activity.activityType, content: activity.content });
        }
    }

    emit(name, detail) {
        this.dispatchEvent(new CustomEvent(name, { detail }));
        this.onEmit(name, detail);
    }

    apply(event) {
        switch (event.type) {
            case 'TEXT_MESSAGE_START':
                this.openMessage(event.messageId, event.role ?? 'assistant', event.metadata);
                break;
            case 'REASONING_MESSAGE_START':
                this.openMessage(event.messageId, 'reasoning', event.metadata);
                break;
            case 'TEXT_MESSAGE_CONTENT':
            case 'REASONING_MESSAGE_CONTENT': {
                const message = this.message(event.messageId);
                if (!message) break;
                message.content += event.delta;
                mergeMetadata(message, event.metadata);
                break;
            }
            case 'TEXT_MESSAGE_END':
            case 'REASONING_MESSAGE_END': {
                const message = this.message(event.messageId);
                if (message) mergeMetadata(message, event.metadata);
                break;
            }
            case 'REASONING_ENCRYPTED_VALUE': {
                const message = this.message(event.entityId);
                if (message) message.encryptedValue = event.encryptedValue;
                break;
            }

            case 'TOOL_CALL_START': {
                const parent = this.message(event.parentMessageId)
                    ?? this.push({ id: event.parentMessageId ?? crypto.randomUUID(), role: 'assistant' });
                parent.toolCalls ??= [];
                const call = { id: event.toolCallId, type: 'function', function: { name: event.toolCallName, arguments: '' } };
                mergeMetadata(call, event.metadata);
                parent.toolCalls.push(call);
                break;
            }
            case 'TOOL_CALL_ARGS': {
                const call = this.toolCall(event.toolCallId);
                if (!call) break;
                call.function.arguments += event.delta;
                mergeMetadata(call, event.metadata);
                break;
            }
            case 'TOOL_CALL_END': {
                const call = this.toolCall(event.toolCallId);
                if (call) mergeMetadata(call, event.metadata);
                break;
            }
            case 'RUN_FINISHED':
                this.interrupts = event.outcome?.type === 'interrupt' ? [...event.outcome.interrupts] : [];
                break;

            case 'TOOL_CALL_RESULT':
                this.push({ id: event.messageId, role: 'tool', toolCallId: event.toolCallId, content: event.content });
                break;

            case 'MESSAGES_SNAPSHOT':
                this.messages = structuredClone(event.messages);
                break;

            case 'STATE_SNAPSHOT':
                this.state = structuredClone(event.snapshot);
                break;
            case 'STATE_DELTA': {
                const state = this.patched(this.state, this.recognisedOperations(event.delta), 'state');
                if (state !== undefined) this.state = state;
                break;
            }

            case 'ACTIVITY_SNAPSHOT': {
                const activity = this.message(event.messageId);
                if (!activity) {
                    this.push({ id: event.messageId, role: 'activity', activityType: event.activityType, content: structuredClone(event.content) });
                } else if (event.replace !== false) {
                    activity.activityType = event.activityType;
                    activity.content = structuredClone(event.content);
                }
                break;
            }
            case 'ACTIVITY_DELTA': {
                const activity = this.message(event.messageId);
                if (!activity) {
                    this.warn(`Skipped an ACTIVITY_DELTA for the unknown activity ${event.messageId}.`);
                    break;
                }
                const content = this.patched(activity.content, this.recognisedOperations(event.patch), 'activity');
                if (content !== undefined) activity.content = content;
                break;
            }
        }
    }

    /** A START for an id already held continues that message: closed is not sealed. */
    openMessage(id, role, metadata) {
        const message = this.message(id) ?? this.push({ id, role, content: '' });
        message.content ??= '';
        mergeMetadata(message, metadata);
    }

    push(message) {
        this.messages.push(message);
        return message;
    }

    message(id) {
        return id === undefined ? undefined : this.messages.find((message) => message.id === id);
    }

    toolCall(id) {
        for (const message of this.messages) {
            const call = message.toolCalls?.find((toolCall) => toolCall.id === id);
            if (call) return call;
        }
        return undefined;
    }

    /** RFC 6902 operations only: any other `op` is dropped with a warning. */
    recognisedOperations(operations) {
        return operations.filter((operation, i) => {
            if (PATCH_OPS.has(operation.op)) return true;
            this.warn(`Dropped /delta/${i}: "${operation.op}" is not a JSON Patch operation.`);
            return false;
        });
    }

    /** The document with the whole patch applied, or undefined (and a warning) if any of it fails. */
    patched(document, operations, what) {
        try {
            return applyPatch(structuredClone(document), operations, true, true).newDocument;
        } catch (error) {
            this.warn(`Failed to apply ${what} patch: ${error.message}`);
            return undefined;
        }
    }

    warn(message) {
        this.emit('warning', { message });
    }
}

/** Metadata merges key by key, last write winning, without recursing. */
function mergeMetadata(target, metadata) {
    if (metadata) target.metadata = { ...target.metadata, ...metadata };
}

/**
 * Expands chunk events into their START / CONTENT / END events. A chunk with an id
 * opens a lane, one without continues it; any other event but RAW closes it.
 */
class ChunkExpander {
    lane = null;

    *expand(event) {
        const kind = CHUNKS[event.type];

        if (!kind) {
            if (event.type !== 'RAW') yield* this.close();
            yield event;
            return;
        }

        const id = event[kind.id];
        if (this.lane && (this.lane.kind !== kind || (id !== undefined && id !== this.lane.id))) {
            yield* this.close();
        }

        if (!this.lane) {
            this.lane = { kind, id };
            yield kind.start(event);
        }

        if (event.delta !== undefined && event.delta !== '') {
            yield { type: kind.content, [kind.id]: this.lane.id, delta: event.delta };
        }
    }

    *close() {
        if (!this.lane) return;
        const { kind, id } = this.lane;
        this.lane = null;
        yield { type: kind.end, [kind.id]: id };
    }
}

const CHUNKS = {
    TEXT_MESSAGE_CHUNK: {
        id: 'messageId', content: 'TEXT_MESSAGE_CONTENT', end: 'TEXT_MESSAGE_END',
        start: (event) => ({ type: 'TEXT_MESSAGE_START', messageId: event.messageId, role: event.role ?? 'assistant' }),
    },
    REASONING_MESSAGE_CHUNK: {
        id: 'messageId', content: 'REASONING_MESSAGE_CONTENT', end: 'REASONING_MESSAGE_END',
        start: (event) => ({ type: 'REASONING_MESSAGE_START', messageId: event.messageId, role: 'reasoning' }),
    },
    TOOL_CALL_CHUNK: {
        id: 'toolCallId', content: 'TOOL_CALL_ARGS', end: 'TOOL_CALL_END',
        start: (event) => ({ type: 'TOOL_CALL_START', toolCallId: event.toolCallId, toolCallName: event.toolCallName, parentMessageId: event.parentMessageId }),
    },
};

/**
 * The AG-UI events of an SSE body, read as the SSE specification says: lines end with
 * CRLF, LF or CR; a blank line ends a frame. Only `message` frames (no `event:` field)
 * carry AG-UI events: named ones — Symfony's `symfony:debug:started` — are skipped.
 */
async function* readEvents(body) {
    const reader = body.pipeThrough(new TextDecoderStream()).getReader();
    let buffer = '';
    let frame = { event: 'message', data: [] };

    while (true) {
        const { value, done } = await reader.read();
        if (done) return;

        buffer += value;
        // A CR at the very end may be the first half of a CRLF: wait for what follows.
        const held = buffer.endsWith('\r') ? '\r' : '';
        const lines = buffer.slice(0, buffer.length - held.length).split(/\r\n|\r|\n/);
        // The last line may be incomplete.
        buffer = lines.pop() + held;

        for (const line of lines) {
            if (line === '') {
                if (frame.event === 'message' && frame.data.length > 0) yield JSON.parse(frame.data.join('\n'));
                frame = { event: 'message', data: [] };
                continue;
            }
            if (line.startsWith(':')) continue;

            const colon = line.indexOf(':');
            const field = colon === -1 ? line : line.slice(0, colon);
            const fieldValue = colon === -1 ? '' : line.slice(colon + 1).replace(/^ /, '');
            if (field === 'data') frame.data.push(fieldValue);
            if (field === 'event') frame.event = fieldValue;
        }
    }
}
