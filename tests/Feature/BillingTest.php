<?php

namespace Tests\Feature;

use App\Billing\Credits;
use App\Models\ApplicationKit;
use App\Models\CreditTransaction;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
    }

    public function test_new_candidates_get_their_sign_up_credits_and_billing_pages_stay_closed_while_off(): void
    {
        $user = $this->candidate();
        $this->assertSame(20, app(Credits::class)->balance($user));

        $this->actingAs($user)->get('/billing')->assertNotFound();
        $this->post('/webhooks/billing/lemonsqueezy')->assertNotFound();
    }

    public function test_with_billing_on_one_credit_covers_a_kit_and_its_rewrites(): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 1, 'billing.rewrites_per_kit' => 1]);
        $user = $this->candidate();
        $role = Opportunity::available()->firstOrFail();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->kitReply())]);

        $this->actingAs($user)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertOk();
        $kit = ApplicationKit::sole();
        $this->assertSame(0, app(Credits::class)->balance($user));
        $this->assertSame(1, $kit->credits_used);

        $this->actingAs($user)->postJson(route('kits.regenerate', $kit))->assertOk();
        $this->actingAs($user)->postJson(route('kits.regenerate', $kit))->assertStatus(402)->assertJsonPath('billing_url', route('billing.index'));
        $this->assertSame(2, $kit->fresh()->generations);

        $other = Opportunity::available()->where('id', '!=', $role->id)->firstOrFail();
        $this->actingAs($user)->postJson("/opportunities/{$other->id}/kit", ['locale' => 'en'])->assertStatus(402);
    }

    public function test_a_failed_first_draft_gives_the_credit_back(): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 1]);
        $user = $this->candidate();
        Http::fake(['api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'Server error']], 500)]);

        $this->actingAs($user)->postJson('/opportunities/'.Opportunity::available()->firstOrFail()->id.'/kit', ['locale' => 'en'])->assertOk();

        $this->assertSame('failed', ApplicationKit::sole()->status);
        $this->assertSame(1, app(Credits::class)->balance($user));
        $this->assertSame(1, CreditTransaction::where('reason', 'refund')->count());
    }

    public function test_lemon_squeezy_checkout_and_signed_webhook_add_credits_once(): void
    {
        $this->billingOn(['billing.gateways.lemonsqueezy' => ['currency' => 'USD', 'api_key' => 'ls-key', 'store_id' => '11', 'signing_secret' => 'secret', 'test_mode' => true]]);
        $user = $this->candidate();
        Http::fake(['api.lemonsqueezy.com/v1/checkouts' => Http::response(['data' => ['attributes' => ['url' => 'https://pay.lemonsqueezy.com/checkout/abc']]], 201)]);

        $this->actingAs($user)->post('/billing/checkout', ['pack' => 'starter', 'gateway' => 'lemonsqueezy'])->assertRedirect('https://pay.lemonsqueezy.com/checkout/abc');
        $payment = Payment::sole();
        $this->assertSame(500, $payment->amount);
        Http::assertSent(fn ($request) => $request['data']['relationships']['variant']['data']['id'] === '77'
            && $request['data']['attributes']['checkout_data']['custom']['payment_id'] === (string) $payment->id && $request['data']['attributes']['custom_price'] === 500);

        $body = json_encode(['meta' => ['event_name' => 'order_created', 'custom_data' => ['payment_id' => (string) $payment->id]],
            'data' => ['id' => '9001', 'attributes' => ['status' => 'paid', 'currency' => 'USD', 'total' => 500, 'identifier' => 'uuid', 'test_mode' => true]]]);
        $this->call('POST', '/webhooks/billing/lemonsqueezy', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => 'wrong'], $body)->assertOk();
        $this->assertSame('pending', $payment->fresh()->status);

        $signed = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => hash_hmac('sha256', $body, 'secret')];
        $this->call('POST', '/webhooks/billing/lemonsqueezy', [], [], [], $signed, $body)->assertOk();
        $this->call('POST', '/webhooks/billing/lemonsqueezy', [], [], [], $signed, $body)->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('9001', $payment->fresh()->provider_ref);
        $this->assertSame(4, app(Credits::class)->balance($user));
    }

    public function test_konnect_payments_are_checked_with_konnect_before_credits_are_added(): void
    {
        $this->billingOn(['billing.gateways.konnect' => ['currency' => 'TND', 'api_key' => 'k-key', 'wallet_id' => 'wallet-1', 'base_url' => 'https://api.sandbox.konnect.network/api/v2']]);
        $user = $this->candidate();
        Http::fake([
            'api.sandbox.konnect.network/api/v2/payments/init-payment' => Http::response(['payUrl' => 'https://gateway.sandbox.konnect.network/pay?payment_ref=ref-1', 'paymentRef' => 'ref-1']),
            'api.sandbox.konnect.network/api/v2/payments/ref-1' => Http::sequence()
                ->push(['payment' => ['status' => 'pending', 'amount' => 15000, 'token' => 'TND']])
                ->push(['payment' => ['status' => 'completed', 'amount' => 15000, 'reachedAmount' => 15000, 'token' => 'TND']]),
        ]);

        $this->actingAs($user)->post('/billing/checkout', ['pack' => 'starter', 'gateway' => 'konnect'])->assertRedirect('https://gateway.sandbox.konnect.network/pay?payment_ref=ref-1');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), 'init-payment') && $request['amount'] === 15000 && $request['receiverWalletId'] === 'wallet-1' && $request->hasHeader('x-api-key', 'k-key'));

        $this->getJson('/webhooks/billing/konnect?payment_ref=ref-1')->assertOk();
        $this->assertSame(1, app(Credits::class)->balance($user));
        $this->get('/webhooks/billing/konnect?payment_ref=ref-1')->assertRedirect(route('billing.index'));
        $this->assertSame(4, app(Credits::class)->balance($user));
    }

    public function test_flouci_return_is_verified_with_flouci(): void
    {
        $this->billingOn(['billing.gateways.flouci' => ['currency' => 'TND', 'public_key' => 'pub', 'private_key' => 'priv', 'base_url' => 'https://developers.flouci.com/api/v2']]);
        $user = $this->candidate();
        Http::fake([
            'developers.flouci.com/api/v2/generate_payment' => Http::response(['result' => ['success' => true, 'payment_id' => 'fl-1', 'link' => 'https://checkout.flouci.com/fl-1']]),
            'developers.flouci.com/api/v2/verify_payment/fl-1' => Http::response(['success' => true, 'result' => ['status' => 'SUCCESS', 'amount' => 15000]]),
        ]);

        $this->actingAs($user)->post('/billing/checkout', ['pack' => 'starter', 'gateway' => 'flouci'])->assertRedirect('https://checkout.flouci.com/fl-1');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), 'generate_payment') && $request['amount'] === '15000' && $request->hasHeader('Authorization', 'Bearer pub:priv'));
        $payment = Payment::sole();

        $this->actingAs($user)->get(route('billing.return', ['gateway' => 'flouci', 'payment' => $payment->id, 'result' => 'success']))->assertRedirect(route('billing.index'));
        $this->assertSame(4, app(Credits::class)->balance($user));
        $this->actingAs($user)->get('/billing')->assertOk();
    }

    private function billingOn(array $gateway): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 1, 'billing.packs.starter' => ['credits' => 3, 'price_tnd' => 15000, 'price_usd' => 500, 'lemonsqueezy_variant' => '77'], ...$gateway]);
    }

    private function candidate(): User
    {
        $user = Candidate::create()->user()->create(['name' => 'Buyer Name', 'email' => 'buyer@example.com', 'password' => Hash::make('password')]);
        $user->forceFill(['email_verified_at' => now()])->save();
        return $user;
    }

    private function kitReply(): array
    {
        $data = ['cv' => ['headline' => 'Developer', 'summary' => 'I build apps.', 'skills' => [], 'experience' => [], 'education' => [], 'projects' => [], 'languages' => []], 'cover_letter' => 'Dear team.'];
        return ['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.5',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => json_encode($data), 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }
}
