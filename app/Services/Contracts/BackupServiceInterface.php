<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface BackupServiceInterface
{
    /**
     * Run a database backup.
     *
     * V49-HIGH-02 FIX: Added optional $prefix parameter to allow custom filename prefixes.
     *
     * @param  bool  $verify  Whether to verify the backup was created
     * @param  string  $prefix  Optional filename prefix (default: 'backup')
     * @return array{path: string, size: int}
     */
    public function run(bool $verify = true, string $prefix = 'backup'): array;

    /** @return array<int, array{path:string,size:int,modified:int}> */
    public function list(): array;

    public function delete(string $path): bool;

    public function verify(array $result): bool;
}
