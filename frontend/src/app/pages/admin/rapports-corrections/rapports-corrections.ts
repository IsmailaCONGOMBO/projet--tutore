import { Component, inject, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
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
  private router = inject(Router);

  rapports = signal<any[]>([]);
  loading = signal(true);
  searchTerm = signal('');
  filterStatut = signal('ALL');
  selectedRapport = signal<any | null>(null);

  filteredRapports = computed(() => {
    const term = this.searchTerm().toLowerCase();
    const status = this.filterStatut();
    
    return this.rapports().filter(r => {
      const matchesSearch = !term || 
                           r.titre?.toLowerCase().includes(term) ||
                           r.etudiant?.user?.name.toLowerCase().includes(term);
      const matchesFilter = status === 'ALL' || r.statut === status;
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
      case 'EN_ATTENTE_ANALYSE_CHEF': return 'badge-attente';
      case 'VALIDE_PLAGIAT': return 'badge-valide-plagiat';
      case 'REJETE_PLAGIAT': return 'badge-rejete';
      case 'ASSIGNE_ENSEIGNANT': return 'badge-assigne';
      case 'NOTE_SOUMISE': return 'badge-note';
      case 'VALIDE_FINAL': return 'badge-archive';
      default: return 'badge-default';
    }
  }

  getPlagiatColor(taux: number): string {
    if (taux < 15) return '#10b981';
    if (taux < 30) return '#f59e0b';
    return '#ef4444';
  }

  onSearch(event: any) {
    this.searchTerm.set(event.target.value);
  }

  onFilter(event: any) {
    this.filterStatut.set(event.target.value);
  }

  selectRapport(r: any) {
    this.selectedRapport.set(r);
  }

  voirPlagiat(id: number) {
    this.router.navigate(['/admin/plagiat', id]);
  }

  download(id: number) {
    this.rapportService.download(id).subscribe(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `rapport_${id}.pdf`;
      a.click();
    });
  }
}
