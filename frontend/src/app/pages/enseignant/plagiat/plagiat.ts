import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AnalyseService, AnalysePlagiat } from '../../../core/services/analyse.service';

@Component({
  selector: 'app-enseignant-plagiat',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './plagiat.html',
  styleUrl: './plagiat.css'
})
export class EnseignantPlagiat implements OnInit {
  private route = inject(ActivatedRoute);
  private analyseSvc = inject(AnalyseService);

  rapportId: number = 0;
  analyse = signal<AnalysePlagiat | null>(null);
  loading = signal(true);
  error = signal('');

  ngOnInit() {
    this.rapportId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadAnalyse();
  }

  loadAnalyse() {
    if (!this.rapportId) return;
    this.loading.set(true);
    this.error.set('');

    this.analyseSvc.getResultat(this.rapportId).subscribe({
      next: (data) => {
        this.analyse.set(data);
        this.loading.set(false);
      },
      error: () => {
        this.error.set("Impossible de charger les détails de l'analyse.");
        this.loading.set(false);
      }
    });
  }

  get taux(): number { return this.analyse()?.taux_plagiat ?? 0; }

  get tauxColor(): string {
    if (this.taux < 20) return '#16a34a';
    if (this.taux < 40) return '#d97706';
    return '#dc2626';
  }

  get tauxLabel(): string {
    if (this.taux < 20) return 'Taux Acceptable';
    if (this.taux < 40) return 'Taux Suspect';
    return 'Plagiat Probable';
  }

  get tauxBg(): string {
    if (this.taux < 20) return '#f0fdf4';
    if (this.taux < 40) return '#fffbeb';
    return '#fef2f2';
  }
}
