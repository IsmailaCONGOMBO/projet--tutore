import { Component, inject, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RapportService } from '../../../core/services/rapport.service';

@Component({
  selector: 'app-admin-rapports-corrections',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './rapports-corrections.html',
  styleUrl: './rapports-corrections.css'
})
export class AdminRapportsCorrections implements OnInit {
  private rapportService = inject(RapportService);

  rapports = signal<any[]>([]);
  loading = signal(true);
  searchTerm = signal('');
  filterStatut = signal('TOUS');

  filteredRapports = computed(() => {
    return this.rapports().filter(r => {
      const matchesSearch = r.titre.toLowerCase().includes(this.searchTerm().toLowerCase()) ||
                           r.etudiant.toLowerCase().includes(this.searchTerm().toLowerCase());
      const matchesFilter = this.filterStatut() === 'TOUS' || r.statut === this.filterStatut();
      return matchesSearch && matchesFilter;
    });
  });

  ngOnInit() {
    this.chargerRapports();
  }

  chargerRapports() {
    this.loading.set(true);
    this.rapportService.getTousLesRapports().subscribe({
      next: (data) => {
        this.rapports.set(data);
        this.loading.set(false);
      },
      error: (err) => {
        console.error('Erreur chargement rapports', err);
        this.loading.set(false);
      }
    });
  }

  getStatutClass(statut: string): string {
    switch (statut) {
      case 'EN_ATTENTE': return 'badge-attente';
      case 'ANALYSE': return 'badge-analyse';
      case 'NOTE': return 'badge-note';
      case 'ARCHIVE': return 'badge-archive';
      default: return '';
    }
  }

  getPlagiatColor(taux: number): string {
    if (taux < 15) return '#10b981';
    if (taux < 30) return '#f59e0b';
    return '#ef4444';
  }
}
