<?php

declare(strict_types=1);

use App\TagRemoval;

it('replace tag', function (): void {
    expect(new TagRemoval)->replace(<<<'TEXT'
    Hello

    <remove>World</remove>
    TEXT)->toBe(<<<'TEXT'
    Hello

    
    TEXT);
});

it('replace tag with a lot of text', function (): void {
    expect(new TagRemoval)
        ->replace(<<<'TXT'
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras sem libero, rutrum ut congue quis, cursus
        quis lectus. Phasellus at laoreet purus. Morbi sagittis ante eget varius tristique. Etiam tempor ac
        lacus in congue.
        
        <remove>
        Ut lobortis eros a ipsum varius, eget tristique risus laoreet. Vestibulum ultricies augue ligula, vitae
        imperdiet urna tempus non. Ut ut vulputate est. Suspendisse potenti. Donec id imperdiet nisi.
        Fusce sit amet quam et justo volutpat tincidunt. Praesent mattis lobortis nunc nec ultrices.
        Nulla non lobortis enim, ac imperdiet sem.
        
        Morbi posuere non lacus quis aliquam. Sed eget ni sisuscipit, sollicitudin lorem ut, rhoncus eros.
        Vestibulum arcu velit, congue quis est vitae, iaculis euismod lorem.

        Nam et convallis nisi. Morbi sem enim, aliquet non justo et, scelerisque maximus enim
        </remove>
        
        Ut ut erat felis. Phasellus pharetra mauris at lacus porttitor consequat. Nullam dapibus risus at
        scelerisque porttitor. <remove>Sed finibus, mi at dignissim scelerisque, lacus nulla ullamcorper urna, sit
        amet blandit purus arcu a erat. Praesent sit amet facilisis magna.</remove>

        Mauris laoreet, ex ut elementum iaculis, sem massa vestibulum urna, quis efficitur purus sem a dui. Aenean
        eget nisl eu enim gravida auctor et eu dolor.
        
        <remove>Aliquam non urna ac dui blandit ultricies ut nec purus. Nunc condimentum nisi nulla, scelerisque
        venenatis lorem finibus quis.</remove> Donec ac rutrum felis, non accumsan justo. Ut rutrum tortor
        sed felis ultrices ultricies. Proin eu vehicula sapien.
        TXT)
        ->toBe(<<<'TOBE'
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras sem libero, rutrum ut congue quis, cursus
        quis lectus. Phasellus at laoreet purus. Morbi sagittis ante eget varius tristique. Etiam tempor ac
        lacus in congue.
        
        Ut ut erat felis. Phasellus pharetra mauris at lacus porttitor consequat. Nullam dapibus risus at
        Mauris laoreet, ex ut elementum iaculis, sem massa vestibulum urna, quis efficitur purus sem a dui. Aenean
        eget nisl eu enim gravida auctor et eu dolor.
        
        sed felis ultrices ultricies. Proin eu vehicula sapien.
        TOBE);
});
