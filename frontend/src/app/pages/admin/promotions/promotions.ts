import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PromotionService, Promotion } from '../../../core/services/promotion.service';

@Component({
  selector: 'app-promotions',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="page">
      <header class="page-header">
        <div>
          <h1>Gestion des Promotions</h1>
          <p>Suivez et organisez les différentes promotions d'étudiants.</p>
        </div>
        <button class="btn btn-primary" (click)="openModal()">
          <span class="material-icons">add</span>
          Nouvelle Promotion
        </button>
      </header>

      <div class="card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Année</th>
              <th>Libellé</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @for (promo of promotions; track promo.id) {
              <tr>
                <td><strong>{{ promo.annee }}</strong></td>
                <td>{{ promo.libelle }}</td>
                <td class="actions">
                  <button class="btn-icon" (click)="editPromotion(promo)" title="Modifier">
                    <span class="material-icons">edit</span>
                  </button>
                  <button class="btn-icon text-danger" (click)="deletePromotion(promo.id!)" title="Supprimer">
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
              <h2>{{ selectedPromotion.id ? 'Modifier' : 'Ajouter' }} une Promotion</h2>
              <button class="btn-icon" (click)="closeModal()"><span class="material-icons">close</span></button>
            </header>
            <div class="modal-body">
              <div class="form-group">
                <label>Année</label>
                <input type="number" [(ngModel)]="selectedPromotion.annee" placeholder="Ex: 2024">
              </div>
              <div class="form-group">
                <label>Libellé</label>
                <input type="text" [(ngModel)]="selectedPromotion.libelle" placeholder="Ex: Promotion 2024 - Master">
              </div>
            </div>
            <footer class="modal-footer">
              <button class="btn btn-secondary" (click)="closeModal()">Annuler</button>
              <button class="btn btn-primary" (click)="savePromotion()" [disabled]="!selectedPromotion.annee || !selectedPromotion.libelle">
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
  `],
})
export class Promotions implements OnInit {
  private promotionService = inject(PromotionService);
  
  promotions: Promotion[] = [];
  showModal = false;
  selectedPromotion: Partial<Promotion> = {};

  ngOnInit() {
    this.loadPromotions();
  }

  loadPromotions() {
    this.promotionService.getPromotions().subscribe(data => this.promotions = data);
  }

  openModal() {
    this.selectedPromotion = { annee: new Date().getFullYear() };
    this.showModal = true;
  }

  closeModal() {
    this.showModal = false;
  }

  editPromotion(promotion: Promotion) {
    this.selectedPromotion = { ...promotion };
    this.showModal = true;
  }

  savePromotion() {
    if (this.selectedPromotion.id) {
      this.promotionService.updatePromotion(this.selectedPromotion.id, this.selectedPromotion as Promotion)
        .subscribe(() => {
          this.loadPromotions();
          this.closeModal();
        });
    } else {
      this.promotionService.addPromotion(this.selectedPromotion as Promotion)
        .subscribe(() => {
          this.loadPromotions();
          this.closeModal();
        });
    }
  }

  deletePromotion(id: number) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette promotion ?')) {
      this.promotionService.deletePromotion(id).subscribe(() => this.loadPromotions());
    }
  }
}
