<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ExpandAttachmentColumns extends Migration
{
    public function up()
    {
        // SQLite (used by the test suite) has no column length limits to relax.
        if (DB::getDriverName() !== 'mysql' && DB::getDriverName() !== 'mariadb') {
            return;
        }

        DB::statement('ALTER TABLE internships MODIFY attachments TEXT NULL');
        DB::statement('ALTER TABLE applications MODIFY attachments TEXT NULL');
    }

    public function down()
    {
        if (DB::getDriverName() !== 'mysql' && DB::getDriverName() !== 'mariadb') {
            return;
        }

        DB::statement('ALTER TABLE internships MODIFY attachments VARCHAR(255) NULL');
        DB::statement('ALTER TABLE applications MODIFY attachments VARCHAR(255) NULL');
    }
}
