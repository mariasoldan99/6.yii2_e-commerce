<?php

use yii\db\Migration;

/**
 * Class m230623_124113_change_product_id_foreign_key_on_order_item_table
 */
class m230623_124113_change_product_id_foreign_key_on_order_item_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // drops foreign key for table `{{%products}}`
        $this->dropForeignKey(
            '{{%fk-order_items-product_id}}',
            '{{%order_items}}'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230623_124113_change_product_id_foreign_key_on_order_item_table cannot be reverted.\n";

        return false;
    }

}
