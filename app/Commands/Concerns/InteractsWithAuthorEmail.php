<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
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

        $email = Str::lower($this->option('email') ?? $this->getAuthorEmail());

        if (! $this->validateEmail($email)) {
            throw new InvalidArgumentException("Invalid email provided [$email]");
        }

        return $this->authorEmailResolved = $email;
    }

    protected function getAuthorEmail(): string
    {
        try {
            $email = app('git')->getConfig('user.email');

            if (filled($email)) {
                return $email;
            }
        } catch (CommandNotFoundException) {
            // let them pass
        }

        return text(
            label: "Author's Email",
            required: true,
            validate: fn (string $value) => ! $this->validateEmail($value) ? 'The value is not a valid e-mail' : null
        );
    }

    protected function validateEmail(string $value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
