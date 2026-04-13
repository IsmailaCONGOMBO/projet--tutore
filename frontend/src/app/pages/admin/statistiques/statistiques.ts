import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
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
    
    // Charger les stats globales
    this.statistiqueService.getStatistiquesGlobales().subscribe({
      next: (data) => {
        this.statistiques = data;
        // Ne pas mettre loading = false ici pour attendre l'évolution
      },
      error: (err) => {
        console.error('Erreur stats globales:', err);
        this.error = 'Impossible de charger les statistiques globales';
      }
    });

    // Charger l'évolution
    this.statistiqueService.getEvolutionRapports().subscribe({
      next: (data) => {
        this.evolution = data;
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur évolution:', err);
        this.loading = false;
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
