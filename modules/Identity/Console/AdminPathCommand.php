<?php

declare(strict_types=1);

namespace Modules\Identity\Console;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Application\AdminPathSetting;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\AdminPath;

/**
 * Xem/đổi đường dẫn Admin từ máy chủ — cứu hộ khi quên đường dẫn hoặc không còn super admin đăng nhập được.
 */
final class AdminPathCommand extends Command
{
    protected $signature = 'vani:admin-path {path? : Đường dẫn mới, vd. quan-tri-7f3k} {--reset : Về mặc định "admin"}';

    protected $description = 'Xem hoặc đổi đường dẫn Admin (lưu trong DB, mặc định "admin")';

    public function handle(AdminPathSetting $setting, CurrentContext $context): int
    {
        $path = $this->option('reset') ? AdminPathSetting::DEFAULT : $this->argument('path');
        if (! is_string($path) || $path === '') {
            $this->line(url('/'.AdminPath::prefix()));

            return self::SUCCESS;
        }

        try {
            $context->runAs(ContextScope::system('console'), fn () => $setting->change($path));
        } catch (ValidationException $exception) {
            $this->error((string) collect($exception->errors())->flatten()->first());

            return self::FAILURE;
        }
        $this->info('Đường dẫn Admin mới: '.url('/'.$setting->current()));

        return self::SUCCESS;
    }
}
