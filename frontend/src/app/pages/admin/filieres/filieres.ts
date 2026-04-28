import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FiliereService, Filiere } from '../../../core/services/filiere.service';

@Component({
  selector: 'app-filieres',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="page">
      <header class="page-header">
        <div>
          <h1>Gestion des Filières</h1>
          <p>Configurez et gérez les filières de formation de l'établissement.</p>
        </div>
        <button class="btn btn-primary" (click)="openModal()">
          <span class="material-icons">add</span>
          Nouvelle Filière
        </button>
      </header>

      <div class="card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Description</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @for (filiere of filieres; track filiere.id) {
              <tr>
                <td><strong>{{ filiere.code }}</strong></td>
                <td>{{ filiere.nom }}</td>
                <td>{{ filiere.description }}</td>
                <td>
                  <span class="badge" [class.badge-VALIDE]="filiere.active" [class.badge-REJETE]="!filiere.active">
                    {{ filiere.active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="actions">
                  <button class="btn-icon" (click)="editFiliere(filiere)" title="Modifier">
                    <span class="material-icons">edit</span>
                  </button>
                  <button class="btn-icon text-danger" (click)="deleteFiliere(filiere.id!)" title="Supprimer">
                    <span class="material-icons">delete</span>
                  </button>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>

      <!-- Modal -->
      @if (showModal) {
        <div class="modal-overlay">
          <div class="modal-card">
            <header class="modal-header">
              <h2>{{ selectedFiliere.id ? 'Modifier' : 'Ajouter' }} une Filière</h2>
              <button class="btn-icon" (click)="closeModal()"><span class="material-icons">close</span></button>
            </header>
            <div class="modal-body">
              <div class="form-group">
                <label>Nom de la filière</label>
                <input type="text" [(ngModel)]="selectedFiliere.nom" placeholder="Ex: Informatique de Gestion">
              </div>
              <div class="form-group">
                <label>Code (optionnel)</label>
                <input type="text" [(ngModel)]="selectedFiliere.code" placeholder="Ex: IG">
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea [(ngModel)]="selectedFiliere.description" rows="3"></textarea>
              </div>
            </div>
            <footer class="modal-footer">
              <button class="btn btn-secondary" (click)="closeModal()">Annuler</button>
              <button class="btn btn-primary" (click)="saveFiliere()" [disabled]="!selectedFiliere.nom">
                Enregistrer
              </button>
            </footer>
          </div>
        </div>
      }
    </div>
  `,
  styles: [`
    .actions { display: flex; gap: 10px; }
    .text-danger { color: #ef4444; }
    textarea { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; }
  `],
})
export class Filieres implements OnInit {
  private filiereService = inject(FiliereService);
  
  filieres: Filiere[] = [];
  showModal = false;
  selectedFiliere: Partial<Filiere> = {};

  ngOnInit() {
    this.loadFilieres();
  }

  loadFilieres() {
    this.filiereService.getFilieres().subscribe(data => this.filieres = data);
  }

  openModal() {
    this.selectedFiliere = { active: true };
    this.showModal = true;
  }

  closeModal() {
    this.showModal = false;
  }

  editFiliere(filiere: Filiere) {
    this.selectedFiliere = { ...filiere };
    this.showModal = true;
  }

  saveFiliere() {
    if (this.selectedFiliere.id) {
      this.filiereService.updateFiliere(this.selectedFiliere.id, this.selectedFiliere as Filiere)
        .subscribe(() => {
          this.loadFilieres();
          this.closeModal();
        });
    } else {
      this.filiereService.createFiliere(this.selectedFiliere as Filiere)
        .subscribe(() => {
          this.loadFilieres();
          this.closeModal();
        });
    }
  }

  deleteFiliere(id: number) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette filière ?')) {
      this.filiereService.deleteFiliere(id).subscribe(() => this.loadFilieres());
    }
  }
}
