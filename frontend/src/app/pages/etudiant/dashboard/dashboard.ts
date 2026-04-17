import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { RapportService } from '../../../core/services/rapport.service';
import { ThemeService } from '../../../core/services/theme.service';
import { NotificationService } from '../../../core/services/notification.service';
import { AnalyseService } from '../../../core/services/analyse.service';

@Component({
  selector: 'app-etudiant-dashboard',
  imports: [RouterLink],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class EtudiantDashboard implements OnInit {
  private rapportSvc      = inject(RapportService);
  private themeSvc        = inject(ThemeService);
  private notifSvc        = inject(NotificationService);
  private analyseSvc      = inject(AnalyseService);

  user = typeof window !== 'undefined' ? JSON.parse(localStorage.getItem('user') || '{}') : {};

  nbRapports    = signal(0);
  nbThemes      = signal(0);
  nbNotifs      = signal(0);
  dernierTaux   = signal<number | null>(null);
  dernierStatut = signal<string>('—');
  loading       = signal(true);

  ngOnInit() {
    this.rapportSvc.getMes().subscribe({
      next: (r: any[]) => {
        this.nbRapports.set(r.length);
        if (r.length) this.dernierStatut.set(r[0].statut ?? '—');
      }
    });

    this.themeSvc.getMes().subscribe({
      next: (t) => this.nbThemes.set(t.length)
    });

    this.notifSvc.getMes().subscribe({
      next: (n) => this.nbNotifs.set(n.filter(x => !x.lu).length)
    });

    this.analyseSvc.getDernier().subscribe({
      next: (a: any) => { this.dernierTaux.set(a.taux_plagiat); this.loading.set(false); },
      error: ()  => this.loading.set(false)
    });
  }

  get tauxColor(): string {
    const t = this.dernierTaux();
    if (t === null) return 'var(--text-light)';
    if (t < 20) return '#16a34a';
    if (t < 40) return '#d97706';
    return '#dc2626';
  }

  get tauxLabel(): string {
    const t = this.dernierTaux();
    if (t === null) return '—';
    if (t < 20) return 'Acceptable';
    if (t < 40) return 'Attention';
    return 'Élevé';
  }

  readonly raccourcis = [
    { path: '../theme',         label: 'Soumettre un thème',  icon: 'lightbulb', color: 'blue' },
    { path: '../rapport',       label: 'Déposer un rapport',  icon: 'upload_file', color: 'green' },
    { path: '../suivi',         label: 'Voir mes demandes',   icon: 'assignment', color: 'orange' },
    { path: '../notifications', label: 'Mes notifications',   icon: 'notifications', color: 'purple' },
  ];
}
