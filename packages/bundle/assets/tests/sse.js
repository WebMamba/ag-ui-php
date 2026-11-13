/**
 * The network edge, faked: a `fetch` that answers each run with the given events
 * as an SSE stream, and records every run agent input it received.
 */
export function fakeServer(...runs) {
    const requests = [];
    const fetch = async (url, init) => {
        requests.push({ url, input: JSON.parse(init.body) });
        const events = runs.shift() ?? [];
        const encoder = new TextEncoder();
        const body = new ReadableStream({
            start(controller) {
                for (const event of events) {
                    controller.enqueue(encoder.encode(`data: ${JSON.stringify(event)}\n\n`));
                }
                controller.close();
            },
        });
        return new Response(body, { status: 200, headers: { 'Content-Type': 'text/event-stream' } });
    };
    return { fetch, requests };
}

/**
 * A server that sends the given events and then keeps the stream open, the way a real
 * `fetch` does: aborting the request errors the body.
 */
export function hangingServer(...events) {
    const requests = [];
    const fetch = async (url, init) => {
        requests.push({ url, input: JSON.parse(init.body) });
        const encoder = new TextEncoder();
        const body = new ReadableStream({
            start(controller) {
                for (const event of events) controller.enqueue(encoder.encode(`data: ${JSON.stringify(event)}\n\n`));
                init.signal?.addEventListener('abort', () => controller.error(new DOMException('The run was stopped.', 'AbortError')));
            },
        });
        return new Response(body, { status: 200 });
    };
    return { fetch, requests };
}

/** A server answering with this exact SSE text — in these network chunks, if a list. */
export function rawServer(text) {
    const requests = [];
    const fetch = async (url, init) => {
        requests.push({ url, input: JSON.parse(init.body) });
        const encoder = new TextEncoder();
        const body = new ReadableStream({
            start(controller) {
                for (const chunk of [text].flat()) controller.enqueue(encoder.encode(chunk));
                controller.close();
            },
        });
        return new Response(body, { status: 200 });
    };
    return { fetch, requests };
}
