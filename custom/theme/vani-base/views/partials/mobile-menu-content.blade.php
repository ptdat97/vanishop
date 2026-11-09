@php
    $panels = [];
    $collectPanels = function (array $panelItems, string $panelId, ?string $parentPanelId, ?string $parentOpenerId, string $parentLabel, int $level) use (&$collectPanels, &$panels): void {
        $panels[] = compact('panelItems', 'panelId', 'parentPanelId', 'parentOpenerId', 'parentLabel', 'level');

        foreach ($panelItems as $index => $item) {
            if ($item['children'] !== []) {
                $openerId = $panelId . '-item-' . $index;
                $childPanelId = $panelId . '-' . $index;
                $collectPanels($item['children'], $childPanelId, $panelId, $openerId, $item['label'], $level + 1);
            }
        }
    };
    $collectPanels($items, 'vani-mobile-menu-root', null, null, __('Danh mục'), 0);
@endphp
<div class="vani-mobile-menu-content min-h-0 flex-1 overflow-hidden text-sm" data-vani-mobile-menu>
    <div class="vani-mobile-menu-track" data-vani-menu-track style="--vani-menu-index: 0">
        @foreach ($panels as $panel)
            @include('theme::partials.mobile-menu-panel', [
                'items' => $panel['panelItems'],
                'panelId' => $panel['panelId'],
                'parentPanelId' => $panel['parentPanelId'],
                'parentOpenerId' => $panel['parentOpenerId'],
                'parentLabel' => $panel['parentLabel'],
                'level' => $panel['level'],
                'fallbackUrl' => $fallbackUrl,
            ])
        @endforeach
    </div>
</div>
