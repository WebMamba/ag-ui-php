// @vitest-environment jsdom
import { Application, Controller } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import ClientController from '../src/client_controller.js';
import { fakeServer, hangingServer } from './sse.js';

const TEXT = [
    { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
    { type: 'TEXT_MESSAGE_CHUNK', messageId: 'm1', delta: 'Fair winds.' },
    { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
];

let application;

/** Mounts the page with a real Stimulus application; the server is the only fake. */
async function mount(html, server, controllers = {}) {
    vi.stubGlobal('fetch', server.fetch);
    document.body.innerHTML = html;
    application = Application.start();
    application.register('ag-ui', ClientController);
    for (const [name, controller] of Object.entries(controllers)) application.register(name, controller);
    await tick();
}

const tick = () => new Promise((resolve) => setTimeout(resolve, 0));

/** Resolves with the detail of the next `name` event on the document. */
const next = (name) => new Promise((resolve) => document.addEventListener(name, ({ detail }) => resolve(detail), { once: true }));

const CHAT = `
    <div data-controller="ag-ui" data-ag-ui-endpoint-value="/agent">
        <form data-action="submit->ag-ui#send">
            <textarea data-ag-ui-target="input"></textarea>
        </form>
    </div>`;

const submit = (text) => {
    document.querySelector('textarea').value = text;
    document.querySelector('form').requestSubmit();
};

afterEach(() => {
    application?.stop();
    vi.unstubAllGlobals();
    document.body.innerHTML = '';
});

describe('the ag-ui controller', () => {
    it('sends the typed message to its endpoint, and empties the input', async () => {
        const server = fakeServer(TEXT);
        await mount(CHAT, server);
        const finished = next('ag-ui:run-finished');

        submit('Weather in Papeete?');
        await finished;

        expect(server.requests[0]).toMatchObject({ url: '/agent', input: { messages: [{ role: 'user', content: 'Weather in Papeete?' }] } });
        expect(document.querySelector('textarea').value).toBe('');
    });

    it('runs the thread its threadId value names, when the page gives one', async () => {
        const server = fakeServer(TEXT);
        await mount(CHAT.replace('data-ag-ui-endpoint-value="/agent"', 'data-ag-ui-endpoint-value="/agent" data-ag-ui-thread-id-value="passage-42"'), server);
        const finished = next('ag-ui:run-finished');

        submit('Weather in Papeete?');
        await finished;

        expect(server.requests[0].input.threadId).toBe('passage-42');
    });

    it('dispatches every event of the stream as an ag-ui: DOM event', async () => {
        await mount(CHAT, fakeServer(TEXT));
        const content = next('ag-ui:text-message-content');
        const messages = next('ag-ui:messages-changed');

        submit('Weather in Papeete?');

        expect(await content).toMatchObject({ messageId: 'm1', delta: 'Fair winds.' });
        expect((await messages).messages.at(-1)).toMatchObject({ role: 'assistant', content: 'Fair winds.' });
    });
});

describe('while a run is in progress', () => {
    it('sends the message an application event carries, leaving the input alone', async () => {
        const server = fakeServer(TEXT);
        await mount(CHAT.replace('data-controller="ag-ui"', 'data-controller="ag-ui" data-action="passage:edited->ag-ui#send"'), server);
        const finished = next('ag-ui:run-finished');
        document.querySelector('textarea').value = 'half-typed';

        document.querySelector('[data-controller="ag-ui"]').dispatchEvent(new CustomEvent('passage:edited', { detail: { message: 'I removed Fakarava from the plan.' } }));
        await finished;

        expect(server.requests[0].input.messages).toEqual([expect.objectContaining({ role: 'user', content: 'I removed Fakarava from the plan.' })]);
        expect(document.querySelector('textarea').value).toBe('half-typed');
    });

    it('marks its element busy', async () => {
        await mount(CHAT, fakeServer(TEXT));
        const element = document.querySelector('[data-controller="ag-ui"]');
        const busyDuringTheRun = next('ag-ui:run-started').then(() => element.getAttribute('aria-busy'));
        const finished = next('ag-ui:messages-changed');

        submit('Weather in Papeete?');

        expect(await busyDuringTheRun).toBe('true');
        await finished;
        await tick();
        expect(element.hasAttribute('aria-busy')).toBe(false);
    });
});

describe('once a run is over', () => {
    it('tells the page it is idle again', async () => {
        await mount(CHAT, fakeServer(TEXT));
        const idle = next('ag-ui:idle');

        submit('Weather in Papeete?');

        await expect(idle).resolves.toEqual({});
    });

    it('reports what failed, instead of rejecting into the void', async () => {
        await mount(CHAT, { fetch: async () => new Response('Boom', { status: 500 }), requests: [] });
        const error = next('ag-ui:error');
        const idle = next('ag-ui:idle');

        submit('Weather in Papeete?');

        expect((await error).message).toContain('500');
        await idle;
    });
});

describe('frontend tools declared in JavaScript', () => {
    it('hands its client over on connect, to register them', async () => {
        const server = fakeServer([
            { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
            { type: 'TOOL_CALL_START', toolCallId: 'c1', toolCallName: 'get_boat_position', parentMessageId: 'm1' },
            { type: 'TOOL_CALL_END', toolCallId: 'c1' },
            { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
        ], TEXT);
        document.addEventListener('ag-ui:connect', ({ detail: { client } }) => {
            client.registerTool('get_boat_position', { description: "The boat's position." }, () => ({ latitude: -17.5 }));
        }, { once: true });
        await mount(CHAT, server);
        const answered = next('ag-ui:text-message-content');

        submit('Where are we?');
        await answered;

        expect(server.requests[0].input.tools).toMatchObject([{ name: 'get_boat_position' }]);
        expect(server.requests[1].input.messages.at(-1)).toMatchObject({ role: 'tool', content: '{"latitude":-17.5}' });
    });
});

describe('frontend tools declared by an element', () => {
    const callingTheMap = [
        { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
        { type: 'TOOL_CALL_START', toolCallId: 'c1', toolCallName: 'get_visible_area', parentMessageId: 'm1' },
        { type: 'TOOL_CALL_ARGS', toolCallId: 'c1', delta: '{"precise":true}' },
        { type: 'TOOL_CALL_END', toolCallId: 'c1' },
        { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
    ];

    const page = (map) => `
        <div data-controller="ag-ui" data-ag-ui-endpoint-value="/agent">
            <form data-action="submit->ag-ui#send"><textarea data-ag-ui-target="input"></textarea></form>
            ${map}
        </div>`;

    const MAP = `
        <div data-controller="map"
             data-ag-ui-tool="get_visible_area"
             data-ag-ui-tool-description="The area shown on the map."
             data-ag-ui-tool-parameters='{"type":"object","properties":{"precise":{"type":"boolean"}}}'
             data-action="ag-ui:tool-call:get_visible_area->map#area"></div>`;

    class MapController extends Controller {
        area({ detail: { args, respond } }) {
            respond(Promise.resolve({ zoom: 9, precise: args.precise }));
        }
    }

    it('offers the tool while its element is on the page, and answers through it', async () => {
        const server = fakeServer(callingTheMap, TEXT);
        await mount(page(MAP), server, { map: MapController });
        const answered = next('ag-ui:text-message-content');

        submit('What do I see?');
        await answered;

        expect(server.requests[0].input.tools).toEqual([{
            name: 'get_visible_area',
            description: 'The area shown on the map.',
            parameters: { type: 'object', properties: { precise: { type: 'boolean' } } },
        }]);
        expect(server.requests[1].input.messages.at(-1)).toMatchObject({ role: 'tool', toolCallId: 'c1', content: '{"zoom":9,"precise":true}' });
    });

    it('does not offer it once the element is gone', async () => {
        const server = fakeServer(TEXT);
        await mount(page(''), server);
        const finished = next('ag-ui:run-finished');

        submit('What do I see?');
        await finished;

        expect(server.requests[0].input.tools).toEqual([]);
    });

    it('answers with an error when nothing on the page responds', async () => {
        const server = fakeServer(callingTheMap, TEXT);
        await mount(page(MAP.replace(' data-action="ag-ui:tool-call:get_visible_area->map#area"', '')), server);
        const answered = next('ag-ui:text-message-content');

        submit('What do I see?');
        await answered;

        expect(server.requests[1].input.messages.at(-1)).toMatchObject({ role: 'tool', toolCallId: 'c1', error: expect.stringContaining('get_visible_area') });
    });
});

describe('the actions', () => {
    const asking = [
        { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
        { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1', outcome: { type: 'interrupt', interrupts: [{ id: 'i1', reason: 'approval' }] } },
    ];

    it('resumes the interrupt of the element it sits in, with its params as the payload', async () => {
        const server = fakeServer(asking, TEXT);
        await mount(CHAT.replace('</form>', `</form>
            <div data-ag-ui-interrupt-id="i1">
                <button id="confirm" data-action="ag-ui#resume" data-ag-ui-approved-param="true">Confirm</button>
            </div>`), server);
        const interrupted = next('ag-ui:run-finished');
        submit('Reserve at Atuona');
        await interrupted;
        await tick();
        const resumed = next('ag-ui:text-message-content');

        document.querySelector('#confirm').click();
        await resumed;

        expect(server.requests[1].input.resume).toEqual([{ interruptId: 'i1', status: 'resolved', payload: { approved: true } }]);
    });

    it('cancels an interrupt named by its params', async () => {
        const server = fakeServer(asking, TEXT);
        await mount(CHAT.replace('</form>', `</form>
            <button id="decline" data-action="ag-ui#resume" data-ag-ui-interrupt-id-param="i1" data-ag-ui-status-param="cancelled">No</button>`), server);
        const interrupted = next('ag-ui:run-finished');
        submit('Reserve at Atuona');
        await interrupted;
        await tick();
        const resumed = next('ag-ui:text-message-content');

        document.querySelector('#decline').click();
        await resumed;

        expect(server.requests[1].input.resume).toEqual([{ interruptId: 'i1', status: 'cancelled' }]);
    });

    it('sets the shared state from the event it reacts to', async () => {
        const server = fakeServer(TEXT);
        await mount(CHAT.replace('data-controller="ag-ui"', 'data-controller="ag-ui" data-action="passage:changed->ag-ui#setState"'), server);
        const finished = next('ag-ui:run-finished');

        document.querySelector('[data-controller="ag-ui"]').dispatchEvent(new CustomEvent('passage:changed', { detail: { waypoints: ['Fakarava'] } }));
        submit('Is it safe?');
        await finished;

        expect(server.requests[0].input.state).toEqual({ waypoints: ['Fakarava'] });
    });

    it('stops the run in progress', async () => {
        const server = hangingServer({ type: 'RUN_STARTED', threadId: 't1', runId: 'r1' });
        await mount(CHAT.replace('</form>', '</form><button id="stop" data-action="ag-ui#stop">Stop</button>'), server);
        const started = next('ag-ui:run-started');
        submit('How long to Nuku Hiva?');
        await started;
        const stopped = next('ag-ui:messages-changed');

        document.querySelector('#stop').click();
        await stopped;
        await tick();

        expect(document.querySelector('[data-controller="ag-ui"]').hasAttribute('aria-busy')).toBe(false);
    });

    it('stops the run when its element leaves the page', async () => {
        const server = hangingServer({ type: 'RUN_STARTED', threadId: 't1', runId: 'r1' });
        await mount(CHAT, server);
        const started = next('ag-ui:run-started');
        submit('How long to Nuku Hiva?');
        await started;
        const stopped = new Promise((resolve) => document.querySelector('[data-controller="ag-ui"]')
            .addEventListener('ag-ui:messages-changed', resolve, { once: true }));

        document.querySelector('[data-controller="ag-ui"]').remove();

        await expect(stopped).resolves.toBeDefined();
    });
});
