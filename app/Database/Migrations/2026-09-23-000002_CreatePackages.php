<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePackages extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'comment'    => 'Advertised package amount',
            ],
            'return_percentage' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'comment'    => 'Annual rate, prorated by the withdrawal frequency',
            ],
            'withdrawal_type' => [
                'type'       => 'ENUM',
                'constraint' => ['weekly', 'monthly', 'quarterly', 'yearly'],
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
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
        $this->forge->addUniqueKey('name');
        $this->forge->addKey('status');

        $this->forge->createTable('packages', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('packages', true);
    }
}
