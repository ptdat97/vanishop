{{-- Tuỳ chọn dòng do plugin thêm (CartLineOption): chỉ hiển thị giá trị đã chuẩn hoá. --}}
@foreach ($options ?? [] as $plugin => $values)
    @foreach ($values as $field => $value)
        @if ($value !== null && $value !== '')
            <span class="block text-xs text-slate-500" data-line-option="{{ $plugin }}.{{ $field }}">{{ $value }}</span>
        @endif
    @endforeach
@endforeach
