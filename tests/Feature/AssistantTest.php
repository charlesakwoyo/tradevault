<?php

use Anthropic\Beta\Messages\BetaMessage;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Services\Assistant\AssistantModel;

/**
 * Scripted stand-in for Claude: returns the queued responses in order and
 * records every request it receives.
 *
 * @param  list<array<string, mixed>>  $responses  wire-format message bodies
 */
function fakeAssistant(array $responses): object
{
    $fake = new class($responses) implements AssistantModel
    {
        /** @var list<array{system: array, messages: array, tools: array}> */
        public array $requests = [];

        public function __construct(private array $responses) {}

        public function respond(array $system, array $messages, array $tools): BetaMessage
        {
            $this->requests[] = compact('system', 'messages', 'tools');

            return BetaMessage::fromArray(array_shift($this->responses));
        }
    };

    app()->instance(AssistantModel::class, $fake);

    return $fake;
}

/**
 * @param  list<array<string, mixed>>  $content
 * @return array<string, mixed>
 */
function claudeMessage(array $content, string $stopReason = 'end_turn'): array
{
    return [
        'id' => 'msg_'.fake()->uuid(), 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5',
        'content' => $content, 'stop_reason' => $stopReason, 'stop_sequence' => null,
        'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
    ];
}

beforeEach(function () {
    config(['services.anthropic.api_key' => 'test-key']);
});

test('customers see the assistant only when it is configured', function () {
    $user = $this->customer();

    $this->actingAs($user)->get(route('dashboard'))->assertSee('TradeVault assistant');

    config(['services.anthropic.api_key' => null]);
    $this->actingAs($user)->get(route('dashboard'))->assertDontSee('TradeVault assistant');
    $this->actingAs($user)->postJson(route('assistant.messages.store'), ['message' => 'Hi'])->assertNotFound();
});

test('admins get the assistant with price tools only', function () {
    $admin = $this->staff();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('TradeVault assistant');

    $fake = fakeAssistant([
        claudeMessage([['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get_my_account_summary', 'input' => []]], 'tool_use'),
        claudeMessage([['type' => 'text', 'text' => 'Account figures are only available to customers.']]),
    ]);

    $this->actingAs($admin)->postJson(route('assistant.messages.store'), ['message' => 'What is my balance?'])->assertOk();

    expect(array_column($fake->requests[0]['tools'], 'name'))->toBe(['get_market_prices'])
        ->and($fake->requests[1]['messages'][2]['content'][0]['content'])->toContain('only available to customers');
});

test('guests cannot use the assistant', function () {
    $this->postJson(route('assistant.messages.store'), ['message' => 'Hi'])->assertUnauthorized();
});

test('the assistant looks up the customer\'s own account and answers', function () {
    $user = $this->customer();
    $this->fund($user, '250');
    $this->fund($this->customer(), '999999');

    $fake = fakeAssistant([
        claudeMessage([['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get_my_account_summary', 'input' => []]], 'tool_use'),
        claudeMessage([['type' => 'text', 'text' => 'Your available balance is USD 250.00.']]),
    ]);

    $this->actingAs($user)->postJson(route('assistant.messages.store'), ['message' => 'What is my balance?'])
        ->assertOk()
        ->assertJson(['reply' => 'Your available balance is USD 250.00.']);

    $toolResult = $fake->requests[1]['messages'][2]['content'][0];
    expect($toolResult['toolUseID'])->toBe('tu_1')
        ->and($toolResult['content'])->toContain('USD 250.00')
        ->and($toolResult['content'])->not->toContain('999,999');

    expect(session('assistant.history'))->toBe([
        ['role' => 'user', 'content' => 'What is my balance?'],
        ['role' => 'assistant', 'content' => 'Your available balance is USD 250.00.'],
    ]);
});

test('the price tool returns live prices for the requested markets', function () {
    $btc = Market::factory()->crypto('BTC', 'Bitcoin')->create();
    Market::factory()->crypto('ETH', 'Ethereum')->create();
    MarketPrice::unguarded(fn () => $btc->prices()->create(['last' => '84279.49', 'open' => '86288', 'source' => 'binance', 'is_simulated' => false, 'recorded_at' => now()]));

    $fake = fakeAssistant([
        claudeMessage([['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get_market_prices', 'input' => ['symbols' => ['btc', 'XYZ']]]], 'tool_use'),
        claudeMessage([['type' => 'text', 'text' => 'Bitcoin is at USD 84,279.49.']]),
    ]);

    $this->actingAs($this->customer())->postJson(route('assistant.messages.store'), ['message' => 'BTC price?'])->assertOk();

    $result = json_decode($fake->requests[1]['messages'][2]['content'][0]['content'], true);
    expect($result['markets'])->toHaveCount(1)
        ->and($result['markets'][0]['price'])->toBe('USD 84,279.49')
        ->and($result['markets'][0]['change_24h_percent'])->toBe(-2.33)
        ->and($result['not_listed'])->toBe(['XYZ']);
});

test('earlier turns are sent back as conversation history', function () {
    $fake = fakeAssistant([
        claudeMessage([['type' => 'text', 'text' => 'First answer']]),
        claudeMessage([['type' => 'text', 'text' => 'Second answer']]),
    ]);
    $user = $this->customer();

    $this->actingAs($user)->postJson(route('assistant.messages.store'), ['message' => 'First question']);
    $this->actingAs($user)->postJson(route('assistant.messages.store'), ['message' => 'Second question']);

    expect($fake->requests[1]['messages'])->toBe([
        ['role' => 'user', 'content' => 'First question'],
        ['role' => 'assistant', 'content' => 'First answer'],
        ['role' => 'user', 'content' => 'Second question'],
    ]);

    $this->actingAs($user)->deleteJson(route('assistant.messages.destroy'))->assertOk();
    expect(session('assistant.history'))->toBeNull();
});

test('a declined request gets a polite answer instead of an error', function () {
    fakeAssistant([claudeMessage([], 'refusal')]);

    $this->actingAs($this->customer())->postJson(route('assistant.messages.store'), ['message' => 'Something off-limits'])
        ->assertOk()
        ->assertJsonPath('reply', fn (string $reply) => str_contains($reply, 'can’t help with that'));
});

test('the tool loop stops after a bounded number of rounds', function () {
    $toolTurn = claudeMessage([['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get_market_prices', 'input' => ['symbols' => []]]], 'tool_use');
    $fake = fakeAssistant(array_fill(0, 10, $toolTurn));

    $this->actingAs($this->customer())->postJson(route('assistant.messages.store'), ['message' => 'Loop'])
        ->assertOk()
        ->assertJsonPath('reply', fn (string $reply) => str_contains($reply, 'too many steps'));

    expect($fake->requests)->toHaveCount(6);
});

test('messages are validated', function () {
    fakeAssistant([]);

    $this->actingAs($this->customer())->postJson(route('assistant.messages.store'), ['message' => ''])->assertJsonValidationErrors('message');
    $this->actingAs($this->customer())->postJson(route('assistant.messages.store'), ['message' => str_repeat('a', 1001)])->assertJsonValidationErrors('message');
});
