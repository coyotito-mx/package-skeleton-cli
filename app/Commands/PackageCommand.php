<?php

namespace App\Commands;

use Illuminate\Console\Concerns\PromptsForMissingInput as PromptsForMissingInputConcern;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use LaravelZero\Framework\Commands\Command;

class PackageCommand extends Command implements PromptsForMissingInput
{
    use PromptsForMissingInputConcern;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init
                            { vendor : The name of the package vendor }
                            { package : The name of the package }
                            { namespace : The base namespace for the package }
                            { --description= : The package description }
                            { --author= : The package author }
                            { --license=MIT : The package license }
                            { --proceed : Accept the configuration and proceed without confirmation }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize a new package structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        //
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [];
    }
}
