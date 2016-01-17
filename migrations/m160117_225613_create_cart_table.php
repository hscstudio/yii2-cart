<?php

use yii\db\Migration;
use yii\db\Schema;

class m160117_225613_create_cart_table extends Migration
{
	/**
     *
     */
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('cart', [
            'id' => $this->string(255),
            'user_id' => $this->integer(11),
            'name' => $this->string(255)->notNull(),
            'value' => $this->text()->notNull(),
            'status' => $this->boolean()->notNull()->defaultValue(0),
            'PRIMARY KEY (id)',
        ],$tableOptions);
    }

	/**
     * @return bool
     */
    public function down()
    {
        echo "m160117_225613_create_cart_table cannot be reverted.\n";
        $this->dropTable('cart');
        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
