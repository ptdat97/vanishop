<?php

/*
| Chuỗi giao diện storefront theo ngôn ngữ (ADR-038): view của theme và plugin bọc chữ hiển thị bằng __()/trans_choice
| (khoá = câu tiếng Việt), mỗi khoá có bản dịch trong lang/<locale>.json của theme hoặc plugin sở hữu view.
*/

const VIETNAMESE = 'àáảãạăằắẳẵặâầấẩẫậđèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬĐÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴ';

/**
 * @return array<string, array{views: string, lang: string}> view gốc => thư mục lang của chủ sở hữu
 */
function storefrontViewOwners(): array
{
    $root = realpath(__DIR__.'/../..');
    $owners = ["{$root}/custom/theme/vani-base/views" => "{$root}/custom/theme/vani-base/lang"];
    // Plugin có chuỗi giao diện storefront theo JSON (Resources/lang/en.json) và view.
    foreach (glob("{$root}/custom/plugin/*/Resources/lang/en.json") as $file) {
        if (is_dir(dirname($file, 2).'/views')) {
            $owners[dirname($file, 2).'/views'] = dirname($file);
        }
    }

    return $owners;
}

it('view storefront không có chữ tiếng Việt ngoài __() / trans_choice (trừ comment Blade)', function () {
    $offenders = [];
    foreach (array_keys(storefrontViewOwners()) as $views) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($file->getPathname()));
            foreach (explode("\n", (string) $source) as $number => $line) {
                $rest = preg_replace("/(?:__|trans_choice)\\('(?:[^'\\\\]|\\\\.)*'/u", '', $line);
                // Comment JS trong <script> của view (không hiển thị).
                $rest = preg_replace('#(?<!:)//.*$|/\*.*?\*/#u', '', (string) $rest);
                if (preg_match('/['.VIETNAMESE.']/u', (string) $rest) === 1) {
                    $offenders[] = substr($file->getPathname(), strlen(realpath(__DIR__.'/../..')) + 1).':'.($number + 1);
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('mọi khoá giao diện storefront có bản dịch tiếng Anh', function () {
    $missing = [];
    foreach (storefrontViewOwners() as $views => $lang) {
        $translations = json_decode((string) file_get_contents("{$lang}/en.json"), true, flags: JSON_THROW_ON_ERROR);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            preg_match_all("/(?:__|trans_choice)\\('((?:[^'\\\\]|\\\\.)*)'/u", (string) file_get_contents($file->getPathname()), $matches);
            foreach ($matches[1] as $key) {
                $key = str_replace("\\'", "'", $key);
                // Khoá dạng `module::file.key` là lang PHP của module (đã có bản dịch riêng).
                if (! str_contains($key, '::') && preg_match('/['.VIETNAMESE.']/u', $key) === 1 && ! array_key_exists($key, $translations)) {
                    $missing[] = $key;
                }
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});
