// @vitest-environment jsdom
import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import ActivitiesController from '../src/activities_controller.js';
import ClientController from '../src/client_controller.js';
import { fakeServer } from './sse.js';

let application;

async function mount(html, ...runs) {
    const server = fakeServer(...runs);
    vi.stubGlobal('fetch', server.fetch);
    document.body.innerHTML = html;
    application = Application.start();
    application.register('ag-ui', ClientController);
    application.register('ag-ui-activities', ActivitiesController);
    await tick();
    return server;
}

const tick = () => new Promise((resolve) => setTimeout(resolve, 0));

/** Sends a message and waits until the run's events are all applied. */
async function chat(text) {
    const done = new Promise((resolve) => document.addEventListener('ag-ui:messages-changed', resolve, { once: true }));
    document.querySelector('textarea').value = text;
    document.querySelector('form').requestSubmit();
    await done;
}

const page = (inside = '') => `
    <div data-controller="ag-ui" data-ag-ui-endpoint-value="/agent">
        <div id="messages">${inside}</div>
        <div id="activities" data-controller="ag-ui-activities"></div>
        <form data-action="submit->ag-ui#send"><textarea data-ag-ui-target="input"></textarea></form>
    </div>`;

const run = (...events) => [
    { type: 'RUN_STARTED', threadId: 't1', runId: 'r1' },
    ...events,
    { type: 'RUN_FINISHED', threadId: 't1', runId: 'r1' },
];

const mooring = (html, content = {}) => ({
    type: 'ACTIVITY_SNAPSHOT', messageId: 'act_c1', activityType: 'twig_component',
    content: { component: 'MooringReservation', props: {}, html, ...content },
});

afterEach(() => {
    application?.stop();
    vi.unstubAllGlobals();
    document.body.innerHTML = '';
});

describe('the ag-ui-activities controller', () => {
    it('shows a rendered component in its element', async () => {
        await mount(page(), run(mooring('<p class="card">⚓ Atuona</p>')));

        await chat('Reserve at Atuona');

        expect(document.querySelector('#activities [data-ag-ui-activity="act_c1"] .card').textContent).toBe('⚓ Atuona');
    });

    it('fills the activity slot the conversation laid out for it instead', async () => {
        await mount(page('<div data-ag-ui-activity="act_c1"></div>'), run(mooring('<p class="card">⚓ Atuona</p>')));

        await chat('Reserve at Atuona');

        expect(document.querySelector('#messages [data-ag-ui-activity="act_c1"] .card')).not.toBeNull();
        expect(document.querySelector('#activities').children).toHaveLength(0);
    });

    it('re-renders the component in place when the activity changes', async () => {
        await mount(page(), run(
            mooring('<p class="card">⚓ Atuona</p>'),
            { type: 'ACTIVITY_DELTA', messageId: 'act_c1', activityType: 'twig_component', patch: [{ op: 'replace', path: '/html', value: '<p class="card">⚓ Atuona, confirmed</p>' }] },
        ));

        await chat('Reserve at Atuona');

        expect(Array.from(document.querySelectorAll('[data-ag-ui-activity="act_c1"] .card'), (card) => card.textContent)).toEqual(['⚓ Atuona, confirmed']);
    });

    it('marks the component of an interrupt, for ag-ui#resume to find', async () => {
        await mount(page(), run(mooring('<p class="card">⚓ Atuona</p>', { interruptId: 'int_c1' })));

        await chat('Reserve at Atuona');

        expect(document.querySelector('[data-ag-ui-activity="act_c1"]').dataset.agUiInterruptId).toBe('int_c1');
    });

    it('leaves the other kinds of activity to the application', async () => {
        await mount(page(), run({ type: 'ACTIVITY_SNAPSHOT', messageId: 'a1', activityType: 'web_search', content: { query: 'marinas' } }));

        await chat('Search marinas');

        expect(document.querySelector('#activities').children).toHaveLength(0);
    });
});
