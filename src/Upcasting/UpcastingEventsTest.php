<?php

declare(strict_types=1);

namespace EventSauce\EventSourcing\Upcasting;

use EventSauce\Clock\TestClock;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use PHPUnit\Framework\TestCase;

class UpcastingEventsTest extends TestCase
{
    /**
     * @test
     */
    public function upcasting_works(): void
    {
        $clock = new TestClock();
        $pointInTime = $clock->now();
        $defaultDecorator = new DefaultHeadersDecorator(null, $clock);
        $eventType = (new DotSeparatedSnakeCaseInflector())->classNameToType(UpcastedPayloadStub::class);
        $payload = [
            'headers' => [
                Header::EVENT_TYPE => $eventType,
                Header::TIME_OF_RECORDING => $pointInTime->format('Y-m-d H:i:s.uO'),
            ],
            'payload' => [],
        ];

        $upcaster = new UpcasterChain(new UpcasterStub());
        $serializer = new UpcastingMessageSerializer(new ConstructingMessageSerializer(), $upcaster);

        $message = $serializer->unserializePayload($payload);
        $expected = $defaultDecorator
                ->decorate(new Message(new UpcastedPayloadStub('upcasted')))
                ->withHeader('version', 1);

        $this->assertEquals($expected, $message);
    }

    /**
     * @test
     */
    public function serializing_still_works(): void
    {
        $upcaster = new UpcasterStub();
        $serializer = new UpcastingMessageSerializer(new ConstructingMessageSerializer(), $upcaster);
        $message = new Message(new UpcastedPayloadStub('a value'));

        $serializeMessage = $serializer->serializeMessage($message);
        $expectedPayload = [
            'headers' => [],
            'payload' => [
                'property' => 'a value',
            ],
        ];

        $this->assertEquals($expectedPayload, $serializeMessage);
    }
}
