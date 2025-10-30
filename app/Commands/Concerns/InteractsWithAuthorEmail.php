<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\text;

trait InteractsWithAuthorEmail
{
    protected ?string $authorEmailResolved = null;

    public function bootInteractsWithAuthorEmail(): void
    {
        $this->addReplacers([
            Replacer\AuthorEmailReplacer::class => fn (): string => $this->getPackageAuthorEmail(),
        ]);

        $this->addOption('email', mode: InputOption::VALUE_REQUIRED, description: "The author's email");
    }

    public function getPackageAuthorEmail(): string
    {
        if (filled($this->authorEmailResolved)) {
            return $this->authorEmailResolved;
        }

        return $this->authorEmailResolved = Str::lower($this->option('email') ?? $this->getAuthorEmail());
    }

    protected function getAuthorEmail(): string
    {
        $email = app('git')->getConfig('user.email');

        if (filled($email)) {
            return $email;
        }

        return text("Author's Email", required: true, validate: function (string $value) {
            if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'The email is not a valid e-mail';
            }

            return null;
        });
    }
}
