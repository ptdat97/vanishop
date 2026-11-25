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
use Modules\Storefront\Application\Builder\SystemFirst;
use Modules\Storefront\Contracts\ContextualStorefrontBlock;
use Modules\Storefront\Contracts\Data\BlockContext;
use Modules\Storefront\Contracts\NormalizesBlockConfiguration;
use Modules\Storefront\Contracts\StorefrontBlock;
use Throwable;

/**
 * Lõi block dùng chung của storefront: registry khối, schema biên tập, validation theo {@see BlockLayout} và renderer.
 * Nơi lưu cấu hình thuộc về người dùng lõi này: Builder Engine (surface) hoặc trang theo thực thể (bộ sưu tập).
 */
final class PageBlocks
{
    public const MAX_BLOCKS = 30;

    public function __construct(
        private readonly Extensions $extensions,
        private readonly MediaDirectory $media,
    ) {}

    /**
     * @return array<string, StorefrontBlock>
     */
    public function types(): array
    {
        return SystemFirst::order(
            $this->extensions->implementations(StorefrontBlock::TAG, StorefrontBlock::class, fn (StorefrontBlock $block): string => $block->type()),
            $this->extensions,
        );
    }

    /**
     * Loại block cho trình biên tập: hệ thống trước, plugin sau; `plugin` = id plugin khai báo (null = hệ thống).
     *
     * @return list<array{type: string, label: string, plugin: string|null, fields: list<array<string, mixed>>}>
     */
    public function editorTypes(): array
    {
        $result = [];
        foreach ($this->types() as $type) {
            $result[] = [
                'type' => $type->type(),
                'label' => $type->label(),
                'plugin' => $this->extensions->ownerOf($type),
                'fields' => array_values(array_map(fn (FieldDefinition $field): array => $field->toArray(), array_filter($type->fields(), fn (FieldDefinition $field): bool => $field->editable))),
            ];
        }

        return $result;
    }

    /** @return array<string, list<string>> */
    public function editorZones(BlockLayout $layout): array
    {
        return array_map(fn (StorefrontBlock $type): array => $this->allowedZones($layout, $type), $this->types());
    }

    /** @return list<string> */
    public function allowedZones(BlockLayout $layout, StorefrontBlock $type): array
    {
        $slots = $type instanceof ContextualStorefrontBlock ? $type->slots() : null;

        return array_values(array_filter($layout->zoneKeys(), fn (string $zone): bool => $layout->accepts($zone, $type->type())
            && ($slots === null || in_array($layout->zones[$zone]->slot, $slots, true))));
    }

    /**
     * @param  list<array{type: string, config: array<string, mixed>, zone?: string}>  $blocks
     * @return list<array{type: string, config: array<string, mixed>, zone?: string}>
     */
    public function editorBlocks(array $blocks): array
    {
        $types = $this->types();

        return array_map(function (array $block) use ($types): array {
            $type = $types[$block['type']] ?? null;
            if ($type instanceof NormalizesBlockConfiguration) {
                $block['config'] = $type->normalize($block['config']);
            }

            return $block;
        }, $blocks);
    }

    /** @return array<string, list<mixed>> */
    public function inputRules(BlockLayout $layout): array
    {
        $rules = ['blocks' => ['present', 'array', 'max:'.self::MAX_BLOCKS], 'blocks.*' => ['array'], 'blocks.*.type' => ['required', 'string', 'max:64'], 'blocks.*.config' => ['nullable', 'array']];
        if ($layout->zoned()) {
            $rules['blocks.*.zone'] = ['required', Rule::in($layout->zoneKeys())];
        }

        return $rules;
    }

    /**
     * Validate theo schema khối và bố cục trang; trang nhiều vùng giữ `zone` của từng khối.
     *
     * @param  array<array{type?: mixed, config?: mixed, zone?: mixed}>  $blocks
     * @return array{blocks: list<array{type: string, config: array<string, mixed>, zone?: string}>, media_ids: list<int>}
     */
    public function validate(array $blocks, BlockLayout $layout): array
    {
        $blocks = array_values($blocks);
        if (count($blocks) > self::MAX_BLOCKS) {
            throw ValidationException::withMessages(['blocks' => __('storefront::texts.page_blocks.most').self::MAX_BLOCKS.__('storefront::texts.page_blocks.blocks')]);
        }
        $types = $this->types();
        $clean = [];
        $mediaFields = [];
        $counts = [];
        foreach ($blocks as $index => $block) {
            $type = $types[(string) ($block['type'] ?? '')] ?? throw ValidationException::withMessages(["blocks.{$index}.type" => __('storefront::texts.page_blocks.block_type_does_not_exist')]);
            $zone = $layout->zoned() ? (string) ($block['zone'] ?? '') : ($layout->zoneKeys()[0] ?? '');
            $counts[$zone] = ($counts[$zone] ?? 0) + 1;
            $limit = $layout->limit($zone);
            if (! in_array($zone, $this->allowedZones($layout, $type), true) || ($limit !== null && $counts[$zone] > $limit)) {
                throw ValidationException::withMessages(["blocks.{$index}.zone" => __('storefront::messages.block_zone_rule')]);
            }
            $fields = $type->fields();
            $config = (array) ($block['config'] ?? []);
            if ($type instanceof NormalizesBlockConfiguration) {
                $config = $type->normalize($config);
            }
            $validator = Validator::make($config, $this->fieldRules($fields));
            if ($validator->fails()) {
                throw ValidationException::withMessages(collect($validator->errors()->messages())->mapWithKeys(fn ($messages, $key): array => ["blocks.{$index}.config.{$key}" => $messages])->all());
            }
            if ($type instanceof RichTextBlock && RichTextRenderer::textLength((string) ($config['body'] ?? '')) > RichTextBlock::MAX_TEXT_LENGTH) {
                throw ValidationException::withMessages([
                    "blocks.{$index}.config.body" => __('storefront::texts.page_blocks.content_must_not_exceed').RichTextBlock::MAX_TEXT_LENGTH.__('storefront::texts.page_blocks.characters'),
                ]);
            }
            $clean[] = ['type' => $type->type(), 'config' => $this->cleanFields($config, $fields, "blocks.{$index}.config", $mediaFields)]
                + ($layout->zoned() ? ['zone' => $zone] : []);
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
     * @param  list<FieldDefinition>  $fields
     * @return array<string, list<mixed>>
     */
    private function fieldRules(array $fields, string $prefix = ''): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $key = $prefix.$field->key;
            $rules[$key] = $field->rules();
            if ($field->type === 'repeater') {
                $rules[$key.'.*'] = ['array'];
                $rules += $this->fieldRules($field->fields, $key.'.*.');
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  list<FieldDefinition>  $fields
     * @param  array<string, array{id: int, kind: string}>  $mediaFields
     * @return array<string, mixed>
     */
    private function cleanFields(array $config, array $fields, string $path, array &$mediaFields): array
    {
        $clean = [];
        foreach ($fields as $field) {
            if (! array_key_exists($field->key, $config)) {
                continue;
            }
            $value = $config[$field->key];
            $key = "{$path}.{$field->key}";
            if ($field->type === 'repeater' && is_array($value)) {
                foreach ($value as $index => $row) {
                    $value[$index] = $this->cleanFields($row, $field->fields, "{$key}.{$index}", $mediaFields);
                }
            }
            if (in_array($field->type, ['media', 'video'], true) && $value !== null) {
                $mediaFields[$key] = ['id' => (int) $value, 'kind' => $field->type === 'video' ? 'video' : 'image'];
            }
            $clean[$field->key] = $value;
        }

        return $clean;
    }

    /**
     * Dữ liệu các khối đã cấu hình — một nguồn cho mọi kênh (ADR-044 đợt A2): Storefront API `GET /pages/home` trả dữ liệu
     * này, Blade render nó bằng view của khối. Khối lỗi (loại không còn, resolve lỗi) bị bỏ, ghi log.
     *
     * @param  list<array{type: string, config: array<string, mixed>}>  $blocks
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    public function resolve(array $blocks, ?BlockContext $blockContext = null): array
    {
        if ($blocks === []) {
            return [];
        }
        $types = $this->types();
        $resolved = [];
        foreach ($blocks as $block) {
            $type = $types[$block['type']] ?? null;
            if ($type === null) {
                continue;
            }
            try {
                $config = $type instanceof NormalizesBlockConfiguration ? $type->normalize($block['config']) : $block['config'];
                $data = $this->extensions->call($type, function () use ($type, $config, $blockContext): ?array {
                    if ($type instanceof ContextualStorefrontBlock) {
                        return $blockContext !== null && in_array($blockContext->slot, $type->slots(), true)
                            ? $type->resolveInContext($config, App::getLocale(), $blockContext)
                            : null;
                    }

                    return $type->resolve($config, App::getLocale());
                }, null, 'storefront.block');
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
    public function render(array $blocks, array $context = [], ?BlockContext $blockContext = null): array
    {
        $types = $this->types();
        $rendered = [];
        foreach ($this->resolve($blocks, $blockContext) as $block) {
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
