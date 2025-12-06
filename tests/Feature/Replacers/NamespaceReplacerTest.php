<?php

use App\Replacers\Exceptions\InvalidNamespace;
use App\Replacers\NamespaceReplacer;

it('replace namespace placeholder', function () {
    $replacer = NamespaceReplacer::make('Coyotito\\PackageSkeleton');

    expect($replacer)
        ->replace('Namespace: {{namespace}}')->toBe('Namespace: Coyotito\\PackageSkeleton');
});

it('throw and error on invalid namespace format', function (string $namespace) {
    expect(fn() => NamespaceReplacer::make($namespace))
        ->toThrow(InvalidNamespace::class);
})->with([
    'double slashes' => ['Coyotito//PackageSkeleton'],
    'space in namespace' => ['Coyotito\\Package Skeleton'],
    'single forward slash' => ['Coyotito/PackageSkeleton'],
]);

it('replace namespace placeholder with modifiers', function () {
    $replacer = NamespaceReplacer::make('Coyotito\\PackageSkeleton');

    expect($replacer)
        ->replace('Namespace Upper: {{namespace|upper}}')->toBe('Namespace Upper: COYOTITO\\PACKAGESKELETON')
        ->replace('Namespace Lower: {{namespace|lower}}')->toBe('Namespace Lower: coyotito\\packageskeleton')
        ->replace('Namespace Title: {{namespace|title}}')->toBe('Namespace Title: Coyotito\\PackageSkeleton')
        ->replace('Namespace Snake: {{namespace|snake}}')->toBe('Namespace Snake: coyotito\\package_skeleton')
        ->replace('Namespace Kebab: {{namespace|kebab}}')->toBe('Namespace Kebab: coyotito\\package-skeleton')
        ->replace('Namespace Slug: {{namespace|slug}}')->toBe('Namespace Slug: coyotito\\package-skeleton')
        ->replace('Namespace Camel: {{namespace|camel}}')->toBe('Namespace Camel: coyotito\\packageSkeleton');
});

it('replace namespace placeholder with multiple modifiers', function () {
    $replacer = NamespaceReplacer::make('Coyotito\\PackageSkeleton');

    expect($replacer)
        ->replace('Namespace Snake + Upper: {{namespace|snake,upper}}')->toBe('Namespace Snake + Upper: COYOTITO\\PACKAGE_SKELETON')
        ->replace('Namespace Upper + Snake: {{namespace|upper,snake}}')->toBe('Namespace Upper + Snake: c_o_y_o_t_i_t_o\\p_a_c_k_a_g_e_s_k_e_l_e_t_o_n')
        ->replace('Namespace Lower + Kebab: {{namespace|lower,kebab}}')->toBe('Namespace Lower + Kebab: coyotito\\package-skeleton')
        ->replace('Namespace Kebab + Lower: {{namespace|kebab,lower}}')->toBe('Namespace Kebab + Lower: coyotito\\package-skeleton')
        ->replace('Namespace Slug + Title: {{namespace|slug,title}}')->toBe('Namespace Slug + Title: Coyotito\\Package-Skeleton')
        ->replace('Namespace Title + Slug: {{namespace|title,slug}}')->toBe('Namespace Title + Slug: coyotito\\package-skeleton')
        ->replace('Namespace Camel + Upper: {{namespace|camel,upper}}')->toBe('Namespace Camel + Upper: COYOTITO\\PACKAGESKELETON')
        ->replace('Namespace Upper + Camel: {{namespace|upper,camel}}')->toBe('Namespace Upper + Camel: cOYOTITO\\pACKAGESKELETON');
});
