# AG-UI for PHP

A server-side PHP implementation of the AG-UI protocol, with bridges for Symfony and
Symfony AI: it lets a PHP application expose an agent to any AG-UI client.

## Language

### The protocol

**AG-UI endpoint**:
The HTTP route that speaks AG-UI: it receives a run agent input and answers with an
event stream.
_Avoid_: agent (in the protocol sense), server agent, AG-UI agent

**Thread**:
One conversation between a user and an AG-UI endpoint, spanning any number of runs.
_Avoid_: conversation id, session, chat

**Run**:
One request to an AG-UI endpoint and the event stream it answers with.
_Avoid_: turn, request, execution

**Run agent input**:
What the client sends to start a run: the thread's messages, the frontend tools, the
shared state, context, and any resumes.
_Avoid_: payload, request body

**Event**:
One typed message of an event stream, as defined by the protocol.
_Avoid_: frame, chunk (a chunk is a specific kind of event), message

**Event stream**:
The ordered events of one run, from `RUN_STARTED` to `RUN_FINISHED` or `RUN_ERROR`.
_Avoid_: SSE stream, response

**Chunk**:
A convenience event that stands for the start, content and end of a text message, tool
call or reasoning message at once; the client expands it into those events.
_Avoid_: delta (a delta is the content a chunk carries), fragment

**Subagent**:
An agent invoked by another one within the same run; the events and messages it
produces are attributed to its invocation.
_Avoid_: child agent, sub-run, nested agent

**Shared state**:
The application state both the client and the agent read and write, sent in every run
agent input and updated by state events.
_Avoid_: UI state, store

### Tools and pauses

**Frontend tool**:
A tool the client declares in the run agent input and executes itself; the run stops
when the agent calls it, and its result comes back in the next run.
_Avoid_: client tool, browser tool

**Interrupt**:
A pause the agent asks for when it needs a human decision; the run finishes with an
interrupt outcome instead of success.
_Avoid_: confirmation, approval step

**Resume**:
The human's answer to an interrupt, sent in the next run agent input.
_Avoid_: reply, approval

### Symfony AI

**Agent**:
The Symfony AI agent: the object that calls the model and runs the tool loop, with its
platform and model. Declared under `ai.agent`; the route of an AG-UI endpoint picks the
one it runs, by name.
_Avoid_: bot, assistant (the assistant is a message role)

**Agent context**:
The facts the server states to the agent at the start of every run, such as the
interface's current theme; read again after each tool, and the ones that changed are
added to that tool's result. Declared with `#[AsAgentContext]`. Not the client's
`context` from the run agent input, which the client states.
_Avoid_: instructions, interface settings, UI state

**Tool result mapping**:
The application-specific rule that turns a tool result into extra events, on top of
the ones the protocol requires.
_Avoid_: tool result handler, renderer

**AgUi**:
The bridge's service that runs an agent over AG-UI: it takes a run agent input and the
agent the route picked, runs it, and produces the run's events. The agent itself is not
changed. One service for every agent.
_Avoid_: AG-UI agent, decorated agent, AG-UI wrapper

**Prepared agent**:
An agent whose frontend toolbox, tool executor and instructions processor are wired in
— the bundle does it for every agent under `ai.agent`. They act only while AgUi runs
that agent: called anywhere else, it is unchanged.
_Avoid_: AG-UI agent, wired agent, exposed agent

**Run context**:
What one run knows about itself — thread id, run id, forwarded props — readable by tools
without touching the request.
_Avoid_: request context, run info

### Interrupts

**Resumable tool**:
A tool that may answer with an interrupt instead of a result, and turns the human's
resume into its result in the next run.
_Avoid_: interruptible tool, approval tool

### Shared state, typed

**State class**:
The application's PHP class that holds a route's shared state; the route hands the
instance to AgUi. Tools inject it like any service; the bridge fills it from the run agent input before the run, diffs it after
each tool into state deltas, and resets it after the run.
_Avoid_: state service, store, state DTO

**State description**:
The text the model reads about the shared state: the state as JSON, unless the state
class describes itself.
_Avoid_: state prompt, state summary

### Generative UI

**Activity**:
A tool result with two audiences: a sentence the model reads, and content the client
renders as an activity message.
_Avoid_: rendering, UI result, widget

**Agent component**:
A Twig component exposed to the model as a tool: its public properties are the tool's
parameters, and calling it renders the component as an activity.
_Avoid_: render tool, UI tool

### The browser client

**Client**:
The bundle's JavaScript AG-UI client (`AgUiClient`): it sends each run agent input,
reads the event stream, keeps the thread's messages and shared state, and runs the
frontend tool and resume loop. It renders nothing.
_Avoid_: agent (in the browser), SDK, chat controller

**Client controller**:
The `ag-ui` Stimulus controller: it creates a client for its element and re-dispatches
every client event as a DOM event, so the application reacts with plain `data-action`s.
_Avoid_: chat controller, agent controller

**DOM event**:
The `ag-ui:`-prefixed browser event the client controller dispatches for an event of the
stream (`ag-ui:tool-call-start`), for a `CUSTOM` event by name (`ag-ui:custom:<name>`),
or for a change the client derived (`ag-ui:state-changed`).
_Avoid_: Stimulus event, hook, callback

**Activity slot**:
An element marked `data-ag-ui-activity="<messageId>"` where the activities controller
puts that activity's HTML, so it shows at its place in the conversation; without one,
the activity is appended to the controller's element.
_Avoid_: placeholder, container, outlet

## Relationships

- An **AG-UI endpoint** runs one **prepared agent** through **AgUi**, with at most one
  **state class**; another endpoint may run another agent, on another platform or model.
- A **run** of **AgUi** turns one **run agent input** into one **event stream**.
- A **resumable tool** answers an **interrupt** with a result once its **resume** arrives,
  in the next **run** of the same **thread**.
- An **agent component** is a **tool** whose result is always an **activity**.
- A **tool result mapping** adds events after any tool result; **activities**,
  **interrupts** and **state class** changes need none.
- A **client controller** owns one **client**, which runs one **thread**; every **event**
  of its **runs** becomes a **DOM event**.
- A **frontend tool** is registered on the **client** in JavaScript, or declared by an
  element of the page and offered only while that element is there.
- An **agent component**'s **activity** is put in its **activity slot** by the
  activities controller, which must sit inside the **client controller**'s element.
