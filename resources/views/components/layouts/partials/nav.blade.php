<nav class="flex flex-1 flex-col px-3 pb-4" aria-label="{{ $isAdmin ? __('Back office') : __('Main') }}">
    <ul role="list" class="space-y-0.5">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['active']); @endphp
            <li>
                <a href="{{ route($item['route']) }}"
                    class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-white/10 text-white' : 'text-ink-300 hover:bg-white/5 hover:text-white' }}"
                    @if ($active) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" class="size-5 {{ $active ? 'text-brand-400' : 'text-ink-400 group-hover:text-ink-200' }}" />
                    {{ __($item['label']) }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="mt-auto rounded-xl bg-white/5 p-3 text-xs leading-relaxed text-ink-300">
        <p class="font-semibold text-ink-100">{{ __('Risk warning') }}</p>
        <p class="mt-1">{{ __('Trading involves risk. The value of investments can fall as well as rise and you may lose money.') }}</p>
        <a href="{{ route('legal.show', 'risk') }}" class="mt-1.5 inline-block font-medium text-brand-300 hover:text-brand-200">{{ __('Read the risk disclosure') }}</a>
    </div>
</nav>
