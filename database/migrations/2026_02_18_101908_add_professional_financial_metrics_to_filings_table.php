<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('filings', function (Blueprint $table) {
            // Indentifiers
            $table->string('cik')->nullable()->after('title')->index();
            $table->string('ticker')->nullable()->after('cik')->index();

            // Profitability
            $table->decimal('reported_eps', 10, 4)->nullable();
            $table->decimal('estimated_eps', 10, 4)->nullable();
            $table->decimal('profit_margin', 10, 4)->nullable();
            $table->decimal('roe', 10, 4)->nullable(); 
            
            // Valuation
            $table->decimal('pe_ratio', 10, 4)->nullable();
            $table->decimal('ps_ratio', 10, 4)->nullable();            

            // Risk & Health
            $table->decimal('debt_to_equity', 10, 4)->nullable();
            $table->decimal('current_ratio', 10, 4)->nullable();
            $table->decimal('capex', 18, 4)->nullable();  
            
            // Yield
            $table->decimal('dividend_yield', 10, 4)->nullable();

            // Historical data for charts
            $table->json('financial_history')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filings', function (Blueprint $table) {
            $table->dropColumn([
                'cik', 'ticker', 'reported_eps', 'estimated_eps',
                'profit_margin', 'roe', 'pe_ratio', 'ps_ratio', 'debt_to_equity',
                'current_ratio', 'capex', 'dividend_yield', 'financial_history'
            ]);
        });
    }
};
