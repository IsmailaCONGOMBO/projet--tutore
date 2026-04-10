import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RapportService } from '../../../core/services/rapport.service';

@Component({
  selector: 'app-enseignant-archives',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './archives.html',
  styleUrl: '../rapports-assignes/rapports-assignes.css' // Réutilisation des styles de la liste
})
export class EnseignantArchives implements OnInit {
  private rapportService = inject(RapportService);
  archives = signal<any[]>([]);
  loading = signal(true);

  ngOnInit() {
    this.rapportService.getRapportsArchives().subscribe({
      next: (data) => {
        this.archives.set(data);
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
      a.download = `archive_${id}.pdf`;
      a.click();
    });
  }
}
