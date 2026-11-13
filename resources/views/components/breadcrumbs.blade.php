@props([
    'items' => [],
])

<div {{ $attributes->merge(['class' => 'flex flex-row items-center justify-between gap-2 px-3 py-2 mb-2 bg-base-100 border border-base-300 rounded-xl shadow-2xs select-none']) }}>
    <div class="breadcrumbs text-xs sm:text-sm py-0 overflow-x-auto">
        <ul>
            @if (!empty($items))
                @foreach ($items as $item)
                    @php
                        $isLast = $loop->last;
                        $hasLink = !empty($item['href']) && ! $isLast;
                    @endphp
                    <li class="flex items-center gap-1.5 {{ $isLast ? 'font-semibold text-base-content' : 'text-base-content/70' }}">
                        @if (!empty($item['icon']))
                            <x-icon :name="$item['icon']" class="w-3.5 h-3.5 opacity-80 shrink-0" />
                        @endif

                        @if ($hasLink)
                            <a href="{{ $item['href'] }}" class="hover:text-primary transition-colors inline-flex items-center gap-1">
                                {{ $item['label'] }}
                            </a>
                        @else
                            <span>{{ $item['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </ul>
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
