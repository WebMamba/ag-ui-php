<?php

declare(strict_types=1);

namespace AgUi\Tests\Serializer;

use AgUi\Event\Event;
use AgUi\Event\TextMessageContentEvent;
use AgUi\Message\UserMessage;
use AgUi\Run\RunAgentInput;
use AgUi\Serializer\AgUiSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class ModelNormalizerTest extends TestCase
{
    public function testARunAgentInputIsDeserializedWithItsMessages(): void
    {
        $input = AgUiSerializer::create()->deserialize(
            '{"threadId":"t1","runId":"r1","messages":[{"id":"u1","role":"user","content":"When should I leave Panama?"}],"state":{}}',
            RunAgentInput::class,
            'json',
        );

        self::assertInstanceOf(RunAgentInput::class, $input);
        self::assertInstanceOf(UserMessage::class, $input->messages[0]);
        self::assertSame('When should I leave Panama?', $input->messages[0]->content);
    }

    public function testAnInterfaceIsDecodedByItsDiscriminator(): void
    {
        $event = AgUiSerializer::create()->deserialize('{"type":"TEXT_MESSAGE_CONTENT","messageId":"m1","delta":"Ahoy"}', Event::class, 'json');

        self::assertInstanceOf(TextMessageContentEvent::class, $event);
    }

    public function testUnknownPropertiesAreDroppedUnlessExtraAttributesAreForbidden(): void
    {
        $json = '{"threadId":"t1","runId":"r1","messages":[],"fromTheFuture":true}';
        $serializer = AgUiSerializer::create();

        self::assertSame('t1', $serializer->deserialize($json, RunAgentInput::class, 'json')->threadId);

        $this->expectException(NotNormalizableValueException::class);
        $serializer->deserialize($json, RunAgentInput::class, 'json', [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false]);
    }

    public function testAShapeErrorNamesThePropertyInTheSerializersNotation(): void
    {
        try {
            AgUiSerializer::create()->deserialize('{"threadId":"t1","runId":"r1","messages":[{"id":"u1","role":"user","content":42}]}', RunAgentInput::class, 'json');
            self::fail('A number is not a message content.');
        } catch (NotNormalizableValueException $e) {
            self::assertSame('messages[0].content', $e->getPath());
        }
    }

    public function testAnEmptyObjectIsSerializedAsAnObject(): void
    {
        $json = AgUiSerializer::create()->serialize(new RunAgentInput('t1', 'r1', [], state: new \stdClass(), forwardedProps: []), 'json');

        self::assertSame('{"threadId":"t1","runId":"r1","messages":[],"state":{},"forwardedProps":[]}', $json, 'A {} state stays {}, a [] value stays [].');
    }
}
