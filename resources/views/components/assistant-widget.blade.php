@php
    $history = session(\App\Http\Controllers\AssistantController::SESSION_KEY, []);
    $isCustomer = auth()->user()->isCustomer();
    $suggestions = $isCustomer
        ? [__('What is the price of Bitcoin right now?'), __('What is my balance?'), __('How do I verify my identity?')]
        : [__('Which markets moved most in the last 24 hours?'), __('What is the price of Bitcoin right now?'), __('What are the deposit and withdrawal limits?')];
@endphp

{{-- Floating help assistant. Replies are rendered with x-text, never as HTML. --}}
<div x-data="{
        open: false,
        input: '',
        sending: false,
        messages: @js($history),
        async send() {
            const text = this.input.trim();
            if (! text || this.sending) return;
            this.messages.push({ role: 'user', content: text });
            this.input = '';
            this.sending = true;
            this.scroll();
            try {
                const response = await fetch('{{ route('assistant.messages.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ message: text }),
                });
                const data = await response.json().catch(() => ({}));
                this.messages.push({ role: 'assistant', content: response.ok ? data.reply
                    : (response.status === 429 ? '{{ __('You are sending messages too quickly. Please wait a moment.') }}' : '{{ __('Something went wrong. Please try again.') }}') });
            } catch (e) {
                this.messages.push({ role: 'assistant', content: '{{ __('Could not reach the assistant. Check your connection.') }}' });
            }
            this.sending = false;
            this.scroll();
        },
        async reset() {
            await fetch('{{ route('assistant.messages.destroy') }}', { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
            this.messages = [];
        },
        scroll() { this.$nextTick(() => { if (this.$refs.log) this.$refs.log.scrollTop = this.$refs.log.scrollHeight; }); },
    }"
    class="fixed right-4 {{ $isCustomer ? 'bottom-20' : 'bottom-4' }} z-50 flex flex-col items-end gap-3 lg:right-6 lg:bottom-6">

    <section x-cloak x-show="open" x-transition.origin.bottom.right
        class="card flex h-[30rem] max-h-[70vh] w-[calc(100vw-2rem)] max-w-sm flex-col overflow-hidden shadow-xl"
        role="dialog" aria-label="{{ __('TradeVault assistant') }}">
        <header class="flex items-center justify-between border-b border-ink-100 px-4 py-3">
            <div>
                <p class="text-sm font-semibold">{{ __('TradeVault assistant') }}</p>
                <p class="text-xs text-ink-400">{{ __('Ask about prices, your account or how things work') }}</p>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" x-show="messages.length" @click="reset()" class="rounded-lg px-2 py-1 text-xs font-medium text-ink-500 hover:bg-ink-50">{{ __('Clear') }}</button>
                <button type="button" @click="open = false" class="rounded-lg p-1 text-ink-400 hover:bg-ink-50 hover:text-ink-700" aria-label="{{ __('Close assistant') }}"><x-icon name="x" class="size-5" /></button>
            </div>
        </header>

        <div x-ref="log" class="flex-1 space-y-3 overflow-y-auto px-4 py-4" aria-live="polite">
            <template x-if="! messages.length">
                <div class="space-y-3 text-sm text-ink-500">
                    <p>{{ __('Hi! I can help with questions like:') }}</p>
                    @foreach ($suggestions as $suggestion)
                        <button type="button" @click="input = @js($suggestion); send()" class="block w-full rounded-xl border border-ink-100 px-3 py-2 text-left text-ink-700 hover:border-brand-200 hover:bg-brand-50">{{ $suggestion }}</button>
                    @endforeach
                </div>
            </template>
            <template x-for="(message, index) in messages" :key="index">
                <div :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <p x-text="message.content"
                        :class="message.role === 'user' ? 'bg-brand-600 text-white' : 'bg-ink-50 text-ink-800'"
                        class="max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-relaxed whitespace-pre-line"></p>
                </div>
            </template>
            <div x-show="sending" class="flex justify-start">
                <p class="rounded-2xl bg-ink-50 px-3 py-2 text-sm text-ink-400">{{ __('Thinking…') }}</p>
            </div>
        </div>

        <form @submit.prevent="send()" class="border-t border-ink-100 p-3">
            <div class="flex items-center gap-2">
                <label for="assistant-input" class="sr-only">{{ __('Your message') }}</label>
                <input id="assistant-input" x-model="input" type="text" maxlength="1000" autocomplete="off" placeholder="{{ __('Type your question…') }}" class="form-input">
                <button type="submit" class="btn-primary shrink-0 px-3" :disabled="sending || ! input.trim()">{{ __('Send') }}</button>
            </div>
            <p class="mt-2 text-[11px] leading-snug text-ink-400">{{ __('AI answers can be wrong and are not financial advice.') }}</p>
        </form>
    </section>

    <button type="button" @click="open = ! open; if (open) scroll()"
        class="flex size-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
        :aria-expanded="open" aria-label="{{ __('Open assistant') }}">
        <svg x-show="! open" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
        <x-icon x-show="open" name="x" class="size-6" />
    </button>
</div>
