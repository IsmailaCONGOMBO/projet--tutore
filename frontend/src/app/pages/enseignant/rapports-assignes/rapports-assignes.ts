import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { RapportService } from '../../../core/services/rapport.service';

@Component({
  selector: 'app-enseignant-rapports',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './rapports-assignes.html',
  styleUrl: './rapports-assignes.css'
})
export class EnseignantRapportsAssignes implements OnInit {
  private rapportService = inject(RapportService);
  rapports = signal<any[]>([]);
  loading = signal(true);

  ngOnInit() {
    this.rapportService.getRapportsAssignes().subscribe({
      next: (data) => {
        this.rapports.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  download(id: number) {
    this.rapportService.telechargerRapport(id).subscribe(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `rapport_${id}.pdf`;
      a.click();
    });
  }

  getStatusClass(status: string) {
    switch (status) {
      case 'EN_ATTENTE': return 'status-pending';
      case 'ANALYSE':     return 'status-analyzing';
      case 'CORRIGE':    return 'status-corrected';
      case 'NOTE':       return 'status-graded';
      case 'ARCHIVE':    return 'status-archived';
      default:           return '';
    }
  }

  getTauxColor(taux: number): string {
    if (taux < 20) return '#16a34a';
    if (taux < 40) return '#d97706';
    return '#dc2626';
  }

  getTauxBg(taux: number): string {
    if (taux < 20) return '#f0fdf4';
    if (taux < 40) return '#fffbeb';
    return '#fef2f2';
  }
}
