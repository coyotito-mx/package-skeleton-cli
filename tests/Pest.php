<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use App\Commands\Command;
use App\Commands\Concerns\InteractsWithReplacers;
use App\Commands\Concerns\WithTraitsBootstrap;
use Illuminate\Support\Facades\Artisan;
use Tests\Fixtures\Concerns\InteractsWithEntryMethod;

use function Illuminate\Filesystem\join_paths;

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @return Command&InteractsWithReplacers&WithTraitsBootstrap&InteractsWithEntryMethod
 */
function testingReplacersInCommand(string $subject, string ...$uses): Command
{
    $setupNamespace = static function (string $class): string {
        // Just in case
        $segments = explode('/', $class);

        if (count($segments) === 1) {
            $class = array_pop($segments);
            $segments = [];
        } else {
            $class = array_pop($segments);
        }

        $namespace = implode('\\', $segments);

        return '\\'.($namespace ? $namespace.'\\' : '').$class;
    };

    $traitsCode = implode(
        ', ',
        array_map(
            fn (string $trait) => $setupNamespace($trait),
            [
                WithTraitsBootstrap::class,
                InteractsWithReplacers::class,
                InteractsWithEntryMethod::class,
                ...$uses,
            ],
        ),
    );

    $commandClass = $setupNamespace(Command::class);

    $code = <<<PHP
    return fn () => new class extends $commandClass {
        use $traitsCode;

        protected \$signature = 'demo';

        protected \$description = 'Evaluated testing command';

        public function handle(): int
        {
            return \$this->entry();
        }

        public function __handle(): int
        {
            \$output = (new \Illuminate\Pipeline\Pipeline)
                ->send('$subject')
                ->through(\$this->getPackageReplacers())
                ->thenReturn();

            \$this->line(\$output);

            return  (int) !\$output;
        }

        protected function getPackagePath(?string \$path = null): string
        {
            return sandbox_path(\$path);
        }
    };
    PHP;

    $class = eval($code);

    Artisan::registerCommand($class = $class());

    return $class;
}

function test_path(?string $path = null): string
{
    return join_paths(__DIR__, $path);
}

function sandbox_path(?string $path = null): string
{
    return test_path(
        join_paths('sandbox', $path ?? '')
    );
}
