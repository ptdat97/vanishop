<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Promotion\Contracts\PromotionAction;
use Modules\Promotion\Contracts\PromotionRule;

/**
 * Rule/action theo type, chỉ gồm implementation có hiệu lực trong phạm vi hiện tại
 * (plugin tắt ở brand này thì type của nó không có mặt → khuyến mãi dùng type đó bị bỏ qua).
 */
final class PromotionRegistry
{
    public const RULES_TAG = PromotionRule::TAG;

    public const ACTIONS_TAG = PromotionAction::TAG;

    public function __construct(private readonly Extensions $extensions) {}

    public function rule(string $type): ?PromotionRule
    {
        return $this->rules()[$type] ?? null;
    }

    public function action(string $type): ?PromotionAction
    {
        return $this->actions()[$type] ?? null;
    }

    /**
     * @return array<string, PromotionRule>
     */
    public function rules(): array
    {
        return $this->extensions->implementations(self::RULES_TAG, PromotionRule::class, fn (PromotionRule $rule): string => $rule->type());
    }

    /**
     * @return array<string, PromotionAction>
     */
    public function actions(): array
    {
        return $this->extensions->implementations(self::ACTIONS_TAG, PromotionAction::class, fn (PromotionAction $action): string => $action->type());
    }
}
