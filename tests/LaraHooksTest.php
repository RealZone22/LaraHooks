<?php

namespace RealZone22\LaraHooks\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RealZone22\LaraHooks\LaraHooks;

class LaraHooksTest extends TestCase
{
    private LaraHooks $laraHooks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->laraHooks = new LaraHooks();
    }

    #[Test]
    public function it_can_register_a_hook_listener(): void
    {
        $this->laraHooks->listen('test.hook', function () {
            return 'test';
        });

        $hooks = $this->laraHooks->getHooks();

        $this->assertContains('test.hook', $hooks);
    }

    #[Test]
    public function it_can_execute_hook_with_callback(): void
    {
        $called = false;

        $this->laraHooks->listen('test.hook', function ($callback, $output, $params) use (&$called) {
            $called = true;
            return 'hook result';
        });

        $result = $this->laraHooks->get('test.hook', [], function () {
            return 'default';
        });

        $this->assertTrue($called);
        $this->assertEquals('hook result', $result);
    }

    #[Test]
    public function it_respects_hook_priority(): void
    {
        $order = [];

        $this->laraHooks->listen('test.hook', function () use (&$order) {
            $order[] = 'second';
        }, 2);

        $this->laraHooks->listen('test.hook', function () use (&$order) {
            $order[] = 'first';
        }, 1);

        $this->laraHooks->get('test.hook', [], function () {});

        $this->assertEquals(['first', 'second'], $order);
    }

    #[Test]
    public function it_can_stop_hook_execution(): void
    {
        $firstCalled = false;
        $secondCalled = false;

        $this->laraHooks->listen('test.hook', function () use (&$firstCalled) {
            $firstCalled = true;
            $this->laraHooks->stop('test.hook');
        }, 1);

        $this->laraHooks->listen('test.hook', function () use (&$secondCalled) {
            $secondCalled = true;
        }, 2);

        $this->laraHooks->get('test.hook', [], function () {});

        $this->assertTrue($firstCalled);
        $this->assertFalse($secondCalled);
    }

    #[Test]
    public function it_can_pass_parameters_to_hook(): void
    {
        $receivedParams = null;

        $this->laraHooks->listen('test.hook', function ($callback, $output, $params) use (&$receivedParams) {
            $receivedParams = $params;
        });

        $this->laraHooks->get('test.hook', ['key' => 'value'], function () {});

        $this->assertEquals(['key' => 'value'], $receivedParams);
    }

    #[Test]
    public function it_returns_callback_result_when_no_hook_registered(): void
    {
        $result = $this->laraHooks->get('nonexistent.hook', [], function () {
            return 'callback result';
        });

        $this->assertEquals('callback result', $result);
    }

    #[Test]
    public function it_can_mock_hook_result(): void
    {
        $this->laraHooks->mock('test.hook', 'mocked value');

        $result = $this->laraHooks->get('test.hook');

        $this->assertEquals('mocked value', $result);
    }

    #[Test]
    public function it_can_retrieve_all_listeners(): void
    {
        $this->laraHooks->listen('hook.one', function () {});
        $this->laraHooks->listen('hook.two', function () {});

        $listeners = $this->laraHooks->getListeners();

        $this->assertArrayHasKey('hook.one', $listeners);
        $this->assertArrayHasKey('hook.two', $listeners);
    }

    #[Test]
    public function it_can_retrieve_events_for_specific_hook(): void
    {
        $this->laraHooks->listen('test.hook', function () {}, 1);
        $this->laraHooks->listen('test.hook', function () {}, 2);

        $events = $this->laraHooks->getEvents('test.hook');

        $this->assertCount(2, $events);
        $this->assertArrayHasKey(1, $events);
        $this->assertArrayHasKey(2, $events);
    }

    #[Test]
    public function it_passes_html_content_to_hook(): void
    {
        $receivedContent = null;

        $this->laraHooks->listen('test.hook', function ($callback, $output) use (&$receivedContent) {
            $receivedContent = $output;
        });

        $this->laraHooks->get('test.hook', [], function () {}, '<div>content</div>');

        $this->assertEquals('<div>content</div>', $receivedContent);
    }

    #[Test]
    public function it_returns_modified_output_from_hook(): void
    {
        $this->laraHooks->listen('test.hook', function ($callback, $output) {
            return $output . ' modified';
        });

        $result = $this->laraHooks->get('test.hook', [], function () {}, 'original');

        $this->assertEquals('original modified', $result);
    }
}
