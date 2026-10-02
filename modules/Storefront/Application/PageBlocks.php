<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\Contracts\Extensions;
use Modules\Storefront\Contracts\StorefrontBlock;
use Modules\Tenancy\Contracts\Settings;
use Throwable;

/**
 * Page builder trang chủ: danh sách khối (loại + cấu hình) lưu ở cấu hình cửa hàng `core.storefront.home_blocks`.
 * Chưa cấu hình → trang chủ mặc định của theme.
 */
final class PageBlocks
{
    public const MAX_BLOCKS = 30;

    private const SETTING = 'storefront.home_blocks';

    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
    ) {}

    /**
     * @return array<string, StorefrontBlock>
     */
    public function types(): array
    {
        return $this->extensions->implementations(StorefrontBlock::TAG, StorefrontBlock::class, fn (StorefrontBlock $block): string => $block->type());
    }

    /**
     * @return list<array{type: string, config: array<string, mixed>}>|null null = chưa cấu hình
     */
    public function home(): ?array
    {
        $blocks = $this->settings->get('core', self::SETTING);

        return is_array($blocks) ? array_values($blocks) : null;
    }

    /**
     * @param  list<array{type?: mixed, config?: mixed}>  $blocks
     */
    public function saveHome(array $blocks): void
    {
        if (count($blocks) > self::MAX_BLOCKS) {
            throw ValidationException::withMessages(['blocks' => 'Tối đa '.self::MAX_BLOCKS.' khối.']);
        }

        $types = $this->types();
        $clean = [];
        foreach (array_values($blocks) as $index => $block) {
            $type = $types[(string) ($block['type'] ?? '')] ?? throw ValidationException::withMessages(["blocks.{$index}.type" => 'Loại khối không có hoặc plugin đã tắt.']);
            $fields = $type->fields();
            $config = (array) ($block['config'] ?? []);
            $rules = [];
            foreach ($fields as $field) {
                $rules[$field->key] = $field->rules();
            }
            $validator = Validator::make($config, $rules);
            if ($validator->fails()) {
                throw ValidationException::withMessages(collect($validator->errors()->messages())->mapWithKeys(fn ($messages, $key): array => ["blocks.{$index}.config.{$key}" => $messages])->all());
            }
            $known = array_map(fn (FieldDefinition $field): string => $field->key, $fields);
            $clean[] = ['type' => $type->type(), 'config' => array_intersect_key($config, array_flip($known))];
        }

        $this->settings->set('core', self::SETTING, $clean);
    }

    public function resetHome(): void
    {
        $this->settings->forget('core', self::SETTING);
    }

    /**
     * Render các khối đã cấu hình: khối lỗi (loại không còn, resolve/render lỗi) bị bỏ, ghi log.
     *
     * @param  list<array{type: string, config: array<string, mixed>}>  $blocks
     * @return list<array{type: string, html: HtmlString}>
     */
    public function render(array $blocks): array
    {
        $types = $this->types();
        $rendered = [];
        foreach ($blocks as $block) {
            $type = $types[$block['type'] ?? ''] ?? null;
            if ($type === null) {
                continue;
            }
            try {
                $data = $this->extensions->call($type, fn (): array => $type->resolve((array) ($block['config'] ?? []), App::getLocale()), null, 'storefront.block');
                if ($data !== null) {
                    $rendered[] = ['type' => $type->type(), 'html' => new HtmlString(view($type->view(), $data)->render())];
                }
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Render khối trang chủ lỗi, bỏ qua.', ['type' => $block['type']]);
            }
        }

        return $rendered;
    }
}
