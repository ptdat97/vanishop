<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Closure;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Application\StaleRecord;
use Modules\Catalog\Domain\Category\CategoryCycle;
use Modules\Catalog\Domain\Category\CategoryHasChildren;
use Modules\Catalog\Domain\Category\CategoryPath;
use Modules\Catalog\Domain\Category\CategoryTooDeep;

/**
 * Đổi lỗi nghiệp vụ thành lỗi form để Inertia hiển thị cạnh trường tương ứng.
 */
trait ConvertsDomainErrors
{
    /**
     * @template T
     *
     * @param  Closure(): T  $action
     * @return T
     */
    private function orFormError(Closure $action): mixed
    {
        try {
            return $action();
        } catch (StaleRecord) {
            throw ValidationException::withMessages(['lock_version' => __('catalog::messages.stale')]);
        } catch (CategoryCycle) {
            throw ValidationException::withMessages(['parent_id' => __('catalog::messages.category_cycle')]);
        } catch (CategoryTooDeep) {
            throw ValidationException::withMessages(['parent_id' => __('catalog::messages.category_too_deep', ['max' => CategoryPath::MAX_DEPTH])]);
        } catch (CategoryHasChildren) {
            throw ValidationException::withMessages(['category' => __('catalog::messages.category_has_children')]);
        }
    }
}
