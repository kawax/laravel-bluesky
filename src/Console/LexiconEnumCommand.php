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
 * vendor/bin/testbench bluesky:lexicon-enum
 * ```
 *
 * update lexicon
 * ```
 * git submodule update --remote
 * ```
 */
class LexiconEnumCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:lexicon-enum';

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
        $this->embed();
        $this->facet();

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
            ->filter(fn (string $file) => Str::contains($file, $path) && Str::doesntContain($file, '/com/atproto/sync/'))
            ->mapWithKeys(fn (string $file) => [Str::of($file)->basename()->chopEnd('.json')->toString() => $file])
            ->map(function (string $file) {
                $json = File::json($file);

                $type = Arr::get($json, 'defs.main.type');
                if (in_array($type, ['query', 'procedure'], true)) {
                    return $json;
                }
            })
            ->filter(fn ($json) => is_array($json))
            //->dump()
            ->implode(function (array $json, string $name) {
                $description = Arr::get($json, 'defs.main.description');
                $id = Arr::get($json, 'id');
                $type = match (Arr::get($json, 'defs.main.type')) {
                    'query' => 'get',
                    'procedure' => 'post',
                    default => '',
                };

                return collect([
                    "    /**",
                    "     * $description",
                    "     *",
                    "     * method: $type",
                    "     */",
                    "    case $name = '$id';",
                ])->implode(PHP_EOL);
            }, PHP_EOL.PHP_EOL);

        $this->save($enum, $name);
    }

    protected function embed(): void
    {
        $enum = collect($this->files)
            ->filter(fn (string $file) => Str::contains($file, '/app/bsky/embed'))
            ->mapWithKeys(fn (string $file) => [Str::of($file)->basename()->chopEnd('.json')->studly()->toString() => $file])
            ->map(function (string $file) {
                $json = File::json($file);

                $type = Arr::get($json, 'defs.main.type');
                if ($type === 'object') {
                    return $json;
                }
            })
            ->filter(fn ($json) => is_array($json))
            //->dump()
            ->implode(function (array $json, string $name) {
                $description = Arr::get($json, 'defs.main.description', Arr::get($json, 'description'));
                $id = Arr::get($json, 'id');

                return collect([
                    "    /**",
                    "     * $description",
                    "     */",
                    "    case $name = '$id';",
                ])->implode(PHP_EOL);
            }, PHP_EOL.PHP_EOL);

        $this->save($enum, 'Embed');
    }

    protected function facet(): void
    {
        $file = collect($this->files)
            ->filter(fn (string $file) => Str::contains($file, '/app/bsky/richtext'))
            ->first();

        $json = File::json($file);

        $id = Arr::get($json, 'id');
        $facets = Arr::get($json, 'defs.main.properties.features.items.refs');

        $enum = collect($facets)
            ->mapWithKeys(fn (string $facet) => [Str::of($facet)->remove('#')->toString() => $id.$facet])
            //->dump()
            ->implode(function (string $file, string $name) use ($json) {
                $description = Arr::get($json, 'defs.'.$name.'.description');
                $name = Str::studly($name);

                return collect([
                    "    /**",
                    "     * $description",
                    "     */",
                    "    case $name = '$file';",
                ])->implode(PHP_EOL);
            }, PHP_EOL.PHP_EOL);

        $this->save($enum, 'Facet');
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
