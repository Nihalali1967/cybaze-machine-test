<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Audit log of every "Process Profit" run (including dry runs).
 * Lets the admin answer "was this week already processed?" with evidence.
 */
class CreateProfitRuns extends Migration
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
            'run_by' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => true,
            ],
            'as_of' => [
                'type' => 'DATE',
            ],
            'investments_scanned' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'periods_created' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'periods_skipped' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0,
            ],
            'is_dry_run' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'run_at' => [
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
        $this->forge->addForeignKey('run_by', 'users', 'id', onUpdate: 'CASCADE', onDelete: 'SET NULL');

        $this->forge->createTable('profit_runs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('profit_runs', true);
    }
}
