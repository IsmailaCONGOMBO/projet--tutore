import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FiliereService, Filiere } from '../../../core/services/filiere.service';

@Component({
  selector: 'app-admin-parametres',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './parametres.html',
  styleUrl: './parametres.css'
})
export class AdminParametres implements OnInit {
  private filiereService = inject(FiliereService);

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
  filieres = signal<Filiere[]>([]);
  nouvelleFiliere = signal('');

  ngOnInit() {
    this.chargerFilieres();
  }

  chargerFilieres() {
    this.filiereService.getFilieres().subscribe({
      next: (data) => this.filieres.set(data),
      error: (err) => this.error.set('Erreur lors du chargement des filières')
    });
  }
  
  sauvegarderParametres() {
    this.loading.set(true);
    this.error.set(null);
    this.success.set(null);

    // Simuler une sauvegarde des paramètres système pour le moment
    setTimeout(() => {
      this.success.set('Paramètres sauvegardés avec succès!');
      this.loading.set(false);
      
      setTimeout(() => {
        this.success.set(null);
      }, 3000);
    }, 1000);
  }

  ajouterFiliere() {
    const nom = this.nouvelleFiliere().trim();
    if (!nom) return;

    this.loading.set(true);
    this.filiereService.createFiliere({ nom }).subscribe({
      next: (filiere) => {
        this.filieres.update(filieres => [...filieres, filiere]);
        this.nouvelleFiliere.set('');
        this.success.set('Filière ajoutée avec succès');
        this.loading.set(false);
        setTimeout(() => this.success.set(null), 3000);
      },
      error: () => {
        this.error.set("Erreur lors de l'ajout de la filière");
        this.loading.set(false);
      }
    });
  }

  toggleFiliere(id: number) {
    const filiere = this.filieres().find(f => f.id === id);
    if (!filiere) return;

    const newStatus = !filiere.active;
    this.filiereService.updateFiliere(id, { active: newStatus }).subscribe({
      next: (updated) => {
        this.filieres.update(filieres => 
          filieres.map(f => f.id === id ? { ...f, active: updated.active } : f)
        );
      },
      error: () => this.error.set('Erreur lors du changement de statut')
    });
  }

  supprimerFiliere(id: number) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette filière ?')) {
      this.loading.set(true);
      this.filiereService.deleteFiliere(id).subscribe({
        next: () => {
          this.filieres.update(filieres => filieres.filter(f => f.id !== id));
          this.success.set('Filière supprimée');
          this.loading.set(false);
          setTimeout(() => this.success.set(null), 3000);
        },
        error: () => {
          this.error.set('Erreur lors de la suppression');
          this.loading.set(false);
        }
      });
    }
  }
}

