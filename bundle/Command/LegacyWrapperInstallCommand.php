<?php
/**
 * File containing the LegacyWrapperInstallCommand class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LegacyWrapperInstallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ezpublish:legacy:assets_install')
            ->setDefinition(
                [
                    new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web'),
                ]
            )
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force overwriting of existing directory (will be removed)')
            ->setDescription('Installs assets from eZ Publish legacy installation and wrapper scripts for front controllers (like index_cluster.php).')
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> installs <info>assets</info> from eZ Publish legacy installation
and wrapper scripts for <info>front controllers</info> (like <info>index_cluster.php</info>).
<info>Assets folders:</info> Symlinks will be created from your eZ Publish legacy directory (will fall back to hard copy if symbolic link fails)
<info>Front controllers:</info> Wrapper scripts will be generated.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetArg = rtrim($input->getArgument('target'), '/');
        if (!is_dir($targetArg)) {
            throw new \InvalidArgumentException(sprintf('The target directory "%s" does not exist.', $input->getArgument('target')));
        }

        /**
         * @var \Symfony\Component\Filesystem\Filesystem
         */
        $filesystem = $this->getContainer()->get('filesystem');
        $legacyRootDir = rtrim($this->getContainer()->getParameter('ezpublish_legacy.root_dir'), '/');

        $output->writeln(sprintf("Installing eZ Publish legacy assets from $legacyRootDir using the <comment>%s</comment> option", $input->getOption('symlink') ? 'symlink' : 'hard copy'));
        $symlink = $input->getOption('symlink');
        $relative = $input->getOption('relative');
        $force = (bool)$input->getOption('force');

        foreach (['design', 'extension', 'share', 'var'] as $folder) {
            $targetDir = "$targetArg/$folder";
            $originDir = "$legacyRootDir/$folder";

            // Check if directory exists (not link) and is not empty to avoid removing things that should be backed up
            if (!$force && !is_link($targetDir) && is_dir($targetDir) && !$this->isDirectoryEmpty($targetDir)) {
                $output->writeln(<<<EOT
<warning>Skipping: The folder "$targetDir" already exists and seems to contain content!</warning>

Make sure to backup this content and move it into corresponding legacy folder which will be setup to symlink / copy
to this folder before you remove it, then re-run this command.

If this folder and the other "$targetArg" directories can be safely overwritten, run this command with the <info>--force</info> option.

EOT
, OutputInterface::VERBOSITY_QUIET
);
                continue;
            }

            $filesystem->remove($targetDir);
            if ($symlink) {
                if ($relative) {
                    $relativeOriginDir = $filesystem->makePathRelative($originDir, realpath($targetArg));

                    try {
                        $filesystem->symlink($relativeOriginDir, $targetDir);
                    } catch (IOException $e) {
                        $relative = false;
                        $output->writeln('It looks like your system doesn\'t support relative symbolic links, so will fallback to absolute symbolic links instead!');
                    }
                }

                if (!$relative) {
                    try {
                        $filesystem->symlink($originDir, $targetDir);
                    } catch (IOException $e) {
                        $symlink = false;
                        $output->writeln('It looks like your system doesn\'t support symbolic links, so will fallback to hard copy instead!');
                    }
                }
            }

            if (!$symlink) {
                $filesystem->mkdir($targetDir, 0777);
                // We use a custom iterator to ignore VCS files
                $currentDir = getcwd();
                chdir(realpath($targetArg));
                $filesystem->mirror($originDir, $folder, Finder::create()->in($originDir));
                chdir($currentDir);
            }
        }

        if ($relative) {
            $legacyRootDir = $filesystem->makePathRelative(realpath($legacyRootDir), realpath($targetArg));
            $rootDirCode = "__DIR__ . DIRECTORY_SEPARATOR . '{$legacyRootDir}'";
        } else {
            $rootDirCode = "'{$legacyRootDir}'";
        }

        $output->writeln("Installing wrappers for eZ Publish legacy front controllers (rest & cluster) with path $legacyRootDir");
        foreach (['index_rest.php', 'index_cluster.php'] as $frontController) {
            $newFrontController = "$targetArg/$frontController";
            $filesystem->remove($newFrontController);

            $code = <<<EOT
<?php
/**
 * File containing the wrapper around the legacy $frontController file
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

 \$legacyRoot = {$rootDirCode};
 chdir( \$legacyRoot );
 require '{$frontController}';

EOT;
            $filesystem->dumpFile($newFrontController, $code);
        }
    }

    private function isDirectoryEmpty($path)
    {
        $directory = new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::SKIP_DOTS
        );
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $item) {
            return false;
        }

        return true;
    }
}
