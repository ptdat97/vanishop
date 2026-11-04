{{-- Đổi/trả trên storefront native (order §7, §7.1). Không cần JS: <details> mở form; đổi size/màu cùng mẫu chọn bằng select. --}}
@php($returns = $order['returns'] ?? [])
@if ($returns !== [] || ($returnForm ?? null) !== null)
    <section class="mt-8" aria-labelledby="returns-heading">
        <h2 id="returns-heading" class="mb-3 text-lg font-semibold">{{ __('Đổi / trả hàng') }}</h2>

        @foreach ($returns as $return)
            <div class="mb-3 rounded-[var(--radius-theme)] border border-slate-200 p-4 text-sm" data-return="{{ $return['number'] }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p>
                        <strong>{{ $return['number'] }}</strong> · {{ ($return['resolution'] ?? 'refund') === 'exchange' ? __('Đổi hàng') : __('Trả hàng hoàn tiền') }}
                        · <span>{{ __('storefront::messages.return_statuses.'.$return['status']) }}</span>
                    </p>
                    @if ($return['status'] === 'requested')
                        <form action="{{ vani_route('storefront.order.returns.cancel', [$order['id'], $return['id']]) }}" method="post">
                            @csrf
                            <button type="submit" class="text-sm text-red-700 underline">{{ __('Huỷ yêu cầu') }}</button>
                        </form>
                    @endif
                </div>
                <ul class="mt-2 text-slate-600">
                    @foreach ($return['lines'] as $line)
                        <li>{{ $line['name'] }} × {{ $line['quantity'] }}</li>
                    @endforeach
                </ul>
                @if (($return['resolution'] ?? 'refund') === 'refund')
                    <p class="mt-1 text-slate-500">{{ __('Tiền hoàn dự kiến: :amount', ['amount' => $return['refund']['formatted'] ?? '']) }}</p>
                @endif
            </div>
        @endforeach

        @if (($returnForm ?? null) !== null)
            <details class="rounded-[var(--radius-theme)] border border-slate-200 p-4" @if ($errors->has('lines') || $errors->has('business')) open @endif>
                <summary class="cursor-pointer font-medium">{{ __('Gửi yêu cầu đổi/trả') }}</summary>
                <form action="{{ vani_route('storefront.order.returns.store', $order['id']) }}" method="post" class="mt-4 space-y-4 text-sm">
                    @csrf
                    @if ($returnForm['deadline'])
                        <p class="text-slate-500">{{ __('Hạn đổi/trả: :date', ['date' => \Modules\Shared\Support\StoreClock::format($returnForm['deadline'], 'date')]) }}</p>
                    @endif

                    <fieldset>
                        <legend class="mb-2 font-medium">{{ __('Bạn muốn') }}</legend>
                        <label class="mr-4"><input type="radio" name="resolution" value="refund" @checked(old('resolution', 'refund') === 'refund')> {{ __('Trả hàng, hoàn tiền') }}</label>
                        <label><input type="radio" name="resolution" value="exchange" @checked(old('resolution') === 'exchange')> {{ __('Đổi size/màu khác (cùng mẫu, miễn phí)') }}</label>
                    </fieldset>

                    <table class="w-full">
                        <thead class="text-left text-slate-500">
                            <tr><th class="py-1 font-normal">{{ __('Sản phẩm') }}</th><th class="py-1 font-normal">{{ __('Số lượng') }}</th><th class="py-1 font-normal">{{ __('Đổi sang (nếu đổi)') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($returnForm['lines'] as $line)
                                <tr class="border-t border-slate-100 align-top">
                                    <td class="py-2 pr-2">{{ $line['name'] }}<span class="block text-xs text-slate-500">{{ $line['variant'] }}</span></td>
                                    <td class="py-2 pr-2">
                                        <label class="sr-only" for="return-qty-{{ $line['id'] }}">{{ __('Số lượng đổi/trả :name', ['name' => $line['name']]) }}</label>
                                        <select id="return-qty-{{ $line['id'] }}" name="lines[{{ $line['id'] }}][quantity]" class="rounded border border-slate-300 px-2 py-1">
                                            @for ($quantity = 0; $quantity <= $line['returnable']; $quantity++)
                                                <option value="{{ $quantity }}" @selected((int) old("lines.{$line['id']}.quantity", 0) === $quantity)>{{ $quantity }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td class="py-2">
                                        @if ($line['exchange_options'] !== [])
                                            <label class="sr-only" for="return-exchange-{{ $line['id'] }}">{{ __('Đổi :name sang', ['name' => $line['name']]) }}</label>
                                            <select id="return-exchange-{{ $line['id'] }}" name="lines[{{ $line['id'] }}][exchange_variant_id]" class="rounded border border-slate-300 px-2 py-1">
                                                <option value="">{{ __('— Chọn size/màu —') }}</option>
                                                @foreach ($line['exchange_options'] as $option)
                                                    <option value="{{ $option['variant_id'] }}" @disabled(! $option['available']) @selected((int) old("lines.{$line['id']}.exchange_variant_id") === $option['variant_id'])>
                                                        {{ $option['label'] }}{{ $option['available'] ? '' : __(' — hết hàng') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-xs text-slate-500">{{ __('Không còn size/màu khác') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @error('lines')
                        <p role="alert" class="text-red-700">{{ $message }}</p>
                    @enderror

                    <div>
                        <label for="return-reason" class="block">{{ __('Lý do') }}</label>
                        <select id="return-reason" name="reason_code" class="mt-1 rounded border border-slate-300 px-2 py-1">
                            @foreach ($returnForm['reasons'] as $code => $label)
                                <option value="{{ $code }}" @selected(old('reason_code') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="return-note" class="block">{{ __('Ghi chú cho shop (không bắt buộc)') }}</label>
                        <textarea id="return-note" name="note" rows="2" maxlength="500" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ old('note') }}</textarea>
                    </div>
                    <p class="text-xs text-slate-500">{{ __('Shop xác nhận yêu cầu rồi hướng dẫn gửi hàng về. Đổi hàng: shop gửi sản phẩm mới sau khi nhận hàng bạn gửi. Đổi sang mẫu khác vui lòng liên hệ shop.') }}</p>
                    <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('Gửi yêu cầu') }}</button>
                </form>
            </details>
        @endif
    </section>
@endif
