<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class FontsAddVendorIndex extends Migration
{
    private $tableName = 'fonts';
    private $indexName = 'fonts_vendor_index';

    public function up()
    {
        $capsule = new Capsule();
        $driver = $capsule::connection()->getDriverName();

        // SQLite can index TEXT directly; MySQL needs a prefix length.
        if ($driver === 'mysql') {
            $capsule::statement("CREATE INDEX {$this->indexName} ON {$this->tableName} (vendor(191))");
            return;
        }

        $indexName = $this->indexName;
        $capsule::schema()->table($this->tableName, function (Blueprint $table) use ($indexName) {
            $table->index('vendor', $indexName);
        });
    }

    public function down()
    {
        $capsule = new Capsule();
        $indexName = $this->indexName;
        $capsule::schema()->table($this->tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
} 