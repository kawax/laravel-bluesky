<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generate php interface from lexicon json.
 *
 * ```
 * vendor/bin/testbench bluesky:lexicon-contracts
 * ```
 *
 * update lexicon
 * ```
 * git submodule update --remote
 * ```
 */
class LexiconContractsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:lexicon-contracts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate php interface from lexicon json';

    protected string $php_path;

    protected string $json_path;

    protected array $files;

    protected Collection $jsons;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->php_path = realpath(__DIR__.'/../Lexicon/Contracts/');
        $this->json_path = realpath(__DIR__.'/../../atproto/lexicons/');

        File::deleteDirectories($this->php_path);

        $this->files = File::allFiles($this->json_path);

        $this->generate();

        return 0;
    }

    protected function generate(): void
    {
        $this->jsons = collect($this->files)
            ->filter(fn (string $file) => Str::endsWith($file, '.json'))
            ->mapWithKeys(function (string $file) {
                $json = File::json($file);

                return [Arr::get($json, 'id') => $json];
            });

        $this->jsons
            ->filter(function (array $json) {
                $type = Arr::get($json, 'defs.main.type');
                if (in_array($type, ['query', 'procedure'], true)) {
                    return true;
                }
            })
            ->filter(fn ($json) => is_array($json))
            //->dump()
            ->mapToGroups(function (array $json, string $id) {
                $path = Str::of($id)->explode('.');
                $name = $path->last();
                $class = $path->take(3)->implode('/');

                $description = Arr::get($json, 'defs.main.description', $id);

                // get/query parameters
                $parameters = Arr::get($json, 'defs.main.parameters');
                $params = $this->getParameters($id, $parameters);

                // post/procedure input schema
                if (blank($params)) {
                    $input = Arr::get($json, 'defs.main.input.schema');
                    $params = $this->getParameters($id, $input);
                }

                $type = match (Arr::get($json, 'defs.main.type')) {
                    'query' => 'get',
                    'procedure' => 'post',
                    default => '',
                };

                return [$class => collect([
                    "    /**",
                    "     * $description",
                    "     *",
                    "     * method: $type",
                    "     */",
                    "    public function $name($params);",
                ])->implode(PHP_EOL)];
            })
            //->dump()
            ->each(function (Collection $methods, string $class) {
                $this->save($methods->implode(PHP_EOL.PHP_EOL), $class);
            });
    }

    protected function getParameters(string $id, ?array $parameters): string
    {
        $required = Arr::get($parameters, 'required', []);

        $properties = Arr::get($parameters, 'properties', []);

        return collect($properties)
            ->map(function ($property, $name) use ($id, $required) {
                $type = Arr::get($property, 'type');

                if ($type === 'ref') {
                    $ref = Arr::get($property, 'ref');
                    if (Str::doesntContain($ref, '.')) {
                        $ref = $id.$ref;
                    }
                    $ref_id = Str::before($ref, '#');
                    $ref_item = Str::after($ref, '#');
                    $ref_type = $ref_id.'.defs.'.$ref_item.'.type';
                    $type = $this->jsons->dot()->get($ref_type);
                }

                $type = match ($type) {
                    'integer' => 'int',
                    'boolean' => 'bool',
                    'string' => 'string',
                    'array', 'object', 'union' => 'array',
                    default => '',
                };

                $default = Arr::get($property, 'default');
                $require = in_array($name, $required, true);

                return compact('type', 'default', 'require');
            })
            ->sortByDesc(function ($property, $name) use ($required) {
                return in_array($name, $required, true);
            })
            ->implode(function ($property, $name) {
                $type = Arr::get($property, 'type');
                $require = Arr::get($property, 'require');
                $default = Arr::get($property, 'default');

                if (! $require) {
                    if (filled($default)) {
                        $name .= ' = ';
                        $name .= match ($type) {
                            'int', 'boolean' => "$default",
                            'string' => "'$default'",
                            'array' => '[]',
                            default => 'null',
                        };
                    }

                    if (filled($type)) {
                        $type = '?'.$type;
                    }

                    if (blank($default)) {
                        $name .= ' = null';
                    }
                }

                return trim($type.' $'.$name);
            }, ', ');
    }

    protected function save(string $contracts, string $class): void
    {
        $path = Str::of($class)->explode('/');
        $path = $path->map(fn ($item) => Str::studly($item));

        $file_path = $path->take(3)->implode("/");
        $name = $path->last();
        $namespace = $path->take(2)->implode("\\");

        $tmp = File::get(realpath(__DIR__.'/stubs/lexicon-interface.stub'));

        $tmp = Str::of($tmp)
            ->replace('{namespace}', $namespace)
            ->replace('{name}', $name)
            ->replace('{dummy}', $contracts)
            ->toString();

        $file_path = $this->php_path."/$file_path.php";
        File::ensureDirectoryExists(dirname($file_path));
        File::put($file_path, $tmp);
    }
}
