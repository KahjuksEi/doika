<?php

namespace Tests\Feature\Http\Controllers\Webhooks\PaymentGateways;

use Diglabby\Doika\Http\Middleware\VerifyBePaidSignature;
use Diglabby\Doika\Models\Campaign;
use Diglabby\Doika\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BePaidWebhookHandlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_creates_successful_transaction_from_webhook_request()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware([VerifyBePaidSignature::class]);
        /** @var Campaign $campaign */
        $campaign = factory(Campaign::class)->create();

        $requestData = $this->getRequestData('BePaidWebhookHandlerTest.donated.json');
        $response = $this->postJson(
            route('webhooks.bepaid.donated', $campaign->id),
            $requestData['input'],
            $requestData['headers']
        );

        $response->assertSuccessful();
        $this->assertDatabaseHas('transactions', [
            'campaign_id' => $campaign->id,
            'subscription_id' => null,
            'payment_gateway' => 'bePaid',
            'payment_gateway_transaction_id' => '12019567-c74ce49914',
            'amount' => 500,
            'currency' => 'BYN',
            'status' => Transaction::STATUS_SUCCESSFUL,
        ]);
        $this->assertDatabaseHas('donators', [
            'email' => 'jake@example.com',
        ]);
    }

    public function getRequestData(string $fixtureFilename): array
    {
        $fixtureFile = __DIR__.DIRECTORY_SEPARATOR.$fixtureFilename;
        return json_decode(file_get_contents($fixtureFile), true);
    }
}
