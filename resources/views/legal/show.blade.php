<x-layouts.guest :title="$title" wide>
    <x-slot:subtitle>{{ __('Version :version', ['version' => $version]) }}</x-slot:subtitle>

    <article class="space-y-4 text-sm leading-relaxed text-ink-700 [&_h2]:mt-6 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-ink-900 [&_li]:ml-5 [&_ul]:list-disc [&_ul]:space-y-1 [&_strong]:text-ink-900">
        {!! $html !!}
    </article>

    <div class="mt-8 flex flex-wrap gap-x-4 gap-y-2 border-t border-ink-100 pt-4 text-xs">
        @foreach (config('tradevault.legal.documents') as $key => $label)
            <a href="{{ route('legal.show', $key) }}" class="font-medium text-brand-700 hover:underline">{{ __($label) }}</a>
        @endforeach
    </div>
</x-layouts.guest>
