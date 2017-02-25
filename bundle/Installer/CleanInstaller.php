<?php

namespace eZ\Bundle\EzPublishLegacyBundle\Installer;

class CleanInstaller extends DbBasedInstaller implements Installer
{
    public function createConfiguration()
    {
    }

    public function importSchema()
    {
        $this->runQueriesFromFile('ezpublish_legacy/kernel/sql/mysql/kernel_schema.sql');
        $this->runQueriesFromFile('ezpublish_legacy/kernel/sql/mysql/cluster_dfs_schema.sql');
    }

    public function importData()
    {
        $this->runQueriesFromFile(
            'ezpublish_legacy/kernel/sql/common/cleandata.sql'
        );
    }

    public function importBinaries()
    {
    }
}
