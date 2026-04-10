import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { SlicePipe } from '@angular/common';
import { RapportChefService, RapportChef } from '../../../core/services/rapport-chef.service';

@Component({
  selector: 'app-chef-rapports',
  imports: [RouterLink, SlicePipe],
  templateUrl: './rapports.html',
  styleUrl: './rapports.css'
})
export class ChefRapports implements OnInit {
  private svc = inject(RapportChefService);

  rapports = signal<RapportChef[]>([]);
  loading  = signal(true);
  error    = signal('');
  filtre   = signal<'TOUS' | 'EN_ATTENTE' | 'ACCEPTE' | 'REJETE'>('TOUS');

  ngOnInit() {
    this.svc.getTous().subscribe({
      next: (r) => { this.rapports.set(r); this.loading.set(false); },
      error: ()  => { this.error.set('Erreur lors du chargement des rapports.'); this.loading.set(false); }
    });
  }

  get rapportsFiltres(): RapportChef[] {
    const f = this.filtre();
    if (f === 'TOUS') return this.rapports();
    return this.rapports().filter(r => r.statut === f);
  }

  countStatut(s: string) { return this.rapports().filter(r => r.statut === s).length; }

  tauxColor(taux: number | null): string {
    if (taux === null) return 'var(--text-light)';
    if (taux < 20) return '#16a34a';
    if (taux < 40) return '#d97706';
    return '#dc2626';
  }

  tauxBg(taux: number | null): string {
    if (taux === null) return 'var(--off-white-2)';
    if (taux < 20) return '#16a34a';
    if (taux < 40) return '#d97706';
    return '#dc2626';
  }
}
