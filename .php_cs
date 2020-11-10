<?php

return EzSystems\EzPlatformCodeStyle\PhpCsFixer\EzPlatformInternalConfigFactory::build()
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude([
                'vendor',
                'ezpublish_legacy',
                'bundle/Resources/init_ini',
            ])
            ->files()->name('*.php')
    );
