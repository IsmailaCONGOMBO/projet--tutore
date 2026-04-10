import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';
import { RapportService, Rapport } from '../../../core/services/rapport.service';

@Component({
  selector: 'app-enseignant-dashboard',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="page-header">
      <h1 class="page-title">Bienvenue, {{ user.name }}</h1>
      <p class="page-subtitle">Voici un aperçu de votre activité.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background: #eff6ff; color: #1a56db;">📄</div>
        <div class="stat-info">
          <span class="stat-label">Assignés</span>
          <span class="stat-value">{{ nbAssignes() }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: #fdf4ff; color: #d946ef;">✅</div>
        <div class="stat-info">
          <span class="stat-label">Corrigés</span>
          <span class="stat-value">{{ nbCorriges() }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: #ecfdf5; color: #059669;">⭐</div>
        <div class="stat-info">
          <span class="stat-label">Notes Soumises</span>
          <span class="stat-value">{{ nbNotes() }}</span>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .page-header { margin-bottom: 2rem; }
    .page-title { font-size: 1.875rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; }
    .page-subtitle { color: #64748b; font-size: 1rem; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; }
    .stat-card { background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem; }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-info { display: flex; flex-direction: column; }
    .stat-label { font-size: 0.875rem; font-weight: 600; color: #64748b; }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: #1e293b; }
  `]
})
export class EnseignantDashboard implements OnInit {
  private auth = inject(AuthService);
  private rapportService = inject(RapportService);

  user = this.auth.getUser();
  nbAssignes = signal(0);
  nbCorriges = signal(0);
  nbNotes    = signal(0);

  ngOnInit() {
    this.rapportService.getRapportsAssignes().subscribe({
      next: (rapports: Rapport[]) => {
        this.nbAssignes.set(rapports.length);
        this.nbCorriges.set(rapports.filter(r => r.statut === 'CORRIGE').length);
        this.nbNotes.set(rapports.filter(r => r.statut === 'NOTE').length);
      }
    });
  }
}
