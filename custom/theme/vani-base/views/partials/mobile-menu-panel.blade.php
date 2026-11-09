<section class="vani-mobile-menu-panel p-4" data-vani-menu-panel data-vani-menu-panel-id="{{ $panelId }}"
    data-vani-menu-panel-level="{{ $level }}" aria-label="{{ $parentLabel }}"
    @if ($level > 0) inert aria-hidden="true" @endif>
    <div class="mb-3 flex items-center gap-3 border-b border-slate-200 pb-3">
        @if ($level > 0)
            <button type="button" class="cursor-pointer rounded p-2 hover:bg-slate-100" data-vani-menu-back
                data-vani-menu-target="{{ $parentPanelId }}" data-vani-menu-focus="{{ $parentOpenerId }}"
                aria-label="{{ __('Quay lại') }}">←</button>
        @endif
        <h3 class="font-semibold">{{ $parentLabel }}</h3>
    </div>
    @if ($items === [] && $level === 0)
        <a href="{{ $fallbackUrl }}" class="block rounded px-3 py-3 hover:bg-slate-50">{{ __('Thương hiệu') }}</a>
        <div class="mt-4 border-t border-slate-200 pt-4">
            <x-vani::hook-slot name="vani.storefront.header.nav" />
        </div>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($items as $item)
                @php($openerId = $panelId . '-item-' . $loop->index)
                @php($childPanelId = $panelId . '-' . $loop->index)
                <li class="flex items-center gap-2">
                    @if ($item['url'] !== null)
                        <a href="{{ $item['url'] }}" class="flex min-w-0 flex-1 items-center gap-2 py-3 hover:text-primary">
                            <span class="truncate">{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span class="shrink-0 rounded-full px-1.5 text-[10px] font-semibold uppercase text-white {{ $item['badge'] === 'new' ? 'bg-emerald-600' : 'bg-red-600' }}">{{ $item['badge'] === 'new' ? __('Mới') : 'Sale' }}</span>
                            @endif
                        </a>
                    @else
                        <span class="min-w-0 flex-1 py-3 font-medium">{{ $item['label'] }}</span>
                    @endif
                    @if ($item['children'] !== [])
                        <button id="{{ $openerId }}" type="button" class="cursor-pointer rounded p-2 hover:bg-slate-100"
                            data-vani-menu-next data-vani-menu-target="{{ $childPanelId }}"
                            aria-label="{{ __('Mở :name', ['name' => $item['label']]) }}">→</button>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($level === 0)
            <div class="mt-4 border-t border-slate-200 pt-4">
                <x-vani::hook-slot name="vani.storefront.header.nav" />
            </div>
        @endif
    @endif
</section>
