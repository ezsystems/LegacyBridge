<?php

namespace eZ\Bundle\EzPublishLegacyBundle\Installer;

use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;

class DbBasedInstaller
{
    /** @var Connection */
    protected $db;

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    protected $output;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Copy and override configuration file.
     *
     * @param string $source
     * @param string $target
     */
    protected function copyConfigurationFile($source, $target)
    {
        $fs = new Filesystem();
        $fs->copy($source, $target, true);

        if (!$this->output->isQuiet()) {
            $this->output->writeln("Copied $source to $target");
        }
    }

    protected function runQueriesFromFile($file)
    {
        $queries = array_filter(preg_split('(;\\s*$)m', file_get_contents($file)));

        if (!$this->output->isQuiet()) {
            $this->output->writeln(
                sprintf(
                    'Executing %d queries from %s on database %s',
                    count($queries),
                    $file,
                    $this->db->getDatabase()
                )
            );
        }

        foreach ($queries as $query) {
            $this->db->exec($query);
        }
    }
}
