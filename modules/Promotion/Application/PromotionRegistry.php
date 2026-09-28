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
        $rules = [];
        foreach ($this->extensions->tagged(self::RULES_TAG) as $rule) {
            if ($rule instanceof PromotionRule) {
                $rules[$rule->type()] = $rule;
            }
        }

        return $rules;
    }

    /**
     * @return array<string, PromotionAction>
     */
    public function actions(): array
    {
        $actions = [];
        foreach ($this->extensions->tagged(self::ACTIONS_TAG) as $action) {
            if ($action instanceof PromotionAction) {
                $actions[$action->type()] = $action;
            }
        }

        return $actions;
    }
}
