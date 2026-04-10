import { Component, inject, signal, OnInit } from '@angular/core';
import { SlicePipe } from '@angular/common';
import { AnalyseService, AnalysePlagiat } from '../../../core/services/analyse.service';

@Component({
  selector: 'app-plagiat',
  imports: [SlicePipe],
  templateUrl: './plagiat.html',
  styleUrl: './plagiat.css'
})
export class EtudiantPlagiat implements OnInit {
  private svc = inject(AnalyseService);

  analyse = signal<AnalysePlagiat | null>(null);
  loading = signal(true);
  error   = signal('');

  ngOnInit() {
    this.svc.getDernier().subscribe({
      next: (a: AnalysePlagiat) => { this.analyse.set(a); this.loading.set(false); },
      error: ()  => { this.error.set('Aucune analyse disponible.'); this.loading.set(false); }
    });
  }

  get taux(): number { return this.analyse()?.taux_plagiat ?? 0; }

  get tauxColor(): string {
    if (this.taux < 20) return '#16a34a';
    if (this.taux < 40) return '#d97706';
    return '#dc2626';
  }

  get tauxLabel(): string {
    if (this.taux < 20) return 'Acceptable';
    if (this.taux < 40) return 'Attention requise';
    return 'Taux élevé';
  }

  get tauxBg(): string {
    if (this.taux < 20) return '#f0fdf4';
    if (this.taux < 40) return '#fffbeb';
    return '#fef2f2';
  }
}
