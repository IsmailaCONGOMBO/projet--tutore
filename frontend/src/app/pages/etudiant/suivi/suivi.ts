import { Component, inject, signal, OnInit } from '@angular/core';
import { SlicePipe } from '@angular/common';
import { ThemeService, Theme } from '../../../core/services/theme.service';
import { RapportService, Rapport } from '../../../core/services/rapport.service';

@Component({
  selector: 'app-suivi',
  imports: [SlicePipe],
  templateUrl: './suivi.html',
  styleUrl: './suivi.css'
})
export class EtudiantSuivi implements OnInit {
  private themeSvc   = inject(ThemeService);
  private rapportSvc = inject(RapportService);

  themes   = signal<Theme[]>([]);
  rapports = signal<Rapport[]>([]);
  loading  = signal(true);

  ngOnInit() {
    this.themeSvc.getMes().subscribe({ next: t => this.themes.set(t) });
    this.rapportSvc.getMes().subscribe({
      next: (r: Rapport[]) => { this.rapports.set(r); this.loading.set(false); },
      error: () => this.loading.set(false)
    });
  }

  readonly statutLabels: Record<string, string> = {
    EN_ATTENTE: 'En attente', 
    VALIDE: 'Validé', 
    REJETE: 'Rejeté',
    CORRIGÉ: 'Corrigé',
    NOTÉ: 'Noté',
    ARCHIVÉ: 'Archivé'
  };
}
