<?php
/**
 * File containing the LegacySrcSymlinkCommand class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class LegacyInitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ezpublish:legacy:init')
            ->setDefinition(
                array(
                    new InputArgument('src', InputArgument::OPTIONAL, 'The src directory for legacy files', 'src/legacy_files'),
                )
            )
            ->setDescription('Inits folders you can use when migrating from eZ Publish install to eZ Platform with Legacy Bridge')
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> creates the following folders for migration usage:
- src/AppBundle/ezpublish_legacy => Optionaly for extensions you want to version in your project source (as opposed to install using composer)
- src/legacy_files/design  => Optionally for custom designs not already made part of an extension
- src/legacy_files/settings/override => Folder for override settings for legacy
- src/legacy_files/settings/siteaccess => Folder for siteaccess settings for legacy


<info>src/legacy_files</info> stored in your root project for
any design/extension/settings project files, and symlinks these into <info>ezpublish_legacy/</info> which is installed by composer.

The benefit of this is:
1. Being able to version your design/extension/settings files in git without versioning legacy itself
2. A predefined convention for where to place these files when migrating from older versions
3. Placing these files directly in ezpublish_legacy folder will lead to them getting removed in some cases when composer
   needs to completely replace ezpublish-legacy package for different reasons.

<comment>NOTE: Once this is ran you'll get instructions on which commands to run to setup symlinks once you have populated the folders.</comment>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $srcArg = rtrim($input->getArgument('src'), '/');

        /**
         * @var \Symfony\Component\Filesystem\Filesystem
         */
        $filesystem = $this->getContainer()->get('filesystem');

        $filesystem->mkdir([
            $srcArg,
            "$srcArg/design",
            //"$srcArg/AppBundle",
            "$srcArg/AppBundle/ezpublish_legacy",
            "$srcArg/settings",
            "$srcArg/settings/override",
            "$srcArg/settings/siteaccess",
        ]);

        $output->writeln(<<<EOT

The following folders should have been created (or where already present):
- src/AppBundle/ezpublish_legacy  _(for extensions)_
- src/legacy_files/design
- src/legacy_files/settings/override
- src/legacy_files/settings/siteaccess

Move over files and directories from older installation and afterwards run the folling commands to setup symlinks into
legacy install:
- <info>ezpublish:legacy:symlink</info>
- <info>ezpublish:legacybundles:install_extensions</info>
- <info>ezpublish:legacy:assets_install</info>


EOT
);
    }
}
