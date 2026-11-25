<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Storefront\Application\PageBlocks;

/** Bản nháp của Builder trang chủ: xem trước chưa lưu và nhập JSON đã xuất. */
final class BuilderDrafts
{
    public function __construct(private readonly PageBlocks $blocks) {}

    public function preview(Request $request): JsonResponse
    {
        $input = $request->validate($this->blocks->inputRules());
        $clean = $this->blocks->validate($input['blocks'])['blocks'];

        return response()->json(['html' => view('storefront::builder-preview', ['blocks' => $this->blocks->render($clean)])->render()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function import(Request $request): JsonResponse
    {
        $input = $request->validate([
            'version' => ['required', 'integer', 'in:1'],
            'blocks' => ['present', 'array', 'list', 'max:'.PageBlocks::MAX_BLOCKS],
            'blocks.*' => ['array'],
            'blocks.*.type' => ['required', 'string', 'max:64'],
            'blocks.*.config' => ['nullable', 'array'],
        ]);
        $types = $this->blocks->types();
        foreach ($input['blocks'] as $index => $block) {
            if (! isset($types[$block['type']])) {
                throw ValidationException::withMessages(["blocks.{$index}.type" => __('storefront::texts.page_blocks.block_type_does_not_exist')]);
            }
        }

        return response()->json(['blocks' => $this->blocks->validate(array_map(
            fn (array $block): array => ['type' => $block['type'], 'config' => $block['config'] ?? []], $input['blocks'],
        ))['blocks']]);
    }
}
