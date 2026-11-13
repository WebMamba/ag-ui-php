import { Controller } from '@hotwired/stimulus';
import { AgUiClient } from './client.js';

/**
 * The client controller: creates a client for its element and dispatches each of its
 * events as an `ag-ui:`-prefixed DOM event (ADR 0008).
 */
export default class extends Controller {
    static targets = ['input'];
    static values = { endpoint: String, threadId: String };

    connect() {
        this.client = new AgUiClient({
            endpoint: this.endpointValue,
            ...(this.hasThreadIdValue && { threadId: this.threadIdValue }),
            tools: () => this.declaredTools(),
            onEmit: (name, detail) => this.dispatch(name, { prefix: 'ag-ui', detail }),
        });

        this.dispatch('connect', { prefix: 'ag-ui', detail: { client: this.client } });
    }

    /**
     * Sends a user message: the `message` param, or the `message` of the event's detail —
     * an application event saying what the user did on the page — or else the input,
     * which is then emptied.
     */
    async send(event) {
        event?.preventDefault();
        const given = event?.params?.message ?? event?.detail?.message;
        const content = (given ?? this.inputTarget.value).trim();
        if (content === '') return;

        if (given === undefined && this.hasInputTarget) this.inputTarget.value = '';
        await this.busy(() => this.client.send(content));
    }

    disconnect() {
        this.client.stop();
    }

    /**
     * Answers an interrupt: the one named by the `interruptId` param, or the one of the
     * closest `[data-ag-ui-interrupt-id]`. A `status` param may cancel it; the other
     * params are the payload.
     */
    async resume(event) {
        const { interruptId = event.target.closest('[data-ag-ui-interrupt-id]')?.dataset.agUiInterruptId, status, ...payload } = event.params ?? {};
        if (!interruptId) throw new Error('ag-ui#resume needs an interrupt: put it in a [data-ag-ui-interrupt-id] or give it an interruptId param.');

        await this.busy(() => this.client.resume(interruptId, {
            ...(status && { status }),
            ...(Object.keys(payload).length > 0 && { payload }),
        }));
    }

    /** The application changed the shared state: the event's detail is the new state. */
    setState(event) {
        this.client.setState(event.detail);
    }

    stop() {
        this.client.stop();
    }

    /**
     * The frontend tools the page declares with `data-ag-ui-tool`, read before each run:
     * a call dispatches `ag-ui:tool-call:<name>` on the element, answered with `detail.respond()`.
     */
    declaredTools() {
        return Array.from(this.element.querySelectorAll('[data-ag-ui-tool]'), (element) => {
            const { agUiTool: name, agUiToolDescription: description, agUiToolParameters: parameters } = element.dataset;

            return {
                name,
                description,
                parameters: parameters === undefined ? undefined : JSON.parse(parameters),
                handler: (args) => {
                    let answer = null;
                    this.dispatch(`tool-call:${name}`, {
                        target: element,
                        prefix: 'ag-ui',
                        detail: { args, respond: (result) => { answer = { result }; } },
                    });
                    if (answer === null) {
                        throw new Error(`Nothing on the page answered the frontend tool "${name}".`);
                    }
                    return answer.result;
                },
            };
        });
    }

    /**
     * `aria-busy` on the element while the agent runs, for CSS to react without JavaScript;
     * `ag-ui:error` if it failed, then `ag-ui:idle` once it is over.
     */
    async busy(run) {
        this.element.setAttribute('aria-busy', 'true');
        try {
            await run();
        } catch (error) {
            this.dispatch('error', { prefix: 'ag-ui', detail: { message: error?.message ?? String(error), error } });
        } finally {
            this.element.removeAttribute('aria-busy');
            this.dispatch('idle', { prefix: 'ag-ui' });
        }
    }
}
