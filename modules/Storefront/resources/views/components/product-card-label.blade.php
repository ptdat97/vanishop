@props(['text', 'accentColor' => null])

<span {{ $attributes->class([
    'inline-block max-w-full wrap-anywhere rounded px-2 py-0.5 text-xs font-medium',
    'bg-subtle text-foreground' => ! $attributes->has('class'),
]) }} @if (is_string($accentColor) && preg_match('/\A#[0-9a-f]{6}\z/i', $accentColor)) style="background-color: {{ $accentColor }}" @endif>
    {{ $text }}
</span>
