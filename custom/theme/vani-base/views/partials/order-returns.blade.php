{{-- Đổi/trả trên storefront native (order §7, §7.1). Không cần JS: <details> mở form; đổi size/màu cùng mẫu chọn bằng select. --}}
@php($returns = $order['returns'] ?? [])
@if ($returns !== [] || ($returnForm ?? null) !== null)
    <section class="mt-8" aria-labelledby="returns-heading">
        <h2 id="returns-heading" class="mb-3 text-lg font-semibold">{{ __('theme::order.returns_exchanges') }}</h2>

        @foreach ($returns as $return)
            <div class="mb-3 rounded-[var(--radius-theme)] border border-slate-200 p-4 text-sm" data-return="{{ $return['number'] }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p>
                        <strong>{{ $return['number'] }}</strong> · {{ ($return['resolution'] ?? 'refund') === 'exchange' ? __('theme::order.exchange') : __('theme::order.return_refund') }}
                        · <span>{{ __('storefront::messages.return_statuses.'.$return['status']) }}</span>
                    </p>
                    @if ($return['status'] === 'requested')
                        <form action="{{ vani_route('storefront.order.returns.cancel', [$order['id'], $return['id']]) }}" method="post">
                            @csrf
                            <button type="submit" class="text-sm text-red-700 underline">{{ __('theme::order.cancel_request') }}</button>
                        </form>
                    @endif
                </div>
                <ul class="mt-2 text-slate-600">
                    @foreach ($return['lines'] as $line)
                        <li>{{ $line['name'] }} × {{ $line['quantity'] }}</li>
                    @endforeach
                </ul>
                @if ($return['reason'] ?? null)
                    <p class="mt-1 text-slate-500">{{ __('theme::order.reason_value', ['reason' => $return['reason']]) }}</p>
                @endif
                @if (($return['resolution'] ?? 'refund') === 'refund')
                    <p class="mt-1 text-slate-500">{{ __('theme::order.estimated_refund_amount', ['amount' => $return['refund']['formatted'] ?? '']) }}</p>
                @endif
                @if (($return['attachments'] ?? []) !== [])
                    <div class="mt-2 flex flex-wrap gap-2" data-return-photos>
                        @foreach ($return['attachments'] as $attachment)
                            <a href="{{ $attachment['url'] }}" target="_blank" rel="noopener"><img src="{{ $attachment['url'] }}" alt="{{ __('theme::order.attached_photo') }}" class="h-16 w-16 rounded object-cover" loading="lazy"></a>
                        @endforeach
                    </div>
                @endif
                {{-- Yêu cầu chờ duyệt/đã duyệt, chưa đủ 5 ảnh: khách gửi thêm ảnh (CSKH hỏi thêm ảnh lỗi…). --}}
                @if ($return['can_add_photos'] ?? false)
                    <form action="{{ vani_route('storefront.order.returns.photos', [$order['id'], $return['id']]) }}" method="post" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-2" data-return-add-photos>
                        @csrf
                        <label class="block">
                            <span class="block text-slate-600">{{ __('theme::order.add_more_photos') }}</span>
                            <input name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required class="mt-1 text-sm">
                        </label>
                        <button type="submit" class="rounded-[var(--radius-theme)] border border-slate-300 px-3 py-1.5">{{ __('theme::order.send_photos') }}</button>
                        @error('photos')<p class="w-full text-red-600">{{ $message }}</p>@enderror
                        @error('photos.*')<p class="w-full text-red-600">{{ $message }}</p>@enderror
                    </form>
                @endif
                @if ($return['return_tracking_number'] ?? null)
                    <p class="mt-1 text-slate-500">{{ __('theme::order.return_tracking_number_number', ['number' => $return['return_tracking_number']]) }}</p>
                @endif
                {{-- Đã duyệt: khách báo đã gửi hàng về (§3.13-2), mã vận đơn tuỳ chọn. --}}
                @if ($return['status'] === 'approved')
                    <form action="{{ vani_route('storefront.order.returns.shipped', [$order['id'], $return['id']]) }}" method="post" class="mt-3 flex flex-wrap items-end gap-2" data-return-shipped>
                        @csrf
                        <label class="text-sm">
                            <span class="block text-slate-500">{{ __('theme::order.return_tracking_number_optional') }}</span>
                            <input name="tracking_number" maxlength="64" class="mt-1 rounded border border-slate-300 px-2 py-1">
                        </label>
                        <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-1.5 text-sm font-medium text-white">{{ __('theme::order.mark_shipped_back') }}</button>
                    </form>
                @endif
            </div>
        @endforeach

        @if (($returnForm ?? null) !== null)
            <details class="rounded-[var(--radius-theme)] border border-slate-200 p-4" @if ($errors->has('lines') || $errors->has('business')) open @endif>
                <summary class="cursor-pointer font-medium">{{ __('theme::order.request_return_exchange') }}</summary>
                <form action="{{ vani_route('storefront.order.returns.store', $order['id']) }}" method="post" class="mt-4 space-y-4 text-sm" enctype="multipart/form-data">
                    @csrf
                    @if ($returnForm['deadline_label'])
                        <p class="text-slate-500">{{ __('theme::order.return_deadline_date', ['date' => $returnForm['deadline_label']]) }}</p>
                    @endif

                    <fieldset>
                        <legend class="mb-2 font-medium">{{ __('theme::order.return_intent_prefix') }}</legend>
                        <label class="mr-4"><input type="radio" name="resolution" value="refund" @checked(old('resolution', 'refund') === 'refund')> {{ __('theme::order.return_refund_short') }}</label>
                        <label><input type="radio" name="resolution" value="exchange" @checked(old('resolution') === 'exchange')> {{ __('theme::order.exchange_free_option') }}</label>
                    </fieldset>

                    <table class="w-full">
                        <thead class="text-left text-slate-500">
                            <tr><th class="py-1 font-normal">{{ __('theme::common.products') }}</th><th class="py-1 font-normal">{{ __('theme::common.quantity') }}</th><th class="py-1 font-normal">{{ __('theme::order.exchange_if_exchanging') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($returnForm['lines'] as $line)
                                <tr class="border-t border-slate-100 align-top">
                                    <td class="py-2 pr-2">{{ $line['name'] }}<span class="block text-xs text-slate-500">{{ $line['variant'] }}</span></td>
                                    <td class="py-2 pr-2">
                                        <label class="sr-only" for="return-qty-{{ $line['id'] }}">{{ __('theme::order.quantity_return_name', ['name' => $line['name']]) }}</label>
                                        <select id="return-qty-{{ $line['id'] }}" name="lines[{{ $line['id'] }}][quantity]" class="rounded border border-slate-300 px-2 py-1">
                                            @for ($quantity = 0; $quantity <= $line['returnable']; $quantity++)
                                                <option value="{{ $quantity }}" @selected((int) old("lines.{$line['id']}.quantity", 0) === $quantity)>{{ $quantity }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td class="py-2">
                                        @if ($line['exchange_options'] !== [])
                                            <label class="sr-only" for="return-exchange-{{ $line['id'] }}">{{ __('theme::order.exchange_item_for', ['name' => $line['name']]) }}</label>
                                            <select id="return-exchange-{{ $line['id'] }}" name="lines[{{ $line['id'] }}][exchange_variant_id]" class="rounded border border-slate-300 px-2 py-1">
                                                <option value="">{{ __('theme::order.select_size_colour') }}</option>
                                                @foreach ($line['exchange_options'] as $option)
                                                    <option value="{{ $option['variant_id'] }}" @disabled(! $option['available']) @selected((int) old("lines.{$line['id']}.exchange_variant_id") === $option['variant_id'])>
                                                        {{ $option['label'] }}{{ $option['available'] ? '' : __('theme::order.option_out_of_stock_suffix') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-xs text-slate-500">{{ __('theme::order.no_exchange_options') }}</span>
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
                        <label for="return-reason" class="block">{{ __('theme::order.reason') }}</label>
                        <select id="return-reason" name="reason_code" class="mt-1 rounded border border-slate-300 px-2 py-1">
                            @foreach ($returnForm['reasons'] as $code => $label)
                                <option value="{{ $code }}" @selected(old('reason_code') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="return-note" class="block">{{ __('theme::order.note_shop_optional') }}</label>
                        <textarea id="return-note" name="note" rows="2" maxlength="500" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ old('note') }}</textarea>
                    </div>
                    <div>
                        <label for="return-photos" class="block">{{ __('theme::order.return_photos_hint') }}</label>
                        <input id="return-photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="mt-1 text-sm">
                        @error('photos')<p class="mt-1 text-red-600">{{ $message }}</p>@enderror
                        @error('photos.*')<p class="mt-1 text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-xs text-slate-500">{{ __('theme::order.return_process_hint') }}</p>
                    <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::order.submit_request') }}</button>
                </form>
            </details>
        @endif
    </section>
@endif
