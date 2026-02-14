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
        // Nurse: le champ 'diploma' existant reste pour le nom/texte du diplôme
        // On ajoute 'diploma_path' pour le fichier uploadé
        Schema::table('nurse_profiles', function (Blueprint $table) {
            $table->string('diploma_path', 500)->nullable()->after('diploma');
        });

        // Patient: 'medical_notes' reste (texte libre)
        // On ajoute 'medical_files' pour stocker les chemins des fichiers (JSON array)
        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->json('medical_files')->nullable()->after('medical_notes');
        });
    }

    public function down(): void
    {
        Schema::table('nurse_profiles', function (Blueprint $table) {
            $table->dropColumn('diploma_path');
        });

        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->dropColumn('medical_files');
        });
    }
};
