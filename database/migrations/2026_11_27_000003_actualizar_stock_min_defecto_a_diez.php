<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Move businesses still on the old default to the new 10-unit minimum.
     */
    public function up(): void
    {
        DB::table('negocios')
            ->where('stock_min', 5)
            ->update(['stock_min' => 10]);
    }

    /**
     * Reverse the data change.
     */
    public function down(): void
    {
        DB::table('negocios')
            ->where('stock_min', 10)
            ->update(['stock_min' => 5]);
    }
};
