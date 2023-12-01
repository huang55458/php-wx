<?php

use think\migration\Migrator;
use think\migration\db\Column;

/*
 * 运行会一次跑所有文件，回滚按时间倒序逐一回滚
 */
class Test extends Migrator
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('user');

        // inserting only one row
        $singleRow = [
            'id'    => 1,
            'name'  => 'In Progress'
        ];

        $table->insert($singleRow)->saveData();

        // inserting multiple rows
//        $rows = [
//            [
//                'id'    => 2,
//                'name'  => 'Stopped'
//            ],
//            [
//                'id'    => 3,
//                'name'  => 'Queued'
//            ]
//        ];
//
//        $table->insert($rows)->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM user  where id = 1');
    }
}
