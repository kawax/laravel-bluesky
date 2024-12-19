<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Revolution\AtProto\Lexicon\Attributes\Post;

/**
 * Generate client trait.
 *
 * ```
 * vendor/bin/testbench bluesky:lexicon-client
 * ```
 *
 * @codeCoverageIgnore
 */
class LexiconClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:lexicon-client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate client trait from lexicon interface';

    protected string $client_path;

    protected string $interface_path;

    protected array $files;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->client_path = (string) realpath(__DIR__.'/../Client/Concerns/');
        $this->interface_path = (string) realpath(__DIR__.'/../../vendor/revolution/atproto-lexicon-contracts/src/Contracts/');

        File::cleanDirectory($this->client_path);

        $this->files = File::allFiles($this->interface_path);

        $this->generate();

        return 0;
    }

    protected function generate(): void
    {
        collect($this->files)
            ->mapWithKeys(function (string $file) {
                // App/Bsky/Actor
                $class = Str::of($file)
                    ->after($this->interface_path)
                    ->chopEnd('.php');

                // AppBskyActor
                $trait = $class->replace('/', '')
                    ->toString();

                // Revolution\AtProto\Lexicon\Contracts\App\Bsky\Actor
                /** @var class-string $contract */
                $contract = $class->replace('/', '\\')
                    ->prepend('Revolution\AtProto\Lexicon\Contracts')
                    ->toString();

                $ref = new ReflectionClass($contract);
                if ($ref->isInterface()) {
                    return [
                        $trait => [
                            'contract' => $contract,
                            'methods' => $ref->getMethods(),
                        ],
                    ];
                }
            })
            ->map(function (array $file) {
                $methods = collect($file['methods'])
                    ->map(function (ReflectionMethod $method) {
                        $func = $method->getName();
                        $params = $this->getParameters($method->getParameters());

                        $attrs = $method->getAttributes(Post::class);
                        $http = filled($attrs) ? 'POST' : 'GET';

                        return [
                            'func' => $func,
                            'http' => $http,
                            'params' => $params,
                        ];
                    })->toArray();

                return [
                    'contract' => $file['contract'],
                    'methods' => $methods,
                ];
            })
            ->each(function (array $file, string $name) {
                $this->save($file, $name);
            });
    }

    protected function getParameters(array $parameters): string
    {
        return collect($parameters)
            ->map(function (ReflectionParameter $parameter) {
                $name = $parameter->getName();
                $type = $parameter->getType()->__toString();

                $attrs = collect($parameter->getAttributes())
                    ->filter(fn (ReflectionAttribute $attr) => $attr->getName() === \SensitiveParameter::class)
                    ->map(function (ReflectionAttribute $attr) {
                        return '#[\\'.$attr->getName().']';
                    })
                    ->implode(' ');

                if ($parameter->isOptional()) {
                    $default = $parameter->getDefaultValue();

                    $default = match (true) {
                        is_int($default) => '= '.$default,
                        is_string($default) => "= '$default'",
                        default => '= null',
                    };
                } else {
                    $default = null;
                }

                return Str::squish("$attrs $type \$$name $default");
            })
            ->implode(', ');
    }

    /**
     * @param  array{contract: class-string, methods: array}  $file
     */
    protected function save(array $file, string $name): void
    {
        $contract = $file['contract'];

        $methods = collect($file['methods'])
            ->map(function (array $method) use ($contract) {
                $contract_name = Str::afterLast($contract, '\\');

                return collect([
                    sprintf('    public function %s(%s): Response', $method['func'], $method['params']),
                    '    {',
                    '        return $this->call(',
                    "            api: $contract_name::".$method['func'].',',
                    '            method: self::'.$method['http'].',',
                    '            params: compact($this->params(__METHOD__)),',
                    '        );',
                    '    }',
                ])->implode(PHP_EOL);
            })->implode(PHP_EOL.PHP_EOL);

        $tmp = File::get((string) realpath(__DIR__.'/stubs/lexicon-trait.stub'));

        $tmp = Str::of($tmp)
            ->replace('{contract}', $contract)
            ->replace('{name}', $name)
            ->replace('{method}', $methods)
            ->toString();

        $file_path = $this->client_path."/$name.php";
        File::ensureDirectoryExists(dirname($file_path));
        File::put($file_path, $tmp);
    }
}
