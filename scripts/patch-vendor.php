<?php

$root = dirname(__DIR__);
$patchBase = $root.'/lang/vendor-patches';
$vendorBase = $root.'/vendor/laravel-lang/starter-kits';

// Seed each starter-kit source found in lang/vendor-patches/source/ with
// livewire strings so lang:update picks them all up.
$livewireSource = $vendorBase.'/source/livewire/main/livewire.json';
$livewire = file_exists($livewireSource)
    ? json_decode(file_get_contents($livewireSource), true)
    : [];

$targets = [];

foreach (glob($patchBase.'/source/*.json') as $patchFile) {
    $kit = basename($patchFile, '.json'); // e.g. react, vue, svelte
    $vendorSource = $vendorBase.'/source/'.$kit.'/main/'.$kit.'.json';

    if ($livewire && file_exists($vendorSource)) {
        $vendor = json_decode(file_get_contents($vendorSource), true);
        $seeded = $vendor + $livewire; // vendor keys take precedence
        ksort($seeded);
        file_put_contents($vendorSource, json_encode($seeded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
        echo '  seeded: '.str_replace($root.'/', '', $vendorSource).' (from livewire)'."\n";
    }

    $targets[$patchFile] = $vendorSource;
}

foreach (glob($patchBase.'/locales/*.json') as $patchFile) {
    $locale = basename($patchFile, '.json');
    $targets[$patchFile] = $vendorBase.'/locales/'.$locale.'/json.json';
}

foreach ($targets as $patchFile => $vendorFile) {
    if (! file_exists($vendorFile)) {
        echo "  skip (vendor not found): $vendorFile\n";

        continue;
    }

    $patch = json_decode(file_get_contents($patchFile), true);
    $vendor = json_decode(file_get_contents($vendorFile), true);
    $merged = array_merge($vendor, $patch);
    ksort($merged);

    file_put_contents($vendorFile, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
    echo '  patched: '.str_replace($root.'/', '', $vendorFile)."\n";
}
