<?php

use think\migration\Migrator;
use think\migration\db\Column;

class TestTwo extends Migrator
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('user');


//         inserting multiple rows
        $rows = [
            [
                'id'    => 2,
                'name'  => 'Stopped'
            ],
            [
                'id'    => 3,
                'name'  => 'Queued'
            ]
        ];

        $table->insert($rows)->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM user where id in (2,3)');
    }
}
