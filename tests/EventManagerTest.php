<?php
namespace FastEventManagerTest;

use PHPUnit_Framework_TestCase;
use FastEventManager\EventManager;
use PHPUnit_Framework_Assert;

class EventManagerTest extends PHPUnit_Framework_TestCase
{
    public function testEventManagerHasZeroListeners()
    {
        $eventManager = new EventManager();
        $listeners = PHPUnit_Framework_Assert::readAttribute($eventManager, 'listeners');
        $this->assertCount(0, $listeners);
    }

    public function testAttachFirstListener()
    {
        $eventManager = new EventManager();
        $eventManager->attach("post-save", function () {
        });
        $listeners = PHPUnit_Framework_Assert::readAttribute($eventManager, 'listeners');
        $this->assertCount(1, $listeners);
    }

    /**
     * @dataProvider triggers
     */
    public function testCallEvent($regexp, $attach, $called)
    {
        $eventManager = new EventManager();

        $override = false;
        $eventManager->attach($attach, function ($assert) use (&$override) {
            $override = true;
        });
        $eventManager->trigger($regexp, ["override"]);

        $this->assertSame($called, $override);
    }

    public function triggers()
    {
        return [
            ["/post-save/", "post-save", true],
            ["/post-*/", "post-save", true],
            ["/post-[a-zA-Z0-9]*/", "post-save", true],
            ["/post-(save|load)/", "post-save", true],
            ["/post-SAVE/i", "post-save", true],
            ["/post-load/i", "post-save", false],
            ["/post-LOAD/", "post-load", false],
            ["/post-LOAD/i", "post-load", true],
            ["/post-(save|load)/i", "post-load", true],
            ["/post-*/i", "post-load", true],
        ];
    }

    public function testCallMoreThanOneListenerWithRegex()
    {
        $eventManager = new EventManager();
        $one = false;
        $two = false;
        $eventManager->attach("pre-load", function() use (&$one){ $one = true; });
        $eventManager->attach("pre-exec", function() use (&$two){ $two = true; });

        $eventManager->trigger("/pre-[a-z]*/i", ["ok"]);

        $this->assertTrue($one);
        $this->assertTrue($two);
    }

    public function testAttachEventCheckName()
    {
        $eventManager = new EventManager();
        $eventManager->attach("post-save", function () {});
        $listeners = PHPUnit_Framework_Assert::readAttribute($eventManager, 'listeners');
        $this->assertTrue(array_key_exists("post-save", $listeners));
    }

    public function testAttachTwoListenersSameEventAndCheckEventNameIntoTheListeners()
    {
        $eventManager = new EventManager();
        $eventManager->attach("post-save", function () {});
        $eventManager->attach("post-save", function () {});
        $listeners = PHPUnit_Framework_Assert::readAttribute($eventManager, 'listeners');
        $this->assertCount(1, $listeners);
    }

    public function testAttachTwoListenersSameEvent()
    {
        $eventManager = new EventManager();

        $eventManager->attach("post-save", function () {});
        $eventManager->attach("post-save", function () {});

        $listeners = PHPUnit_Framework_Assert::readAttribute($eventManager, 'listeners');
        $this->assertCount(2, $listeners['post-save']);
    }
}
