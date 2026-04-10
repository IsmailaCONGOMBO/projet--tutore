<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Création de l'Administrateur
        User::updateOrCreate(['email' => 'admin@test.com'], [
            'name' => 'Admin Système',
            'password' => bcrypt('1234'),
            'role' => 'admin'
        ]);

        // 2. Création du Chef de Département
        User::updateOrCreate(['email' => 'chef@test.com'], [
            'name' => 'Chef Informatique',
            'password' => bcrypt('1234'),
            'role' => 'chef_departement'
        ]);

        // 3. Création de l'Enseignant Correcteur
        $enseignantUser = User::updateOrCreate(['email' => 'enseignant@test.com'], [
            'name' => 'Prof. Enseignant Test',
            'password' => bcrypt('1234'),
            'role' => 'enseignant'
        ]);
        
        \App\Models\Enseignant::updateOrCreate(
            ['user_id' => $enseignantUser->id],
            ['grade' => 'Professeur Titulaire', 'specialite' => 'Génie Logiciel']
        );

        // 4. Création de l'Étudiant
        $etudiantUser = User::updateOrCreate(['email' => 'etudiant@test.com'], [
            'name' => 'Étudiant Test',
            'password' => bcrypt('1234'),
            'role' => 'etudiant'
        ]);

        \App\Models\Etudiant::updateOrCreate(
            ['user_id' => $etudiantUser->id],
            ['matricule' => 'ETU2026', 'niveau' => 'Licence 3', 'annee_academique' => '2025-2026']
        );
    }

}
