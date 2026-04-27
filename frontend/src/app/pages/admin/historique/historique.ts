import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HistoriqueService, Historique } from '../../../core/services/historique.service';

@Component({
  selector: 'app-admin-historique',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './historique.html',
  styleUrl: './historique.css'
})
export class AdminHistorique implements OnInit {
  private historiqueService = inject(HistoriqueService);
  
  loading = signal(false);
  actions = signal<Historique[]>([]);

  ngOnInit() {
    this.chargerHistorique();
  }

  chargerHistorique() {
    this.loading.set(true);
    this.historiqueService.getRecentActions().subscribe({
      next: (res) => {
        this.actions.set(res.data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  getActionLabel(action: string): string {
    const labels: { [key: string]: string } = {
      'VALIDATION_NOTE': 'Validation de note',
      'REJET_NOTE': 'Rejet de note',
      'CREATION_FILIERE': 'Création de filière',
      'UPDATE_FILIERE': 'Mise à jour de filière',
      'SUPPRESSION_FILIERE': 'Suppression de filière',
      'CREATION_UTILISATEUR': 'Création d\'utilisateur',
      'UPDATE_UTILISATEUR': 'Mise à jour d\'utilisateur',
      'SUPPRESSION_UTILISATEUR': 'Suppression d\'utilisateur'
    };
    return labels[action] || action;
  }

  getActionClass(action: string): string {
    if (action.includes('VALIDATION') || action.includes('CREATION')) return 'status-success';
    if (action.includes('REJET') || action.includes('SUPPRESSION')) return 'status-error';
    return 'status-info';
  }
}
