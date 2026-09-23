<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * An investment copies (snapshots) the package terms when it is created.
 * That is why every package column is duplicated here: once an investment
 * exists, later edits to the package must never change it.
 */
class CreateInvestments extends Migration
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
                'comment'  => 'The investing customer',
            ],
            'package_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'Reference only - never used for profit maths',
            ],

            // ---- Frozen package terms (written once, at creation) ----
            'package_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'package_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'return_percentage' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'withdrawal_type' => [
                'type'       => 'ENUM',
                'constraint' => ['weekly', 'monthly', 'quarterly', 'yearly'],
            ],
            'period_profit_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'comment'    => 'Profit per period, pre-computed and frozen',
            ],
            'return_basis' => [
                'type'       => 'ENUM',
                'constraint' => ['annual', 'per_period'],
                'default'    => 'annual',
            ],

            // ---- Position ----
            'investment_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'investment_date' => [
                'type' => 'DATE',
            ],
            'next_profit_date' => [
                'type'     => 'DATE',
                'null'     => true,
                'comment'  => 'First payout = investment_date + one period; NULL when completed',
            ],
            'periods_paid' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'maturity_periods' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'NULL = open ended',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'completed', 'cancelled'],
                'default'    => 'active',
            ],
            'notes' => [
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
        $this->forge->addKey(['status', 'next_profit_date']); // processor hot path
        $this->forge->addKey('package_id');

        $this->forge->addForeignKey('user_id', 'users', 'id', onUpdate: 'CASCADE', onDelete: 'CASCADE');
        // RESTRICT: a package that has investments can never be hard deleted.
        $this->forge->addForeignKey('package_id', 'packages', 'id', onUpdate: 'CASCADE', onDelete: 'RESTRICT');

        $this->forge->createTable('investments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('investments', true);
    }
}
