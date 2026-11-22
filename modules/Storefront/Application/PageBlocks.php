<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\Contracts\Extensions;
use Modules\Storefront\Application\Blocks\BlockLayout;
use Modules\Storefront\Application\Blocks\RichTextBlock;
use Modules\Storefront\Application\Blocks\RichTextRenderer;
use Modules\Storefront\Contracts\StorefrontBlock;
use Modules\Tenancy\Contracts\Settings;
use Throwable;

/**
 * Page builder chung của storefront: registry khối, schema biên tập, validation và renderer.
 * Mỗi trang chỉ khác {@see BlockLayout} (vùng + giới hạn) và nơi lưu cấu hình; trang chủ lưu ở `core.storefront.home_blocks`.
 */
final class PageBlocks
{
    public const MAX_BLOCKS = 30;

    private const SETTING = 'storefront.home_blocks';

    /** Chủ sở hữu khai báo ảnh đang dùng của các trường `media` trong khối trang chủ (Thư viện ảnh không cho xoá). */
    public const MEDIA_OWNER = 'storefront.home';

    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly MediaDirectory $media,
    ) {}

    /**
     * @return array<string, StorefrontBlock>
     */
    public function types(): array
    {
        return $this->extensions->implementations(StorefrontBlock::TAG, StorefrontBlock::class, fn (StorefrontBlock $block): string => $block->type());
    }

    /** @return list<array{type: string, label: string, fields: list<array<string, mixed>>}> */
    public function editorTypes(): array
    {
        return array_values(array_map(fn (StorefrontBlock $type): array => [
            'type' => $type->type(),
            'label' => $type->label(),
            'fields' => array_map(fn (FieldDefinition $field): array => $field->toArray(), $type->fields()),
        ], $this->types()));
    }

    /** @return array<string, list<mixed>> */
    public function inputRules(BlockLayout $layout): array
    {
        $rules = ['blocks' => ['present', 'array'], 'blocks.*.type' => ['required', 'string', 'max:64'], 'blocks.*.config' => ['nullable', 'array']];
        if ($layout->zoned()) {
            $rules['blocks.*.zone'] = ['required', Rule::in($layout->zoneKeys())];
        }

        return $rules;
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
        ['blocks' => $clean, 'media_ids' => $mediaIds] = $this->validate($blocks);
        $this->settings->set('core', self::SETTING, $clean);
        $this->media->syncUsages(self::MEDIA_OWNER, 1, 'blocks', $mediaIds);
    }

    /**
     * Validate theo schema khối và bố cục trang; trang nhiều vùng giữ `zone` của từng khối.
     *
     * @param  list<array{type?: mixed, config?: mixed, zone?: mixed}>  $blocks
     * @return array{blocks: list<array{type: string, config: array<string, mixed>, zone?: string}>, media_ids: list<int>}
     */
    public function validate(array $blocks, ?BlockLayout $layout = null): array
    {
        $layout ??= BlockLayout::home();
        $blocks = array_values($blocks);
        if (count($blocks) > self::MAX_BLOCKS) {
            throw ValidationException::withMessages(['blocks' => __('storefront::texts.page_blocks.most').self::MAX_BLOCKS.__('storefront::texts.page_blocks.blocks')]);
        }
        $this->validateZones($blocks, $layout);

        $types = $this->types();
        $clean = [];
        $mediaFields = [];
        foreach ($blocks as $index => $block) {
            $type = $types[(string) ($block['type'] ?? '')] ?? throw ValidationException::withMessages(["blocks.{$index}.type" => __('storefront::texts.page_blocks.block_type_does_not_exist')]);
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
            if ($type instanceof RichTextBlock && RichTextRenderer::textLength((string) ($config['body'] ?? '')) > RichTextBlock::MAX_TEXT_LENGTH) {
                throw ValidationException::withMessages([
                    "blocks.{$index}.config.body" => __('storefront::texts.page_blocks.content_must_not_exceed').RichTextBlock::MAX_TEXT_LENGTH.__('storefront::texts.page_blocks.characters'),
                ]);
            }
            $known = array_map(fn (FieldDefinition $field): string => $field->key, $fields);
            $clean[] = ['type' => $type->type(), 'config' => array_intersect_key($config, array_flip($known))]
                + ($layout->zoned() ? ['zone' => (string) $block['zone']] : []);
            foreach ($fields as $field) {
                if (in_array($field->type, ['media', 'video'], true) && is_numeric($config[$field->key] ?? null)) {
                    $mediaFields["blocks.{$index}.config.{$field->key}"] = ['id' => (int) $config[$field->key], 'kind' => $field->type === 'video' ? 'video' : 'image'];
                }
            }
        }

        $mediaIds = array_values(array_unique(array_column($mediaFields, 'id')));
        $files = $mediaIds === [] ? [] : $this->media->find($mediaIds);
        $errors = [];
        foreach ($mediaFields as $path => $field) {
            $file = $files[$field['id']] ?? null;
            if ($file === null || ! str_starts_with($file->mimeType, $field['kind'].'/')) {
                $errors[$path] = __('storefront::texts.page_blocks.choose_'.$field['kind']);
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return ['blocks' => $clean, 'media_ids' => $mediaIds];
    }

    /**
     * @param  list<array{type?: mixed, zone?: mixed}>  $blocks
     */
    private function validateZones(array $blocks, BlockLayout $layout): void
    {
        if (! $layout->zoned()) {
            return;
        }
        $counts = [];
        foreach ($blocks as $index => $block) {
            $zone = (string) ($block['zone'] ?? '');
            $counts[$zone] = ($counts[$zone] ?? 0) + 1;
            $limit = $layout->limit($zone);
            if (! $layout->accepts($zone, (string) ($block['type'] ?? '')) || ($limit !== null && $counts[$zone] > $limit)) {
                throw ValidationException::withMessages(["blocks.{$index}.zone" => __('storefront::messages.block_zone_rule')]);
            }
        }
    }

    public function resetHome(): void
    {
        $this->settings->forget('core', self::SETTING);
        $this->media->releaseUsages(self::MEDIA_OWNER, 1);
    }

    /**
     * Dữ liệu các khối đã cấu hình — một nguồn cho mọi kênh (ADR-044 đợt A2): Storefront API `GET /pages/home` trả dữ liệu
     * này, Blade render nó bằng view của khối. Khối lỗi (loại không còn, resolve lỗi) bị bỏ, ghi log.
     *
     * @param  list<array{type: string, config: array<string, mixed>}>  $blocks
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    public function resolve(array $blocks): array
    {
        $types = $this->types();
        $resolved = [];
        foreach ($blocks as $block) {
            $type = $types[$block['type'] ?? ''] ?? null;
            if ($type === null) {
                continue;
            }
            try {
                $data = $this->extensions->call($type, fn (): array => $type->resolve((array) ($block['config'] ?? []), App::getLocale()), null, 'storefront.block');
                if ($data !== null) {
                    $resolved[] = ['type' => $type->type(), 'data' => $data];
                }
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Dựng khối storefront lỗi, bỏ qua.', ['type' => $block['type']]);
            }
        }

        return $resolved;
    }

    /**
     * Dữ liệu khối theo vùng của bố cục, cho Storefront API.
     *
     * @param  list<array{type: string, config: array<string, mixed>, zone?: string}>  $blocks
     * @return array<string, list<array{type: string, data: array<string, mixed>}>>
     */
    public function resolveZones(BlockLayout $layout, array $blocks): array
    {
        return array_map($this->resolve(...), $layout->group($blocks));
    }

    /**
     * Render các khối đã cấu hình cho Blade: cùng dữ liệu với {@see self::resolve()}; khối render lỗi bị bỏ, ghi log.
     * `$context` là biến thêm cho view của khối (vd. ngữ cảnh trang khi khối làm phần đầu trang).
     *
     * @param  list<array{type: string, config: array<string, mixed>}>  $blocks
     * @param  array<string, mixed>  $context
     * @return list<array{type: string, data: array<string, mixed>, html: HtmlString}>
     */
    public function render(array $blocks, array $context = []): array
    {
        $types = $this->types();
        $rendered = [];
        foreach ($this->resolve($blocks) as $block) {
            try {
                $html = new HtmlString(view($types[$block['type']]->view(), $block['data'] + $context)->render());
                $rendered[] = [...$block, 'html' => $html];
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Render khối storefront lỗi, bỏ qua.', ['type' => $block['type']]);
            }
        }

        return $rendered;
    }

    /**
     * @param  list<array{type: string, config: array<string, mixed>, zone?: string}>  $blocks
     * @param  array<string, array<string, mixed>>  $zoneContext  biến thêm cho view theo từng vùng
     * @return array<string, list<array{type: string, data: array<string, mixed>, html: HtmlString}>>
     */
    public function renderZones(BlockLayout $layout, array $blocks, array $zoneContext = []): array
    {
        $zones = $layout->group($blocks);
        foreach ($zones as $zone => $zoneBlocks) {
            $zones[$zone] = $this->render($zoneBlocks, $zoneContext[$zone] ?? []);
        }

        return $zones;
    }
}
