<?php
/**
 * File containing the LegacyInitCommand class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

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
            ->setDescription('Inits Platform installation for legacy usage')
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> prepares install for use with eZ Publish legacy and LegacyBridge.

1. It creates the following folders which can be safely versioned, and symlinks them into ezpublish_legacy folder on composer install/update:
- <info>src/AppBundle/ezpublish_legacy</info> => Optionally for extensions you want to version in your project source (as opposed to install using composer)
- <info>src/legacy_files/design</info>  => Optionally for custom designs not already made part of an extension
- <info>src/legacy_files/settings/override</info> => Folder for override settings for legacy
- <info>src/legacy_files/settings/siteaccess</info> => Folder for siteaccess settings for legacy
<comment>NOTE: You'll get instructions on which commands to run to setup symlinks once you have populated the folders.</comment>

2. It configures <info>@legacy-scrips</info> in composer.json to make sure all needed scripts are executed on <info>composer install/update</info>

3. It enables <info>EzPublishLegacyBundle</info> and <info>XmlTextFieldTypeBundle</info> in <info>app/AppKernel.php</info> if needed

4. It appends legacy routes to <info>app/config/routing.yml</info> if needed

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createDirectories($input, $output);
        $this->updateComposeJson($output);
        $this->enableBundles($output);
        $this->enableRoutes($output);

        $output->writeln(<<<'EOT'

<options=bold,underscore>All steps completed!</options>

You can now check changes done and start to move over any legacy files (see above).

One done you can run the following command to setup symlinks, dump assets, (...):
- <info>composer symfony-scripts</info>
EOT
        );
    }

    protected function createDirectories(InputInterface $input, OutputInterface $output)
    {
        $srcArg = rtrim($input->getArgument('src'), '/');

        /**
         * @var \Symfony\Component\Filesystem\Filesystem
         */
        $filesystem = $this->getContainer()->get('filesystem');

        $filesystem->mkdir([
            $srcArg,
            "$srcArg/design",
            "$srcArg/AppBundle/ezpublish_legacy",
            "$srcArg/settings",
            "$srcArg/settings/override",
            "$srcArg/settings/siteaccess",
        ]);

        $output->writeln(<<<'EOT'

The following folders should have been created (or were already present):
- <info>src/AppBundle/ezpublish_legacy</info>  (for extensions)
- <info>src/legacy_files/design</info>  (optional if you have a design which is not provided by an extension)
- <info>src/legacy_files/settings/override</info>
- <info>src/legacy_files/settings/siteaccess</info>
EOT
        );
    }

    protected function updateComposeJson(OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $updateComposerJson = false;
        $composerJson = json_decode(file_get_contents('composer.json'), true);
        if ($composerJson === null) {
            $errOutput->writeln('<error>Error: Unable to parse composer.json</error>');

            return;
        }

        if (!array_key_exists('legacy-scripts', $composerJson['scripts'])) {
            $legacy_scripts = [
                'eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installAssets',
                'eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installLegacyBundlesExtensions',
                'eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateAutoloads',
                'eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::symlinkLegacyFiles',
            ];
            $composerJson['scripts'] = array_merge(['legacy-scripts' => $legacy_scripts], $composerJson['scripts']);
            $updateComposerJson = true;
        }

        if (!array_key_exists('symfony-scripts', $composerJson['scripts'])) {
            $composerJson['scripts']['symfony-scripts'] = ['@legacy-scripts'];
            $updateComposerJson = true;
            $errOutput->writeln(<<<'EOT'
<error>Warning : Did not find a <info>symfony-scripts</info> section in composer.json, check
source from eZ for how this is used and adapt your composer.json to take advantage.</error>

Example for stock 2.x setup:
<info>
"scripts": {
    "legacy-scripts": [
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installAssets",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installLegacyBundlesExtensions",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateAutoloads",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::symlinkLegacyFiles"
    ],
    "symfony-scripts": [
        "Incenteev\\ParameterHandler\\ScriptHandler::buildParameters",
        "eZ\\Bundle\\EzPublishCoreBundle\\Composer\\ScriptHandler::clearCache",
        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installAssets",
        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installRequirementsFile",
        "@legacy-scripts",
        "@php bin/console assetic:dump"
    ],
    "post-install-cmd": [
        "@symfony-scripts"
    ],
    "post-update-cmd": [
        "@symfony-scripts"
    ],
</info>
EOT
            );
        } elseif (!\in_array('@legacy-scripts', $composerJson['scripts']['symfony-scripts'])) {
            $symfonyScripts = $composerJson['scripts']['symfony-scripts'];

            $offset = array_search('@php bin/console assetic:dump', $symfonyScripts);
            if ($offset === false) {
                // Fallback to 1.x version of the dump script is present instead in case of upgrade
                $offset = array_search('eZ\Bundle\EzPublishCoreBundle\Composer\ScriptHandler::dumpAssets', $symfonyScripts);
            }

            if ($offset === false) {
                $errOutput->writeln('Warning : Unable to find "assetic:dump / ScriptHandler::dumpAsset" in [symfony-scripts], putting "@legacy_scripts" at the end of array');
                $offset = \count($symfonyScripts);
            }

            array_splice($symfonyScripts, $offset, 0, ['@legacy-scripts']);
            $composerJson['scripts']['symfony-scripts'] = $symfonyScripts;
            $updateComposerJson = true;
        }

        if ($updateComposerJson) {
            file_put_contents('composer.json', json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL);
        }
    }

    protected function enableBundles(OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        if (!$text = file_get_contents('app/AppKernel.php')) {
            $errOutput->writeln('<error>Error: Unable to load app/AppKernel.php</error>');

            return;
        }

        $changed = false;
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $appOffset = false;
        foreach ($lines as $i => $line) {
            if (stripos($line, 'new AppBundle\AppBundle()') !== false) {
                $appOffset = $i;
                // Make sure element has comma (",") at end, however don't mark as $changed
                if (stripos($line, 'new AppBundle\AppBundle(),') === false) {
                    $lines[$i] = $line . ',';
                }

                break;
            }
        }

        if (!$appOffset) {
            $errOutput->writeln('<error>Error: Could not find "new AppBundle\AppBundle()" in app/AppKernel.php</error>');

            return;
        }

        // Enable Bundles (if needed)
        if (stripos($text, 'EzSystemsEzPlatformXmlTextFieldTypeBundle') === false) {
            // Add XmlText bundle bundle before AppBundle (on purpose without indenting)
            array_splice(
                $lines,
                trim($lines[$appOffset - 1]) === '// Application' ? $appOffset - 1 : $appOffset,
                0,
                'new EzSystems\EzPlatformXmlTextFieldTypeBundle\EzSystemsEzPlatformXmlTextFieldTypeBundle(),'
                );
            $changed = true;
        }

        if ($changed) {
            file_put_contents('app/AppKernel.php', implode(PHP_EOL, $lines));
        }
    }

    protected function enableRoutes(OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        if (!$text = file_get_contents('app/config/routing.yml')) {
            $errOutput->writeln('<error>Error: Unable to load app/config/routing.yml</error>');

            return;
        }

        $changed = false;
        if (stripos($text, 'resource: @EzPublishLegacyBundle/Resources/config/routing.yml') === false) {
            // Add routes to the end of routes file
            $text .= <<<'EOT'

# NOTE: ALWAYS keep this at the end of your routing rules so native symfony routes have precedence
#       To avoid legacy REST pattern overriding possible eZ Platform REST routes and so on.
_ezpublishLegacyRoutes:
    resource: '@EzPublishLegacyBundle/Resources/config/routing.yml'
EOT;
            $changed = true;
        }

        if ($changed) {
            file_put_contents('app/config/routing.yml', $text);
        }
    }
}
