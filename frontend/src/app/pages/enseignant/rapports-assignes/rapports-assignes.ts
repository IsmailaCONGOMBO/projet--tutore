import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { RapportService, Rapport } from '../../../core/services/rapport.service';
import { NoteService } from '../../../core/services/note.service';

@Component({
  selector: 'app-enseignant-rapports',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './rapports-assignes.html',
  styleUrl: './rapports-assignes.css'
})
export class EnseignantRapportsAssignes implements OnInit {
  private rapportService = inject(RapportService);
  private noteService = inject(NoteService);

  rapports = signal<Rapport[]>([]);
  loading = signal(true);
  
  selectedRapport = signal<Rapport | null>(null);
  noteValue = 0;
  commentaireTxt = '';

  message = signal<{ type: 'success' | 'error', text: string } | null>(null);

  ngOnInit() {
    this.chargerRapports();
  }

  chargerRapports() {
    this.loading.set(true);
    this.rapportService.getAssignes().subscribe({
      next: (data) => {
        this.rapports.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  selectRapport(r: Rapport) {
    this.selectedRapport.set(r);
    this.noteValue = r.note || 0;
    this.commentaireTxt = r.commentaire || '';
    this.message.set(null);
  }

  envoyerNote() {
    const r = this.selectedRapport();
    if (!r) return;

    this.noteService.soumettreNote(r.id, this.noteValue, this.commentaireTxt).subscribe({
      next: () => {
        this.message.set({ type: 'success', text: 'Note et commentaire soumis avec succès.' });
        this.chargerRapports();
        this.selectedRapport.set(null);
      },
      error: (e) => {
        this.message.set({ type: 'error', text: e.error?.message || 'Erreur lors de la soumission.' });
      }
    });
  }

  download(id: number) {
    this.rapportService.download(id).subscribe(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `rapport_${id}.pdf`;
      a.click();
    });
  }

  getStatusLabel(s: string): string {
    return s.replace('_', ' ');
  }
}
