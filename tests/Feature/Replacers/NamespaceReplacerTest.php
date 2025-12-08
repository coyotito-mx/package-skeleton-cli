<?php

use App\Replacers\Exceptions\InvalidNamespace;
use App\Replacers\NamespaceReplacer;

it('replace namespace placeholder', function () {
    $replacer = NamespaceReplacer::make('Coyotito\\PackageSkeleton');

    expect($replacer)
        ->replace('Namespace: {{namespace}}')->toBe('Namespace: Coyotito\\PackageSkeleton');
});

it('throw and error on invalid namespace format', function (string $namespace) {
    expect(fn () => NamespaceReplacer::make($namespace))
        ->toThrow(InvalidNamespace::class);
})->with([
    'space in namespace' => ['Coyotito\\Package Skeleton'],
    'double backslash' => ['Coyotito\\\\PackageSkeleton'],
    'single forward slash' => ['Coyotito/PackageSkeleton'],
]);

it('replace namespace placeholder with modifiers', function (string $modifier, string $expected) {
    $replacer = NamespaceReplacer::make('Coyotito\\PackageSkeleton');

    expect($replacer)
        ->replace("Namespace $modifier: {{namespace|$modifier}}")->toBe("Namespace $modifier: $expected");
})->with([
    'upper' => ['upper', 'COYOTITO\\PACKAGESKELETON'],
    'lower' => ['lower', 'coyotito\\packageskeleton'],
    'title' => ['title', 'Coyotito\\PackageSkeleton'],
    'snake' => ['snake', 'coyotito\\package_skeleton'],
    'kebab' => ['kebab', 'coyotito\\package-skeleton'],
    'slug' => ['slug', 'coyotito\\package-skeleton'],
    'camel' => ['camel', 'coyotito\\packageSkeleton'],
    'escape' => ['escape', 'Coyotito\\\\PackageSkeleton'],
    'reverse' => ['reverse', 'Coyotito/PackageSkeleton'],
]);

test('cannot apply excluded modifier', function () {
    $replacer = NamespaceReplacer::make('Coyotito\\PackageSkeleton');

    expect($replacer)
        ->replace('Namespace Acronym: {{namespace|acronym}}')->toBe('Namespace Acronym: Coyotito\\PackageSkeleton');
});

it('replace namespace placeholder with multiple modifiers', function (array $modifiers, string $expected) {
    $replacer = NamespaceReplacer::make('Coyotito\\PackageSkeleton');

    $modifiersList = implode(',', $modifiers);
    $modifiersString = implode(' + ', $modifiers);

    expect($replacer)
        ->replace("Namespace $modifiersString: {{namespace|$modifiersList}}")->toBe("Namespace $modifiersString: $expected");
})->with([
    'snake + upper' => [['snake', 'upper'], 'COYOTITO\\PACKAGE_SKELETON'],
    'upper + snake' => [['upper', 'snake'], 'c_o_y_o_t_i_t_o\\p_a_c_k_a_g_e_s_k_e_l_e_t_o_n'],
    'lower + kebab' => [['lower', 'kebab'], 'coyotito\\package-skeleton'],
    'kebab + lower' => [['kebab', 'lower'], 'coyotito\\package-skeleton'],
    'kebab + upper' => [['kebab', 'upper'], 'COYOTITO\\PACKAGE-SKELETON'],
    'slug + title' => [['slug', 'title'], 'Coyotito\\Package-Skeleton'],
    'title + slug' => [['title', 'slug'], 'coyotito\\package-skeleton'],
    'camel + upper' => [['camel', 'upper'], 'COYOTITO\\PACKAGESKELETON'],
    'escape + reverse' => [['escape', 'reverse'], 'Coyotito\\\\PackageSkeleton'],
    'reverse + escape' => [['reverse', 'escape'], 'Coyotito/PackageSkeleton'],
]);
