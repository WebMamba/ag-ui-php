<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests;

use AgUi\Bundle\Tests\App\Cart;
use AgUi\Bundle\Tests\App\Context\ShopHours;
use AgUi\Bundle\Tests\App\Kernel;
use AgUi\Bundle\Tests\App\ScriptedPlatform;
use AgUi\Run\RunAgentInput;
use AgUi\SymfonyAi\Context\AgentContext;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\Stream\Delta\TextDelta;
use Symfony\AI\Platform\Result\Stream\Delta\ToolCallComplete;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * An application that only configured its Symfony AI agents, and routes that pick one:
 * everything AG-UI below comes from the bundle.
 */
final class AgUiBundleTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        ScriptedPlatform::$rounds = [];
        ScriptedPlatform::$inputs = [];
        ScriptedPlatform::$tools = [];
        ShopHours::$status = 'open';
    }

    public function testTheAgentContextIsStatedAndItsChangesReported(): void
    {
        $client = self::createClient();
        ScriptedPlatform::$rounds = [[new ToolCallComplete([new ToolCall('c1', 'close_shop', [])])], [new TextDelta('Closed.')]];

        $events = $this->post($client, ['threadId' => 't1', 'runId' => 'r1', 'messages' => [['id' => 'u1', 'role' => 'user', 'content' => 'Close']]]);

        self::assertNotSame('RUN_ERROR', end($events)['type'], end($events)['message'] ?? '');
        $firstRound = json_encode(ScriptedPlatform::$inputs[0], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
        self::assertStringContainsString('Current facts from the application:', $firstRound);
        self::assertStringContainsString('Shop: open', $firstRound, 'On an invokable class, for every agent.');
        self::assertStringContainsString('Customer: Moana', $firstRound, 'On a method, for this agent.');
        self::assertStringNotContainsString('Coupon', $firstRound, 'A null fact is left out.');
        self::assertStringNotContainsString('Warehouse', $firstRound, 'Another agent\'s fact is not stated.');

        self::assertSame("Closed.\n\n[context changed] Shop: closed", self::first($events, 'TOOL_CALL_RESULT')['content']);
    }

    public function testARouteStreamsTheAnswerOfTheAgentItPicked(): void
    {
        $client = self::createClient();
        ScriptedPlatform::$rounds = [[new TextDelta('Hello '), new TextDelta('sailor.')]];

        $events = $this->post($client, ['threadId' => 't1', 'runId' => 'r1', 'messages' => [['id' => 'u1', 'role' => 'user', 'content' => 'Hi']]]);

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('text/event-stream', (string) $client->getResponse()->headers->get('Content-Type'));
        self::assertSame(['RUN_STARTED', 'STATE_SNAPSHOT', 'TEXT_MESSAGE_START', 'TEXT_MESSAGE_CONTENT', 'TEXT_MESSAGE_CONTENT', 'TEXT_MESSAGE_END', 'MESSAGES_SNAPSHOT', 'RUN_FINISHED'], array_column($events, 'type'));
    }

    public function testTheAgentsToolsChangeTheStateClassInjectedAsItself(): void
    {
        $client = self::createClient();
        ScriptedPlatform::$rounds = [[new ToolCallComplete([new ToolCall('c1', 'add_to_cart', ['sku' => 'B7'])])], [new TextDelta('Added.')]];

        $events = $this->post($client, ['threadId' => 't1', 'runId' => 'r1', 'messages' => [['id' => 'u1', 'role' => 'user', 'content' => 'Add B7']], 'state' => ['skus' => ['A1']]]);

        self::assertNotSame('RUN_ERROR', end($events)['type'], end($events)['message'] ?? '');
        self::assertSame('Added.', self::first($events, 'TOOL_CALL_RESULT')['content']);
        self::assertSame([['op' => 'add', 'path' => '/skus/-', 'value' => 'B7']], self::first($events, 'STATE_DELTA')['delta']);
        self::assertSame([], self::getContainer()->get(Cart::class)->skus, 'The state is reset after the run.');
    }

    public function testATwigComponentIsAToolThatRendersAnActivity(): void
    {
        $client = self::createClient();
        ScriptedPlatform::$rounds = [[new ToolCallComplete([new ToolCall('c2', 'render_map', ['place' => 'Papeete', 'markers' => [['title' => 'Marina Taina', 'lat' => -17.58]]])])], [new TextDelta('There.')]];

        $events = $this->post($client, ['threadId' => 't1', 'runId' => 'r1', 'messages' => [['id' => 'u1', 'role' => 'user', 'content' => 'Show Papeete']]]);

        $activity = self::first($events, 'ACTIVITY_SNAPSHOT');
        self::assertSame('twig_component', $activity['activityType']);
        self::assertSame('Map', $activity['content']['component']);
        self::assertStringContainsString('<div class="map" data-zoom="8">Papeete<b>Marina Taina</b></div>', $activity['content']['html'], 'Props are denormalized to the types the component declares.');
        self::assertSame('Displayed the Map component to the user.', self::first($events, 'TOOL_CALL_RESULT')['content']);

        $tools = json_encode(ScriptedPlatform::$inputs, \JSON_THROW_ON_ERROR);
        self::assertStringContainsString('render_map', $tools);
    }

    public function testTheComponentsToolExposesItsPublicPropertiesButNotTheIgnoredOnes(): void
    {
        self::bootKernel();
        $tools = self::getContainer()->get('ag_ui.agent_components')->getTools();

        self::assertCount(1, $tools);
        self::assertSame('render_map', $tools[0]->getName());
        self::assertSame(['place', 'zoom', 'markers'], array_keys($tools[0]->getParameters()['properties'] ?? []));
        self::assertSame(['place'], $tools[0]->getParameters()['required'] ?? null);
    }

    public function testTheProfilerStillTracesTheAgentsToolboxWithOurToolsInside(): void
    {
        self::bootKernel(['debug' => true]);
        $toolbox = self::getContainer()->get('ai.toolbox.shop');
        self::getContainer()->get('ag_ui.run_scope')->open(RunAgentInput::fromArray(['threadId' => 't', 'runId' => 'r', 'messages' => []]), [], agent: 'shop');

        self::assertInstanceOf(\Symfony\AI\Agent\Toolbox\TraceableToolbox::class, $toolbox, 'The AI profiler requires the traceable toolbox outermost.');
        self::assertContains('render_map', array_map(static fn ($tool): string => $tool->getName(), $toolbox->getTools()));
    }

    public function testAnotherRouteRunsAnotherAgentWithItsOwnFacts(): void
    {
        $client = self::createClient();
        ScriptedPlatform::$rounds = [[new TextDelta('How can I help?')]];

        $events = $this->post($client, ['threadId' => 't1', 'runId' => 'r1', 'messages' => [['id' => 'u1', 'role' => 'user', 'content' => 'Hi']]], '/agent/support');

        self::assertSame('RUN_FINISHED', end($events)['type'], end($events)['message'] ?? '');
        self::assertNotContains('STATE_SNAPSHOT', array_column($events, 'type'), 'This route shares no state.');
        $firstRound = json_encode(ScriptedPlatform::$inputs[0], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
        self::assertStringContainsString('You are the support desk.', $firstRound);
        self::assertStringContainsString('Warehouse: Papeete', $firstRound);
        self::assertStringNotContainsString('Customer', $firstRound, 'The shop agent\'s fact is not stated.');
        self::assertSame(['render_map'], ScriptedPlatform::$tools[0], 'An agent without tools of its own still gets the agent components.');
    }

    public function testAnAgentCalledOutsideAgUiIsUnchanged(): void
    {
        self::bootKernel();
        ScriptedPlatform::$rounds = [[new TextDelta('Hello.')]];

        $agent = self::getContainer()->get('ai.agent.shop');
        \assert($agent instanceof AgentInterface);
        iterator_to_array($agent->call(new MessageBag(Message::ofUser('Hi')), ['stream' => true]), false);

        self::assertContains('add_to_cart', ScriptedPlatform::$tools[0], 'It keeps its own tools…');
        self::assertNotContains('render_map', ScriptedPlatform::$tools[0], '…but is offered no agent component…');
        self::assertStringNotContainsString(AgentContext::HEADER, json_encode(ScriptedPlatform::$inputs[0], \JSON_THROW_ON_ERROR), '…and told no AG-UI context.');
    }

    public function testABodyThatIsNotARunAgentInputIsRejected(): void
    {
        $client = self::createClient();
        $client->request('POST', '/agent/shop', server: ['CONTENT_TYPE' => 'application/json'], content: '{"messages": []}');

        self::assertResponseStatusCodeSame(422);
    }

    public function testAStateThatDoesNotFitTheStateClassIsRejected(): void
    {
        $client = self::createClient();
        $client->request('POST', '/agent/shop', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['threadId' => 't', 'runId' => 'r', 'messages' => [], 'state' => ['skus' => 'not a list']], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return list<array<string, mixed>>
     */
    private function post(KernelBrowser $client, array $input, string $route = '/agent/shop'): array
    {
        ob_start();
        $client->request('POST', $route, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($input, \JSON_THROW_ON_ERROR));
        $body = ob_get_clean().$client->getInternalResponse()->getContent();

        $events = [];
        foreach (explode("\n\n", trim($body)) as $frame) {
            if (str_starts_with($frame, 'data: ')) {
                $events[] = json_decode(substr($frame, 6), true, 512, \JSON_THROW_ON_ERROR);
            }
        }

        return $events;
    }

    /**
     * @param list<array<string, mixed>> $events
     *
     * @return array<string, mixed>
     */
    private static function first(array $events, string $type): array
    {
        foreach ($events as $event) {
            if ($type === $event['type']) {
                return $event;
            }
        }

        self::fail(\sprintf('No %s event in: %s', $type, implode(', ', array_column($events, 'type'))));
    }
}
