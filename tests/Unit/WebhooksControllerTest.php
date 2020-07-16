<?php

namespace Digikraaft\PaystackWebhooks\Tests\Unit;

use Digikraaft\PaystackWebhooks\Events\WebhookHandled;
use Digikraaft\PaystackWebhooks\Events\WebhookReceived;
use Digikraaft\PaystackWebhooks\Http\Controllers\WebhooksController;
use Digikraaft\PaystackWebhooks\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class WebhooksControllerTest extends TestCase
{
    public function test_proper_methods_are_called_based_on_paystack_event()
    {
        $request = $this->request('charge.success');

        Event::fake([
            WebhookHandled::class,
            WebhookReceived::class,
        ]);

        $response = (new WebhooksControllerTestStub)->handleWebhook($request);

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event) use ($request) {
            return $request->getContent() == json_encode($event->payload);
        });

        Event::assertDispatched(WebhookHandled::class, function (WebhookHandled $event) use ($request) {
            return $request->getContent() == json_encode($event->payload);
        });

        $this->assertEquals('Webhook Handled', $response->getContent());
    }

    public function test_normal_response_is_returned_if_method_is_missing()
    {
        $request = $this->request('foo.bar');

        Event::fake([
            WebhookHandled::class,
            WebhookReceived::class,
        ]);

        $response = (new WebhooksControllerTestStub)->handleWebhook($request);

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event) use ($request) {
            return $request->getContent() == json_encode($event->payload);
        });

        Event::assertNotDispatched(WebhookHandled::class);

        $this->assertEquals(200, $response->getStatusCode());
    }

    private function request($event)
    {
        return Request::create(
            '/paystack',
            'POST',
            [],
            [],
            [],
            [],
            json_encode(['event' => $event, 'data' => 'domain'])
        );
    }
}

class WebhooksControllerTestStub extends WebhooksController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handleChargeSuccess(array $payload)
    {
        return $this->successMethod();
    }
}
