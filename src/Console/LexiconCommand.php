<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generate php enum from lexicon json.
 *
 * ```
 * vendor/bin/testbench bluesky:lexicon
 * ```
 *
 * update lexicon
 * ```
 * git submodule update --remote
 * ```
 */
class LexiconCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:lexicon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate php enum from lexicon json';

    protected string $php_path;

    protected string $json_path;

    protected array $files;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->php_path = realpath(__DIR__.'/../Lexicon/');
        $this->json_path = realpath(__DIR__.'/../../atproto/lexicons/');

        $this->files = File::allFiles($this->json_path);

        $this->bsky();
        $this->atproto();
        $this->embed(path: '/app/bsky/embed', name: 'Embed');
        $this->facet(path: '/app/bsky/richtext', name: 'Facet');

        return 0;
    }

    protected function bsky(): void
    {
        $this->action(path: '/app/bsky', name: 'Bsky');
    }

    protected function atproto(): void
    {
        $this->action(path: '/com/atproto', name: 'AtProto');
    }

    protected function action(string $path, string $name): void
    {
        $enum = collect($this->files)
            ->filter(function (string $file) use ($path) {
                return Str::contains($file, $path);
            })
            ->mapWithKeys(function (string $file) {
                return [Str::of($file)->basename()->chopEnd('.json')->toString() => $file];
            })
            ->map(function (string $file) {
                $json = json_decode(File::get($file), true);

                $type = Arr::get($json, 'defs.main.type');
                if (in_array($type, ['query', 'procedure'], true)) {
                    return Arr::get($json, 'id');
                }
            })
            ->reject(fn ($file) => is_null($file))
            ->dump()
            ->implode(function (string $file, string $name) {
                return "    case $name = '$file';";
            }, PHP_EOL);

        $this->save($enum, $name);
    }

    protected function embed(string $path, string $name): void
    {
        $enum = collect($this->files)
            ->filter(function (string $file) use ($path) {
                return Str::contains($file, $path);
            })
            ->mapWithKeys(function (string $file) {
                return [Str::of($file)->basename()->chopEnd('.json')->studly()->toString() => $file];
            })
            ->map(function (string $file) {
                $json = json_decode(File::get($file), true);

                $type = Arr::get($json, 'defs.main.type');
                if ($type === 'object') {
                    return Arr::get($json, 'id');
                }
            })
            ->reject(fn ($file) => is_null($file))
            ->dump()
            ->implode(function (string $file, string $name) {
                return "    case $name = '$file';";
            }, PHP_EOL);

        $this->save($enum, $name);
    }

    protected function facet(string $path, string $name): void
    {
        $file = collect($this->files)
            ->filter(function (string $file) use ($path) {
                return Str::contains($file, $path);
            })->first();

        $json = json_decode(File::get($file), true);

        $id = Arr::get($json, 'id');
        $facets = Arr::get($json, 'defs.main.properties.features.items.refs');

        $enum = collect($facets)
            ->mapWithKeys(function (string $facet) use ($id) {
                return [Str::of($facet)->remove('#')->studly()->toString() => $id.$facet];
            })
            ->dump()
            ->implode(function (string $file, string $name) {
                return "    case $name = '$file';";
            }, PHP_EOL);

        $this->save($enum, $name);
    }

    protected function save(string $enum, string $name): void
    {
        $tmp = File::get(realpath(__DIR__.'/stubs/lexicon-enum.stub'));

        $tmp = Str::of($tmp)
            ->replace('{name}', $name)
            ->replace('{dummy}', $enum)
            ->toString();

        File::put($this->php_path."/$name.php", $tmp);
    }
}
