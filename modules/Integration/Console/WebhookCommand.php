<?php

declare(strict_types=1);

namespace Modules\Integration\Console;

use Illuminate\Console\Command;
use Modules\Integration\Application\ClientProvisioning;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class WebhookCommand extends Command
{
    protected $signature = 'vani:integration:webhook
        {client : Mã Integration Client}
        {url : URL HTTPS nhận webhook}
        {--event=* : Loại event (order.created, payment.captured…; * = tất cả)}';

    protected $description = 'Đăng ký webhook subscription cho một Integration Client (secret ký hiện một lần).';

    public function handle(ClientProvisioning $provisioning, CurrentContext $context): int
    {
        return $context->runAs(ContextScope::system('cli integration webhook'), function () use ($provisioning): int {
            $events = (array) $this->option('event');
            [$subscription, $secret] = $provisioning->subscribe((string) $this->argument('client'), (string) $this->argument('url'), $events === [] ? ['*'] : $events);
            $this->info("Subscription #{$subscription->id} → {$subscription->url}");
            $this->line("Signing secret: {$secret}");
            $this->warn('Lưu secret ngay — không hiển thị lại.');

            return self::SUCCESS;
        });
    }
}
