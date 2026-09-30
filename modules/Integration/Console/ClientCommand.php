<?php

declare(strict_types=1);

namespace Modules\Integration\Console;

use Illuminate\Console\Command;
use Modules\Integration\Application\ClientProvisioning;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Tạo/cập nhật Integration Client và cấp key. Secret chỉ hiện MỘT lần.
 */
final class ClientCommand extends Command
{
    protected $signature = 'vani:integration:client
        {code : Mã client (vd. erp-main) — cũng là giá trị stock_authority của location do client quản lý}
        {--name= : Tên hiển thị}
        {--scope=* : Scope (orders:read, orders:write, inventory:write, events:read)}
        {--brand=* : Brand id trong data scope (bỏ trống = mọi brand)}
        {--ip=* : IP/CIDR được phép (bỏ trống = không giới hạn)}
        {--rate-limit=600 : Request/phút}
        {--suspend : Tạm dừng client}
        {--new-key : Cấp thêm key (tối đa 2 key còn hiệu lực — thu hồi key cũ bằng --revoke)}
        {--revoke= : key_id cần thu hồi}';

    protected $description = 'Quản lý Integration Client (ERP/ODO/POS) và key HMAC.';

    public function handle(ClientProvisioning $provisioning, CurrentContext $context): int
    {
        return $context->runAs(ContextScope::system('cli integration client'), function () use ($provisioning): int {
            $client = $provisioning->upsert((string) $this->argument('code'), [
                'name' => $this->option('name') ?: null,
                'scopes' => (array) $this->option('scope'),
                'brand_ids' => array_map('intval', (array) $this->option('brand')),
                'ip_allowlist' => (array) $this->option('ip'),
                'rate_limit' => (int) $this->option('rate-limit'),
                'status' => $this->option('suspend') ? 'suspended' : null,
            ]);
            $this->info("Client {$client->code} ({$client->status}); scope: ".implode(', ', $client->scopes));

            if ($this->option('revoke')) {
                $provisioning->revokeKey($client, (string) $this->option('revoke'));
                $this->info("Đã thu hồi key {$this->option('revoke')}.");
            }

            if ($this->option('new-key') || $client->wasRecentlyCreated) {
                [$keyId, $secret] = $provisioning->issueKey($client);
                $this->line("Key ID: {$keyId}");
                $this->line("Secret: {$secret}");
                $this->warn('Lưu secret ngay — không hiển thị lại.');
            }

            return self::SUCCESS;
        });
    }
}
