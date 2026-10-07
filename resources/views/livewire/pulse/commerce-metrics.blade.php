<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header name="Thương mại" x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`" details="counter trong {{ $this->periodForHumans() }}, gauge mới nhất">
        <x-slot:icon>
            <x-pulse::icons.scale />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.15s="">
        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm md:grid-cols-3">
            @foreach ($counterLabels as $name => $label)
                <div class="flex justify-between gap-2 border-b border-gray-100 py-1 dark:border-gray-800">
                    <span class="text-gray-500 dark:text-gray-400" title="vani.{{ $name }}">{{ $label }}</span>
                    <span class="font-semibold tabular-nums {{ in_array($name, $alerts, true) && $counters[$name] > 0 ? 'text-red-600' : 'text-gray-700 dark:text-gray-200' }}">{{ number_format($counters[$name]) }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-1 text-sm md:grid-cols-3">
            @foreach ($gaugeLabels as $name => $label)
                <div class="flex justify-between gap-2 border-b border-gray-100 py-1 dark:border-gray-800">
                    <span class="text-gray-500 dark:text-gray-400" title="vani.{{ $name }}">{{ $label }}</span>
                    <span class="font-semibold tabular-nums text-gray-700 dark:text-gray-200">{{ $gauges[$name] !== '' ? $gauges[$name] : '0' }}</span>
                </div>
            @endforeach
        </div>
    </x-pulse::scroll>
</x-pulse::card>
