<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Replacers\AuthorReplacer;
use App\Replacers\EmailReplacer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

trait HasAuthorInformation
{
    protected function bootAuthorInformation(): void
    {
        $this->addCommandArguments([
            ['author', InputArgument::REQUIRED, 'The package author'],
            ['email', InputArgument::REQUIRED, 'The package author email'],
        ]);

        $this
            ->addReplacer(AuthorReplacer::class, fn () => $this->getAuthor())
            ->addReplacer(EmailReplacer::class, fn () => $this->getEmail());
    }

    /**
     * Get the author name formatted in Title Case.
     */
    private function getAuthor(): string
    {
        return Str::title($this->argument('author'));
    }

    /**
     * Get the author email in lowercase.
     */
    private function getEmail(): string
    {
        return Str::lower($this->argument('email'));
    }

    /**
     * Fetch user's git global configuration.
     *
     * @return array<string, string>|null
     */
    protected function getGitUserInformation(): ?array
    {
        $result = $this->makeProcess(['git', 'config'], '--list')->run();

        if ($result->failed() || ! $result->output()) {
            return null;
        }

        $options = collect(explode(PHP_EOL, $result->output()))
            ->mapWithKeys(function ($line) {
                $parts = explode('=', $line, 2);

                if (count($parts) === 2) {
                    return [trim($parts[0]) => trim($parts[1])];
                }

                return [];
            })
            ->filter(filled(...));

        if ($options->isEmpty() || ! $options->has(['user.name', 'user.email'])) {
            return null;
        }

        return [
            'author' => $options->get('user.name'),
            'email' => $options->get('user.email'),
        ];
    }
}
