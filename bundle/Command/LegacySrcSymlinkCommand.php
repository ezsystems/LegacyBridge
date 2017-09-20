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

<comment>NOTE: Look towards 'ezpublish:legacybundles:install_extensions' command for how you handle extensions.</comment>
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
            if ($create) {
                $this->createSrcFolderStructure($filesystem, $srcArg);
                $output->writeln("<comment>Empty legacy src folder was created in '$srcArg'.</comment>");
                // No break, continue so setup symlink settings/override
            } else {
                throw new \InvalidArgumentException(sprintf('The src directory "%s" does not exist.', $srcArg));
            }
        }

        $symlinkFolderStr = implode(' ,', $this->linkSrcFolders($filesystem, $srcArg, $force));

        if ($symlinkFolderStr) {
            $output->writeln("The following folders where symlinked: '$symlinkFolderStr'.");
        } else {
            $output->writeln('No folders where symlinked, use force option if they need to be re-created.');
        }

        $output->writeln(<<<EOT

If you create or move additional design or siteaccess folders to '$srcArg' from previous install, then
re-run <info>ezpublish:legacy:symlink</info> to setup symlinks to eZ Publish legacy folder for them also.

EOT
);
    }

    /**
     * Create legacy src folder structure.
     *
     * src/legacy_files:
     * - design
     * - settings:
     *   - override
     *   - siteaccess
     *
     * @param Filesystem $filesystem
     * @param string $srcArg
     */
    protected function createSrcFolderStructure(Filesystem $filesystem, $srcArg)
    {
        $filesystem->mkdir([
            $srcArg,
            "$srcArg/design",
            "$srcArg/settings",
            "$srcArg/settings/override",
            "$srcArg/settings/siteaccess",
        ]);
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
