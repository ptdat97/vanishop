<?php

use Illuminate\Support\Facades\File;
use Modules\Storefront\Application\Theme\ThemeTranslationLoader;

beforeEach(function () {
    $this->root = storage_path('framework/testing/theme-lang-'.uniqid());
    foreach (['parent' => ['title' => 'Cha', 'nested' => ['a' => 'A cha', 'b' => 'B cha']], 'child' => ['title' => 'Con', 'nested' => ['b' => 'B con']]] as $theme => $lines) {
        File::ensureDirectoryExists("{$this->root}/{$theme}/vi");
        File::put("{$this->root}/{$theme}/vi/common.php", '<?php return '.var_export($lines, true).';');
    }
    File::ensureDirectoryExists("{$this->root}/parent/en");
    File::put("{$this->root}/parent/en/common.php", "<?php return ['title' => 'Parent'];");
    $this->loader = app('translator')->getLoader();
});

afterEach(fn () => File::deleteDirectory($this->root));

it('ADR-049: theme:: gộp theo chuỗi cha → con, theme con ghi đè từng khoá; namespace khác qua loader gốc', function () {
    expect($this->loader)->toBeInstanceOf(ThemeTranslationLoader::class);
    $this->loader->usePaths(["{$this->root}/parent", "{$this->root}/child"]);
    app('translator')->setLoaded([]);

    expect(__('theme::common.title', [], 'vi'))->toBe('Con')
        ->and(__('theme::common.nested.a', [], 'vi'))->toBe('A cha')
        ->and(__('theme::common.nested.b', [], 'vi'))->toBe('B con')
        ->and(__('theme::common.title', [], 'en'))->toBe('Parent')
        ->and(__('theme::common.missing', [], 'vi'))->toBe('theme::common.missing')
        ->and(__('storefront::messages.maintenance_title', [], 'vi'))->not->toBe('storefront::messages.maintenance_title');
});
