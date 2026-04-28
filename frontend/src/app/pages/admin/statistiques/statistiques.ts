import { Component, inject, OnInit, AfterViewInit, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { forkJoin } from 'rxjs';
import { StatistiqueService, StatistiqueGlobale, FiliereStat, PromotionStat } from '../../../core/services/statistique.service';
import Chart from 'chart.js/auto';

@Component({
  selector: 'app-admin-statistiques',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './statistiques.html',
  styleUrl: './statistiques.css'
})
export class AdminStatistiques implements OnInit, AfterViewInit {
  private statistiqueService = inject(StatistiqueService);
  
  @ViewChild('filiereChart') filiereChartRef!: ElementRef;
  @ViewChild('promotionChart') promotionChartRef!: ElementRef;
  
  loading = true;
  error: string | null = null;
  
  statistiques: StatistiqueGlobale | null = null;
  filiereStats: FiliereStat[] = [];
  promotionStats: PromotionStat[] = [];

  ngOnInit() {
    this.chargerDonnees();
  }

  ngAfterViewInit() {
    // Initial charts will be created after data is loaded
  }

  chargerDonnees() {
    this.loading = true;
    this.error = null;
    
    forkJoin({
      global: this.statistiqueService.getGlobalAdvancedStats(),
      filiere: this.statistiqueService.getFiliereStats(),
      promotion: this.statistiqueService.getPromotionStats()
    }).subscribe({
      next: (res) => {
        this.statistiques = res.global;
        this.filiereStats = res.filiere;
        this.promotionStats = res.promotion;
        this.loading = false;
        
        // Wait for DOM update
        setTimeout(() => this.initCharts(), 0);
      },
      error: (err) => {
        console.error('Erreur stats:', err);
        this.error = 'Impossible de charger les statistiques détaillées';
        this.loading = false;
      }
    });
  }

  initCharts() {
    if (this.filiereChartRef) {
      new Chart(this.filiereChartRef.nativeElement, {
        type: 'bar',
        data: {
          labels: this.filiereStats.map(f => f.nom),
          datasets: [{
            label: 'Taux de Plagiat (%)',
            data: this.filiereStats.map(f => f.taux_plagiat_moyen),
            backgroundColor: this.filiereStats.map(f => this.getPlagiatColor(f.taux_plagiat_moyen)),
            borderRadius: 8
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, max: 100 } }
        }
      });
    }

    if (this.promotionChartRef) {
      new Chart(this.promotionChartRef.nativeElement, {
        type: 'line',
        data: {
          labels: this.promotionStats.map(p => p.annee).reverse(),
          datasets: [{
            label: 'Taux de Plagiat Moyen',
            data: this.promotionStats.map(p => p.taux_plagiat_moyen).reverse(),
            borderColor: '#6366f1',
            tension: 0.4,
            fill: true,
            backgroundColor: 'rgba(99, 102, 241, 0.1)'
          }]
        },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true, max: 100 } }
        }
      });
    }
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
}
