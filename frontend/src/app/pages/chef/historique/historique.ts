import { Component, inject, signal, OnInit, computed } from '@angular/core';
import { SlicePipe } from '@angular/common';
import { ThemeChefService, ThemeChef } from '../../../core/services/theme-chef.service';

@Component({
  selector: 'app-chef-historique',
  imports: [SlicePipe],
  templateUrl: './historique.html',
  styleUrl: './historique.css'
})
export class ChefHistorique implements OnInit {
  private svc = inject(ThemeChefService);

  tous     = signal<ThemeChef[]>([]);
  filtre   = signal<'TOUS' | 'VALIDE' | 'REJETE'>('TOUS');
  loading  = signal(true);

  filtres = computed(() => {
    const f = this.filtre();
    if (f === 'TOUS') return this.tous();
    return this.tous().filter(t => t.statut === f);
  });

  ngOnInit() {
    this.svc.getHistorique().subscribe({
      next: (t) => { this.tous.set(t); this.loading.set(false); },
      error: ()  => this.loading.set(false)
    });
  }

  setFiltre(f: 'TOUS' | 'VALIDE' | 'REJETE') { this.filtre.set(f); }

  countStatut(s: string) { return this.tous().filter(t => t.statut === s).length; }

  readonly statutLabels: Record<string, string> = {
    VALIDE: 'Validé', REJETE: 'Rejeté', EN_ATTENTE: 'En attente'
  };
}
