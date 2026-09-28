<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Contracts\Container\Container;
use Modules\Promotion\Contracts\PromotionAction;
use Modules\Promotion\Contracts\PromotionRule;

/**
 * Rule/action theo type, lấy từ container tag. Plugin bị tắt thì type của nó không có mặt.
 */
final class PromotionRegistry
{
    public const RULES_TAG = 'vani.promotion.rules';

    public const ACTIONS_TAG = 'vani.promotion.actions';

    public function __construct(private readonly Container $container) {}

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
        foreach ($this->container->tagged(self::RULES_TAG) as $rule) {
            $rules[$rule->type()] = $rule;
        }

        return $rules;
    }

    /**
     * @return array<string, PromotionAction>
     */
    public function actions(): array
    {
        $actions = [];
        foreach ($this->container->tagged(self::ACTIONS_TAG) as $action) {
            $actions[$action->type()] = $action;
        }

        return $actions;
    }
}
