<?php

use App\Replacers\ClassReplacer;

it('replaces class name placeholder', function (): void {
    $replacer = ClassReplacer::make('User Controller');

    expect($replacer)
        ->replace('The class name is {{class}}')->toBe('The class name is UserController');
});

it('replaces class name with filename modifier', function (): void {
    $replacer = ClassReplacer::make('UserController');

    expect($replacer)
        ->replace('The file name is {{class|filename}}.php')->toBe('The file name is user-controller.php');
});
