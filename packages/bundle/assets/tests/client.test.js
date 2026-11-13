import { describe, expect, it } from 'vitest';
import { AgUiClient } from '../src/client.js';
import { fakeServer, hangingServer, rawServer } from './sse.js';

describe('a run', () => {
    it('posts the user message and assembles the streamed answer', async () => {
        const server = fakeServer([
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'TEXT_MESSAGE_START', messageId: 'm1', role: 'assistant' },
            { type: 'TEXT_MESSAGE_CONTENT', messageId: 'm1', delta: 'Fair ' },
            { type: 'TEXT_MESSAGE_CONTENT', messageId: 'm1', delta: 'winds.' },
            { type: 'TEXT_MESSAGE_END', messageId: 'm1' },
            { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
        ]);
        const client = new AgUiClient({ endpoint: '/agent', threadId: 't1', fetch: server.fetch });

        await client.send('Weather in Papeete?');

        expect(server.requests).toHaveLength(1);
        expect(server.requests[0].url).toBe('/agent');
        expect(server.requests[0].input).toMatchObject({
            threadId: 't1',
            messages: [{ role: 'user', content: 'Weather in Papeete?' }],
            tools: [],
            state: {},
            context: [],
            forwardedProps: {},
        });
        expect(client.messages).toMatchObject([
            { role: 'user', content: 'Weather in Papeete?' },
            { id: 'm1', role: 'assistant', content: 'Fair winds.' },
        ]);
    });
});

describe('the events it emits', () => {
    const record = (client, ...names) => {
        const received = [];
        for (const name of names) client.addEventListener(name, ({ detail }) => received.push([name, detail]));
        return received;
    };

    it('emits every event under its own kebab-case name', async () => {
        const server = fakeServer([
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'TOOL_CALL_START', toolCallId: 'c1', toolCallName: 'weather', parentMessageId: 'm1' },
            { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
        ]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        const received = record(client, 'run-started', 'tool-call-start', 'run-finished');

        await client.run();

        expect(received.map(([name]) => name)).toEqual(['run-started', 'tool-call-start', 'run-finished']);
        expect(received[1][1]).toMatchObject({ toolCallId: 'c1', toolCallName: 'weather' });
    });

    it('also hands everything it emits to onEmit, for whoever relays it elsewhere', async () => {
        const server = fakeServer([
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'STATE_SNAPSHOT', snapshot: { waypoints: [] } },
            { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
        ]);
        const relayed = [];
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch, onEmit: (name) => relayed.push(name) });

        await client.run();

        expect(relayed).toEqual([
            'event', 'run-started',
            'event', 'state-snapshot', 'state-changed',
            'event', 'run-finished',
            'messages-changed',
        ]);
    });

    it('emits a CUSTOM event under its name too', async () => {
        const server = fakeServer([
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'CUSTOM', name: 'theme', value: 'night' },
            { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
        ]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        const received = record(client, 'custom:theme');

        await client.run();

        expect(received).toEqual([['custom:theme', expect.objectContaining({ value: 'night' })]]);
    });

    it('emits the whole state after each snapshot or delta', async () => {
        const server = fakeServer([
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'STATE_SNAPSHOT', snapshot: { waypoints: ['Papeete'] } },
            { type: 'STATE_DELTA', delta: [{ op: 'add', path: '/waypoints/-', value: 'Moorea' }] },
            { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
        ]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        const received = record(client, 'state-changed');

        await client.run();

        expect(received).toEqual([
            ['state-changed', { state: { waypoints: ['Papeete'] } }],
            ['state-changed', { state: { waypoints: ['Papeete', 'Moorea'] } }],
        ]);
    });

    it('emits the whole activity after each snapshot or delta', async () => {
        const server = fakeServer([
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'ACTIVITY_SNAPSHOT', messageId: 'a1', activityType: 'twig_component', content: { html: '<p>1</p>' } },
            { type: 'ACTIVITY_DELTA', messageId: 'a1', activityType: 'twig_component', patch: [{ op: 'replace', path: '/html', value: '<p>2</p>' }] },
            { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
        ]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        const received = record(client, 'activity-changed');

        await client.run();

        expect(received).toEqual([
            ['activity-changed', { messageId: 'a1', activityType: 'twig_component', content: { html: '<p>1</p>' } }],
            ['activity-changed', { messageId: 'a1', activityType: 'twig_component', content: { html: '<p>2</p>' } }],
        ]);
    });

    it('emits the messages once a run has changed them', async () => {
        const server = fakeServer([
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'TEXT_MESSAGE_CHUNK', messageId: 'm1', delta: 'Ahoy' },
            { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
        ]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        const received = record(client, 'messages-changed');

        await client.send('Hello');

        expect(received.at(-1)[1].messages).toMatchObject([
            { role: 'user', content: 'Hello' },
            { id: 'm1', role: 'assistant', content: 'Ahoy' },
        ]);
    });
});

describe('frontend tools', () => {
    const position = { description: "The boat's GPS position.", parameters: { type: 'object', properties: {} } };

    const callingTheTool = (name = 'get_boat_position', args = '{}') => [
        { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
        { type: 'TOOL_CALL_START', toolCallId: 'c1', toolCallName: name, parentMessageId: 'm1' },
        { type: 'TOOL_CALL_ARGS', toolCallId: 'c1', delta: args },
        { type: 'TOOL_CALL_END', toolCallId: 'c1' },
        { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
    ];
    const answering = [
        { type: 'RUN_STARTED', threadId: 't1', runId: 'r2' },
        { type: 'TEXT_MESSAGE_CHUNK', messageId: 'm2', delta: 'You are off Moorea.' },
        { type: 'RUN_FINISHED', threadId: 't1', runId: 'r2' },
    ];

    it('offers registered tools in every run agent input', async () => {
        const server = fakeServer([]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        client.registerTool('get_boat_position', position, () => ({}));

        await client.run();

        expect(server.requests[0].input.tools).toEqual([{ name: 'get_boat_position', ...position }]);
    });

    it('runs the tool the agent called, then starts a new run with its result', async () => {
        const server = fakeServer(callingTheTool('get_boat_position', '{"precise":true}'), answering);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        const calls = [];
        client.registerTool('get_boat_position', position, (args) => {
            calls.push(args);
            return { latitude: -17.5, longitude: -149.8 };
        });

        await client.send('Where are we?');

        expect(calls).toEqual([{ precise: true }]);
        expect(server.requests).toHaveLength(2);
        expect(server.requests[1].input.messages.at(-1)).toMatchObject({
            role: 'tool', toolCallId: 'c1', content: '{"latitude":-17.5,"longitude":-149.8}',
        });
        expect(client.messages.at(-1)).toMatchObject({ role: 'assistant', content: 'You are off Moorea.' });
    });

    it('sends back the error when the tool fails', async () => {
        const server = fakeServer(callingTheTool(), answering);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        client.registerTool('get_boat_position', position, async () => {
            throw new Error('Geolocation is not available.');
        });

        await client.send('Where are we?');

        expect(server.requests[1].input.messages.at(-1)).toMatchObject({
            role: 'tool', toolCallId: 'c1', content: '', error: 'Geolocation is not available.',
        });
    });

    it('leaves the tools it does not know to the server', async () => {
        const server = fakeServer(callingTheTool('search_marinas'));
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        client.registerTool('get_boat_position', position, () => ({}));

        await client.send('Find a marina');

        expect(server.requests).toHaveLength(1);
    });

    it('also offers the tools its provider declares, read before each run', async () => {
        const server = fakeServer(callingTheTool('get_visible_area'), answering);
        const declared = [{ name: 'get_visible_area', description: 'The area shown on the map.', handler: () => ({ zoom: 9 }) }];
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch, tools: () => declared });

        await client.send('What do I see?');

        expect(server.requests[0].input.tools).toEqual([{ name: 'get_visible_area', description: 'The area shown on the map.' }]);
        expect(server.requests[1].input.messages.at(-1)).toMatchObject({ role: 'tool', content: '{"zoom":9}' });
    });

    it('refuses two tools with the same name', async () => {
        const client = new AgUiClient({
            endpoint: '/agent',
            fetch: fakeServer([]).fetch,
            tools: () => [{ name: 'get_boat_position', handler: () => ({}) }],
        });
        client.registerTool('get_boat_position', position, () => ({}));

        await expect(client.run()).rejects.toThrow('get_boat_position');
    });
});

describe('interrupts', () => {
    const asking = (...ids) => [
        { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
        {
            type: 'RUN_FINISHED', threadId: 't1', runId: 'r1',
            outcome: { type: 'interrupt', interrupts: ids.map((id) => ({ id, reason: 'approval', message: 'Reserve the mooring?' })) },
        },
    ];
    const done = [
        { type: 'RUN_STARTED', threadId: 't1', runId: 'r2' },
        { type: 'RUN_FINISHED', threadId: 't1', runId: 'r2' },
    ];

    it('waits for the human once the run finishes on interrupts', async () => {
        const server = fakeServer(asking('i1'));
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });

        await client.send('Reserve at Atuona');

        expect(client.interrupts).toMatchObject([{ id: 'i1', reason: 'approval' }]);
        expect(server.requests).toHaveLength(1);
    });

    it('resumes in a new run once every interrupt is answered', async () => {
        const server = fakeServer(asking('i1', 'i2'), done);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        await client.send('Reserve at Atuona and Hanavave');

        await client.resume('i1', { payload: { approved: true } });
        expect(server.requests).toHaveLength(1);

        await client.resume('i2', { status: 'cancelled' });

        expect(server.requests).toHaveLength(2);
        expect(server.requests[1].input.resume).toEqual([
            { interruptId: 'i1', status: 'resolved', payload: { approved: true } },
            { interruptId: 'i2', status: 'cancelled' },
        ]);
        expect(client.interrupts).toEqual([]);
    });

    it('sends the resumes only once', async () => {
        const server = fakeServer(asking('i1'), done, done);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        await client.send('Reserve at Atuona');
        await client.resume('i1', { payload: { approved: true } });

        await client.send('Thanks');

        expect(server.requests[2].input.resume).toEqual([]);
    });

    it('refuses a resume for an interrupt it is not waiting on', async () => {
        const client = new AgUiClient({ endpoint: '/agent', fetch: fakeServer().fetch });

        await expect(client.resume('i9')).rejects.toThrow('i9');
    });

    it('refuses a new message while interrupts wait for an answer', async () => {
        const server = fakeServer(asking('i1'));
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        await client.send('Reserve at Atuona');

        await expect(client.send('Never mind')).rejects.toThrow('interrupt');
        expect(server.requests).toHaveLength(1);
    });
});

describe('stopping', () => {
    it('stops the run in progress and keeps what already arrived', async () => {
        const server = hangingServer(
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'TEXT_MESSAGE_START', messageId: 'm1', role: 'assistant' },
            { type: 'TEXT_MESSAGE_CONTENT', messageId: 'm1', delta: 'The passage to the Marquesas takes' },
        );
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        const started = new Promise((resolve) => client.addEventListener('text-message-content', resolve));
        const changed = [];
        client.addEventListener('messages-changed', ({ detail }) => changed.push(detail.messages.length));

        const running = client.send('How long to Nuku Hiva?');
        await started;
        client.stop();

        await expect(running).resolves.toBeUndefined();
        expect(client.messages.at(-1)).toMatchObject({ id: 'm1', content: 'The passage to the Marquesas takes' });
        expect(changed).toEqual([2]);
    });

    it('rejects when the server answers with an error status', async () => {
        const client = new AgUiClient({ endpoint: '/agent', fetch: async () => new Response('Boom', { status: 500 }) });

        await expect(client.send('Hello')).rejects.toThrow('500');
    });
});

describe('the shared state', () => {
    it('sends the state the application set, and tells listeners', async () => {
        const server = fakeServer([]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });
        const changed = [];
        client.addEventListener('state-changed', ({ detail }) => changed.push(detail.state));

        client.setState({ waypoints: ['Papeete', 'Fakarava'] });
        await client.run();

        expect(changed).toEqual([{ waypoints: ['Papeete', 'Fakarava'] }]);
        expect(server.requests[0].input.state).toEqual({ waypoints: ['Papeete', 'Fakarava'] });
    });
});

describe('the event stream', () => {
    it('reads AG-UI events from message frames only, and skips named ones', async () => {
        // What Symfony's EventStreamResponse sends in debug mode, before the run's events.
        const server = rawServer(
            'event: symfony:debug:started\ndata: 9ec815\ndata: https://127.0.0.1:8000/_profiler/9ec815\n\n'
            + 'data: {"type":"RUN_STARTED","threadId":"t1","runId":"r1"}\n\n'
            + 'data: {"type":"TEXT_MESSAGE_CHUNK","messageId":"m1","delta":"Ahoy"}\n\n'
            + 'event: message\ndata: {"type":"RUN_FINISHED","threadId":"t1","runId":"r1"}\n\n',
        );
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });

        await client.send('Hello');

        expect(client.messages.at(-1)).toMatchObject({ id: 'm1', content: 'Ahoy' });
    });

    it('reads frames whose lines end with CRLF', async () => {
        const server = rawServer(
            'data: {"type":"RUN_STARTED","threadId":"t1","runId":"r1"}\r\n\r\n'
            + 'data: {"type":"TEXT_MESSAGE_CHUNK","messageId":"m1","delta":"Ahoy"}\r\n\r\n'
            + 'data: {"type":"RUN_FINISHED","threadId":"t1","runId":"r1"}\r\n\r\n',
        );
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });

        await client.send('Hello');

        expect(client.messages.at(-1)).toMatchObject({ id: 'm1', content: 'Ahoy' });
    });

    it('reads a CRLF the network split in two', async () => {
        const server = rawServer([
            'data: {"type":"RUN_STARTED","threadId":"t1","runId":"r1"}\r',
            '\n\r\ndata: {"type":"TEXT_MESSAGE_CHUNK","messageId":"m1",',
            '"delta":"Ahoy"}\r\n\r',
            '\ndata: {"type":"RUN_FINISHED","threadId":"t1","runId":"r1"}\r\n\r\n',
        ]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });

        await client.send('Hello');

        expect(client.messages.at(-1)).toMatchObject({ id: 'm1', content: 'Ahoy' });
    });

    it('keeps a frame of several data lines whole when a CRLF inside it is split', async () => {
        const server = rawServer([
            'data: {"type":"RUN_STARTED","threadId":"t1","runId":"r1"}\r\n\r\n',
            'data: {"type":"TEXT_MESSAGE_CHUNK",\r',
            '\ndata: "messageId":"m1","delta":"Ahoy"}\r\n\r\n',
            'data: {"type":"RUN_FINISHED","threadId":"t1","runId":"r1"}\r\n\r\n',
        ]);
        const client = new AgUiClient({ endpoint: '/agent', fetch: server.fetch });

        await client.send('Hello');

        expect(client.messages.at(-1)).toMatchObject({ id: 'm1', content: 'Ahoy' });
    });
});
