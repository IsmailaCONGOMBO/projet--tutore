import { Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../core/services/auth.service';
import { StatistiqueService, StatistiqueGlobale } from '../../core/services/statistique.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard implements OnInit {
  private auth = inject(AuthService);
  private statistiqueService = inject(StatistiqueService);
  
  user = this.isBrowser ? JSON.parse(localStorage.getItem('user') || '{}') : {};
  private get isBrowser() { return typeof window !== 'undefined'; }

  stats = signal<StatistiqueGlobale | null>(null);
  loading = signal(true);
  today = new Date();

  ngOnInit() {
    this.statistiqueService.getStatistiquesGlobales().subscribe({
      next: (data) => {
        this.stats.set(data);
        this.loading.set(false);
      },
      error: (err) => {
        console.error('Erreur chargement stats dashboard', err);
        this.loading.set(false);
      }
    });
  }

  getProgress(count: number): number {
    if (!this.stats()?.rapports_par_filiere) return 0;
    const max = Math.max(...this.stats()!.rapports_par_filiere.map(f => f.count));
    return max > 0 ? (count / max) * 100 : 0;
  }

  logout() {
    this.auth.logout();
  }
}
