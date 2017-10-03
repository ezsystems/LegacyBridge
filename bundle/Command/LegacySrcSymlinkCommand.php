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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class LegacySrcSymlinkCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ezpublish:legacy:symlink')
            ->setDefinition(
                array(
                    new InputArgument('src', InputArgument::OPTIONAL, 'The src directory for legacy files', 'src/legacy_files'),
                )
            )
            ->addOption('create', 'c', InputOption::VALUE_NONE, 'Create "src" directory structure if it does not exist')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Symlink folders even if target already exist')
            ->setDescription('Installs legacy project settings and design files from "src" to corresponding folders in ezpublish_legacy/')
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> setups and symlinks <info>src/legacy_files</info> stored in your root project for
any design/settings project files, and symlinks these into <info>ezpublish_legacy/</info> which is installed by composer.

The benefit of this is:
1. Being able to version your design/config files in git without versioning legacy itself
2. A predefined convention for where to place these files when migrating from older versions
3. Placing these files directly in ezpublish_legacy folder will lead to them getting removed in some cases when composer
   needs to completely replace ezpublish-legacy package for different reasons.

<comment>NOTE: Look towards 'ezpublish:legacybundles:install_extensions' command for how you handle legacy extensions.</comment>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $srcArg = rtrim($input->getArgument('src'), '/');
        $create = (bool)$input->getOption('create');
        $force = (bool)$input->getOption('force');

        /**
         * @var \Symfony\Component\Filesystem\Filesystem
         */
        $filesystem = $this->getContainer()->get('filesystem');

        if (!$filesystem->exists($srcArg)) {
            if (!$create) {
                $output->writeln(<<<EOT
Aborting: The src directory "$srcArg" does not exist.

You can create the directory by running <info>ezpublish:legacy:symlink -c</info>, OR by creating the folders you need
manually among the once supported by this command:
- $srcArg/design
- $srcArg/settings/override
- $srcArg/settings/siteaccess

TIP: It is recommended that you likewise setup symlink for var/[site/]storage to a folder outside ezpublish_legacy/.

EOT
, OutputInterface::VERBOSITY_QUIET
);

                return;
            }

            $filesystem->mkdir([
                $srcArg,
                "$srcArg/design",
                "$srcArg/settings",
                "$srcArg/settings/override",
                "$srcArg/settings/siteaccess",
            ]);

            $output->writeln("<comment>Empty legacy src folder was created in '$srcArg'.</comment>");
        }

        $symlinkFolderStr = implode(' ,', $this->linkSrcFolders($filesystem, $srcArg, $force));

        if ($symlinkFolderStr) {
            $output->writeln("The following folders where symlinked: '$symlinkFolderStr'.");
        } else {
            $output->writeln('No folders where symlinked, use force option if they need to be re-created.');
        }

        $output->writeln(<<<EOT

NOTE: If you create or move additional design or siteaccess folders to '$srcArg' from previous install, then
re-run <info>ezpublish:legacy:symlink</info> to setup symlinks to eZ Publish legacy folder for them also.

EOT
);
    }

    /**
     * Setup symlinks for legacy settings/design files within eZ Publish legacy folder.
     *
     * @param Filesystem $filesystem
     * @param string $srcArg
     * @param bool $force
     *
     * @return array
     */
    protected function linkSrcFolders(Filesystem $filesystem, $srcArg, $force)
    {
        $symlinks = [];
        $legacyRootDir = rtrim($this->getContainer()->getParameter('ezpublish_legacy.root_dir'), '/');

        // first handle override folder if it exists
        if (
            $filesystem->exists("$srcArg/settings/override") &&
            ($force || !$filesystem->exists("$legacyRootDir/settings/override"))
        ) {
            $filesystem->symlink(
                $filesystem->makePathRelative(
                    realpath("$srcArg/settings/override"),
                    realpath("$legacyRootDir/settings")
                ),
                "$legacyRootDir/settings/override"
            );
            $symlinks[] = "$legacyRootDir/settings/override";
        }

        // secondly handle sub folders in design and settings/siteaccess
        $directories = ['design', 'settings/siteaccess'];
        foreach ($directories as $directory) {
            foreach (Finder::create()->directories()->in(["$srcArg/$directory"]) as $folder) {
                $folderName = $folder->getFilename();
                if (!$force && $filesystem->exists("$legacyRootDir/$directory/$folderName")) {
                    continue;
                }

                $filesystem->symlink(
                    $filesystem->makePathRelative(
                        realpath("$srcArg/$directory/$folderName"),
                        realpath("$legacyRootDir/$directory")
                    ),
                    "$legacyRootDir/$directory/$folderName"
                );
                $symlinks[] = "$legacyRootDir/$directory/$folderName";
            }
        }

        return $symlinks;
    }
}
