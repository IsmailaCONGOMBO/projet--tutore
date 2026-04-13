import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-admin-parametres',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './parametres.html',
  styleUrl: './parametres.css'
})
export class AdminParametres {
  loading = signal(false);
  success = signal<string | null>(null);
  error = signal<string | null>(null);

  // Paramètres système
  settings = signal({
    seuil_plagiat: 30,
    periite_soumission_themes: '2024-03-01',
    periite_depot_rapports: '2024-05-01',
    notification_auto: true,
    maintenance_mode: false
  });

  // Liste des filières
  filieres = signal([
    { id: 1, nom: 'Génie Informatique', active: true },
    { id: 2, nom: 'Génie Civil', active: true },
    { id: 3, nom: 'Génie Électrique', active: false },
    { id: 4, nom: 'Management', active: true }
  ]);

  nouvelleFiliere = signal('');
  
  sauvegarderParametres() {
    this.loading.set(true);
    this.error.set(null);
    this.success.set(null);

    // Simuler une sauvegarde
    setTimeout(() => {
      this.success.set('Paramètres sauvegardés avec succès!');
      this.loading.set(false);
      
      setTimeout(() => {
        this.success.set(null);
      }, 3000);
    }, 1500);
  }

  ajouterFiliere() {
    const nom = this.nouvelleFiliere().trim();
    if (!nom) return;

    const newFiliere = {
      id: Math.max(...this.filieres().map(f => f.id)) + 1,
      nom: nom,
      active: true
    };

    this.filieres.update(filieres => [...filieres, newFiliere]);
    this.nouvelleFiliere.set('');
  }

  toggleFiliere(id: number) {
    this.filieres.update(filieres => 
      filieres.map(f => f.id === id ? { ...f, active: !f.active } : f)
    );
  }

  supprimerFiliere(id: number) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette filière ?')) {
      this.filieres.update(filieres => filieres.filter(f => f.id !== id));
    }
  }
}
