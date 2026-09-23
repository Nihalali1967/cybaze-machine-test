<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWithdrawals extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'investment_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'NULL when the payout spans several investments',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'profit_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'paid'],
                'default'    => 'paid',
            ],
            'requested_at' => [
                'type' => 'DATETIME',
            ],
            'processed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'processed_by' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'Admin who actioned the payout',
            ],
            'admin_remarks' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addKey('investment_id');

        $this->forge->addForeignKey('user_id', 'users', 'id', onUpdate: 'CASCADE', onDelete: 'CASCADE');
        $this->forge->addForeignKey('investment_id', 'investments', 'id', onUpdate: 'CASCADE', onDelete: 'SET NULL');
        $this->forge->addForeignKey('processed_by', 'users', 'id', onUpdate: 'CASCADE', onDelete: 'SET NULL');

        $this->forge->createTable('withdrawals', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('withdrawals', true);
    }
}
