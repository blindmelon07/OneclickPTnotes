<?php

use Illuminate\Support\Facades\Artisan;

test('backups are stored on a disk separate from the PHI storage disk', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['backups']);
    expect(config('filesystems.disks.backups.root'))
        ->not->toBe(config('filesystems.disks.local.root'));
});

test('backups only include the private storage disk, not the whole application source', function () {
    expect(config('backup.backup.source.files.include'))->toBe([
        storage_path('app/private'),
    ]);
});

test('the backup:list command runs successfully', function () {
    $exitCode = Artisan::call('backup:list');

    expect($exitCode)->toBe(0);
});
