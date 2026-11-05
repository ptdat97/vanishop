{{-- Điểm nổi bật của sản phẩm (store-content §3.2), dưới tên sản phẩm. --}}
<ul class="mt-2 space-y-1 text-sm text-slate-700" data-vani-cms-highlights>
    @foreach ($highlights as $highlight)
        <li class="flex gap-2"><span aria-hidden="true" class="text-accent">✓</span><span>{{ $highlight }}</span></li>
    @endforeach
</ul>
