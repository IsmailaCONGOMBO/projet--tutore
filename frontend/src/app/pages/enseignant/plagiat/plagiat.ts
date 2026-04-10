import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AnalyseService, AnalysePlagiat } from '../../../core/services/analyse.service';

@Component({
  selector: 'app-enseignant-plagiat',
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <div class="page-header">
      <div class="header-actions">
        <a routerLink="/enseignant/rapports" class="btn-back">← Retour aux rapports</a>
      </div>
      <h1 class="page-title">Rapport d'Analyse de Plagiat</h1>
      <p class="page-subtitle">Détails des similitudes détectées pour le rapport #{{ rapportId }}</p>
    </div>

    @if (loading()) {
      <div class="loading-state">
        <div class="spinner"></div>
        <p>Analyse des résultats en cours...</p>
      </div>
    } @else if (error()) {
      <div class="error-state">
        <span class="icon">⚠️</span>
        <p>{{ error() }}</p>
        <button (click)="loadAnalyse()" class="btn-retry">Réessayer</button>
      </div>
    } @else if (analyse()) {
      <div class="stats-overview">
        <div class="rate-card" [style.background]="tauxBg">
          <div class="rate-circle" [style.border-color]="tauxColor">
            <span class="rate-value" [style.color]="tauxColor">{{ analyse()?.taux_plagiat }}%</span>
            <span class="rate-label">Similitude globale</span>
          </div>
          <div class="rate-info">
            <h3 [style.color]="tauxColor">{{ tauxLabel }}</h3>
            <p>Ce score représente le pourcentage total de texte identifié comme potentiellement plagié.</p>
          </div>
        </div>
      </div>

      <div class="content-card">
        <h2 class="section-title">Passages Suspects</h2>
        <div class="passages-list">
          @for (p of analyse()?.passages_suspects; track $index) {
            <div class="passage-item">
              <div class="passage-header">
                <span class="source-label">Source : <strong>{{ p.source }}</strong></span>
                <span class="similarity-tag">{{ p.similarite }}% identifiant</span>
              </div>
              <p class="passage-text">"{{ p.texte }}"</p>
            </div>
          } @empty {
            <div class="empty-passages">
              <span>✅</span>
              <p>Aucun passage suspect n'a été détecté dans ce document.</p>
            </div>
          }
        </div>
      </div>
    }
  `,
  styles: [`
    .page-header { margin-bottom: 2rem; }
    .btn-back { display: inline-block; color: #64748b; text-decoration: none; font-size: 0.875rem; margin-bottom: 1rem; transition: color 0.2s; }
    .btn-back:hover { color: #1e293b; }
    .page-title { font-size: 1.875rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; }
    .page-subtitle { color: #64748b; }

    .loading-state, .error-state { padding: 4rem; text-align: center; background: white; border-radius: 16px; border: 1px solid #e2e8f0; }
    .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3b82f6; border-radius: 50%; margin: 0 auto 1rem; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    .stats-overview { margin-bottom: 2rem; }
    .rate-card { display: flex; align-items: center; gap: 2rem; padding: 2rem; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); }
    .rate-circle { width: 140px; height: 140px; border: 8px solid; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: white; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .rate-value { font-size: 2rem; font-weight: 800; line-height: 1; }
    .rate-label { font-size: 0.75rem; font-weight: 600; color: #64748b; margin-top: 0.25rem; }
    .rate-info h3 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
    .rate-info p { color: #475569; max-width: 400px; }

    .content-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 2rem; }
    .section-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem; }
    .passages-list { display: flex; flex-direction: column; gap: 1.5rem; }
    .passage-item { padding: 1.5rem; background: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 0 8px 8px 0; }
    .passage-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
    .source-label { font-size: 0.875rem; color: #475569; }
    .similarity-tag { font-size: 0.75rem; font-weight: 700; background: #dbeafe; color: #1e40af; padding: 0.25rem 0.625rem; border-radius: 9999px; }
    .passage-text { color: #1e293b; font-style: italic; line-height: 1.6; }
    .empty-passages { text-align: center; padding: 3rem; color: #64748b; }
    .empty-passages span { font-size: 3rem; display: block; margin-bottom: 1rem; }
  `]
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
