{{-- Không JS: các tab xếp dọc (mỗi tab có tiêu đề); JS (extras.js) chuyển thành tab. --}}
@if ($tabs !== [])
    <section class="mb-10" data-block="extras_product_tabs" data-extras-tabs>
        @if ($title !== '')<h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>@endif
        <div role="tablist" class="mb-4 hidden gap-2" data-extras-tablist>
            @foreach ($tabs as $tab)
                <button type="button" role="tab" class="rounded-full border border-slate-300 px-4 py-1 text-sm aria-selected:border-primary aria-selected:font-semibold aria-selected:text-primary" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-extras-tab="{{ $loop->index }}">{{ $tab['label'] }}</button>
            @endforeach
        </div>
        @foreach ($tabs as $tab)
            <div role="tabpanel" class="mb-6" data-extras-panel="{{ $loop->index }}">
                <h3 class="mb-3 font-medium" data-extras-panel-title>{{ $tab['label'] }}</h3>
                <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
                    @foreach ($tab['products'] as $product)
                        @include('theme::partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
@endif
