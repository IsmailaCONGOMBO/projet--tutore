import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { forkJoin } from 'rxjs';
import { StatistiqueService, StatistiqueGlobale, EvolutionData } from '../../../core/services/statistique.service';

@Component({
  selector: 'app-admin-statistiques',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './statistiques.html',
  styleUrl: './statistiques.css'
})
export class AdminStatistiques implements OnInit {
  private statistiqueService = inject(StatistiqueService);
  
  loading = true;
  error: string | null = null;
  
  statistiques: StatistiqueGlobale | null = null;
  evolution: EvolutionData[] = [];

  ngOnInit() {
    this.chargerStatistiques();
  }

  chargerStatistiques() {
    this.loading = true;
    this.error = null;
    let pending = 2;

    const checkDone = () => {
      pending--;
      if (pending === 0) {
        this.loading = false;
      }
    };
    
    this.statistiqueService.getStatistiquesGlobales().subscribe({
      next: (data) => {
        this.statistiques = data;
        checkDone();
      },
      error: (err) => {
        console.error('Erreur stats:', err);
        this.error = 'Impossible de charger les statistiques';
        checkDone();
      }
    });

    this.statistiqueService.getEvolutionRapports().subscribe({
      next: (data) => {
        this.evolution = data;
        checkDone();
      },
      error: (err) => {
        console.error('Erreur evolution:', err);
        checkDone();
      }
    });
  }

  getPlagiatColor(taux: number): string {
    if (taux < 20) return '#10b981'; // Vert
    if (taux < 40) return '#f59e0b'; // Orange
    return '#ef4444'; // Rouge
  }

  getPlagiatLabel(taux: number): string {
    if (taux < 20) return 'Faible';
    if (taux < 40) return 'Moyen';
    return 'Élevé';
  }

  getProgressPercentage(count: number): number {
    if (!this.statistiques?.rapports_par_filiere?.length) return 0;
    const maxCount = Math.max(...this.statistiques.rapports_par_filiere.map(f => f.count));
    return maxCount > 0 ? (count / maxCount) * 100 : 0;
  }
}
