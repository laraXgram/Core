<?php

namespace LaraGram\Database\Schema\Grammars;

use LaraGram\Database\Connection;
use LaraGram\Database\Schema\Blueprint;
use LaraGram\Support\Fluent;

class MariaDbGrammar extends MySqlGrammar
{
    /**
     * Compile a rename column command.
     *
     * @param  \LaraGram\Database\Schema\Blueprint  $blueprint
     * @param  \LaraGram\Support\Fluent  $command
     * @param  \LaraGram\Database\Connection  $connection
     * @return array|string
     */
    public function compileRenameColumn(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        if (version_compare($connection->getServerVersion(), '10.5.2', '<')) {
            return $this->compileLegacyRenameColumn($blueprint, $command, $connection);
        }

        return parent::compileRenameColumn($blueprint, $command, $connection);
    }

    /**
     * Create the column definition for a uuid type.
     *
     * @param  \LaraGram\Support\Fluent  $column
     * @return string
     */
    protected function typeUuid(Fluent $column)
    {
        return 'uuid';
    }

    /**
     * Create the column definition for a spatial Geometry type.
     *
     * @param  \LaraGram\Support\Fluent  $column
     * @return string
     */
    protected function typeGeometry(Fluent $column)
    {
        $subtype = $column->subtype ? strtolower($column->subtype) : null;

        if (! in_array($subtype, ['point', 'linestring', 'polygon', 'geometrycollection', 'multipoint', 'multilinestring', 'multipolygon'])) {
            $subtype = null;
        }

        return sprintf('%s%s',
            $subtype ?? 'geometry',
            $column->srid ? ' ref_system_id='.$column->srid : ''
        );
    }
}
