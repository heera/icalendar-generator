<?php

namespace Spatie\IcalendarGenerator\Tests\Components;

use DateInterval;
use DateTime;
use DateTimeZone;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Tests\TestCase;

class CalendarTest extends TestCase
{
    /** @test */
    public function it_can_create_a_calendar()
    {
        $payload = Calendar::create()->resolvePayload();

        $this->assertEquals('CALENDAR', $payload->getType());

        $this->assertCount(2, $payload->getProperties());

        $this->assertPropertyEqualsInPayload('VERSION', '2.0', $payload);
        $this->assertPropertyEqualsInPayload('PRODID', 'spatie/icalendar-generator', $payload);
    }

    /** @test */
    public function it_can_set_calendar_properties()
    {
        $payload = Calendar::create()
            ->name('Full Stack Europe Schedule')
            ->description('What events are going to happen?')
            ->productIdentifier('Ruben\'s calendar creator machine')
            ->resolvePayload();

        $this->assertCount(4, $payload->getProperties());

        $this->assertPropertyEqualsInPayload('NAME', 'Full Stack Europe Schedule', $payload);
        $this->assertPropertyEqualsInPayload('X-WR-CALNAME', 'Full Stack Europe Schedule', $payload);
        $this->assertPropertyEqualsInPayload('DESCRIPTION', 'What events are going to happen?', $payload);
        $this->assertPropertyEqualsInPayload('PRODID', 'Ruben\'s calendar creator machine', $payload);
    }

    /** @test */
    public function it_can_add_an_event_to_a_calendar()
    {
        $event = Event::create('An introduction to event sourcing');

        $payload = Calendar::create()
            ->event($event)
            ->resolvePayload();

        $subComponents = $payload->getSubComponents();

        $this->assertCount(1, $subComponents);
        $this->assertEquals($subComponents[0], $event);
    }

    /** @test */
    public function it_can_add_an_event_by_closure_to_a_calendar()
    {
        $payload = Calendar::create()
            ->event(function (Event $event) {
                $event->name('An introduction to event sourcing');
            })
            ->resolvePayload();

        $subComponents = $payload->getSubComponents();

        $this->assertCount(1, $subComponents);
        $this->assertPropertyEqualsInPayload('SUMMARY', 'An introduction to event sourcing', $subComponents[0]->resolvePayload());
    }

    /** @test */
    public function it_can_add_multiple_events_to_a_calendar()
    {
        $firstEvent = Event::create('An introduction to event sourcing');
        $secondEvent = Event::create('Websockets what are they?');

        $payload = Calendar::create()
            ->event([$firstEvent, $secondEvent])
            ->resolvePayload();

        $subComponents = $payload->getSubComponents();

        $this->assertCount(2, $subComponents);
        $this->assertEquals($subComponents[0], $firstEvent);
        $this->assertEquals($subComponents[1], $secondEvent);
    }

    /** @test */
    public function it_can_add_multiple_events_by_closure_to_a_calendar()
    {
        $payload = Calendar::create()
            ->event([
                function (Event $event) {
                    $event->name('An introduction to event sourcing');
                },
                function (Event $event) {
                    $event->name('Websockets what are they?');
                },
            ])
            ->resolvePayload();

        $subComponents = $payload->getSubComponents();

        $this->assertCount(2, $subComponents);
        $this->assertPropertyEqualsInPayload('SUMMARY', 'An introduction to event sourcing', $subComponents[0]->resolvePayload());
        $this->assertPropertyEqualsInPayload('SUMMARY', 'Websockets what are they?', $subComponents[1]->resolvePayload());
    }

    /** @test */
    public function when_setting_with_timezones_events_will_be_added_with_timezones()
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $date = new DateTime('16 may 2019');

        $date->setTimezone($timezone);

        $payload = Calendar::create()
            ->withTimezone()
            ->event(function (Event $event) use ($date) {
                $event->startsAt($date);
            })
            ->resolvePayload();

        $eventTimezone = $payload->getSubComponents()[0]
            ->resolvePayload()
            ->getProperty('DTSTART')
            ->getOriginalValue()
            ->getTimezone();

        $this->assertEquals($timezone, $eventTimezone);
    }

    /** @test */
    public function a_refresh_rate_can_be_set()
    {
        $payload = Calendar::create()
            ->refreshInterval(5)
            ->resolvePayload();

        $this->assertPropertyEqualsInPayload('REFRESH-INTERVAL', new DateInterval('PT5M'), $payload);
        $this->assertParameterEqualsInProperty('VALUE', 'DURATION', $payload->getProperty('REFRESH-INTERVAL'));
    }

    /** @test */
    public function it_is_possible_to_add_multiple_events()
    {
        $firstEvent = Event::create('An introduction to event sourcing');
        $secondEvent = Event::create('An introduction to event sourcing');

        $payload = Calendar::create()
            ->event($firstEvent)
            ->event([$secondEvent])
            ->resolvePayload();

        $subComponents = $payload->getSubComponents();

        $this->assertCount(2, $subComponents);
        $this->assertEquals($subComponents[0], $firstEvent);
        $this->assertEquals($subComponents[1], $secondEvent);
    }
}
