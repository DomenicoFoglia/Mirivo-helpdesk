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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            //Inizialmente il ticket non e' assegnato ad alcun tecnico quindi e' NULL. Se viene cancellato il tecnico
            //il campo viene rimesso a  null con SET NULL. Avessimo usato CASCADE il ticket sarebbe stato cancecllato
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');
            //Se si tenta di eliminare una categoria che ha ancora dei ticket attivi, l'admin e' costretto a spostare i ticket
            // in un'altra categoria. Usiamo quindi restrictOnDelete()
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->enum('status', ['open', 'working', 'escalated', 'closed'])->default('open');
            //La priorita' e' gestita dai tecnici quindi inizialmente sara' NULL
            $table->enum('priority', ['low', 'medium', 'high'])->nullable();
            $table->timestamps();
            $table->date('closed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
