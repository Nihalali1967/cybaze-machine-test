<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The profit ledger.
 *
 * UNIQUE (investment_id, due_date) is the hard guarantee that the same
 * period can never be generated twice for the same investment, even if two
 * admins process profits at the same moment.
 *
 * The unique key is deliberately on due_date and not on period_key: if an
 * investment's frequency is ever changed, two different schedules can fall in
 * the same calendar week/month and a period_key-only index would wrongly
 * block a legitimate payout.
 */
class CreateProfits extends Migration
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
            'investment_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'comment'  => 'Denormalised for fast customer history queries',
            ],
            'period_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'comment'    => '2026-W39 | 2026-09 | 2026-Q3 | 2026 (display only)',
            ],
            'period_start' => [
                'type' => 'DATE',
            ],
            'period_end' => [
                'type' => 'DATE',
            ],
            'due_date' => [
                'type'    => 'DATE',
                'comment' => 'Schedule anchor - part of the unique key',
            ],
            'investment_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'comment'    => 'Snapshot so history rows survive package edits',
            ],
            'profit_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['available', 'withdrawn'],
                'default'    => 'available',
            ],
            'withdrawal_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => true,
            ],
            'processed_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addUniqueKey(['investment_id', 'due_date']);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addKey('withdrawal_id');

        $this->forge->addForeignKey('investment_id', 'investments', 'id', onUpdate: 'CASCADE', onDelete: 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', onUpdate: 'CASCADE', onDelete: 'CASCADE');
        $this->forge->addForeignKey('withdrawal_id', 'withdrawals', 'id', onUpdate: 'CASCADE', onDelete: 'SET NULL');

        $this->forge->createTable('profits', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('profits', true);
    }
}
