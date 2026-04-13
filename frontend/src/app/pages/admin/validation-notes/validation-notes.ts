import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RapportService, Rapport } from '../../../core/services/rapport.service';
import { NoteService } from '../../../core/services/note.service';

@Component({
  selector: 'app-validation-notes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './validation-notes.html',
  styleUrl: './validation-notes.css'
})
export class AdminValidationNotes implements OnInit {
  private rapportService = inject(RapportService);
  private noteService = inject(NoteService);
  
  rapports = signal<Rapport[]>([]);
  loading = signal(true);
  error = signal<string | null>(null);
  
  selectedRapport = signal<Rapport | null>(null);
  processingAction = signal(false);

  ngOnInit() {
    this.chargerRapports();
  }

  chargerRapports() {
    this.loading.set(true);
    this.rapportService.getListTous().subscribe({
      next: (data) => {
        // Uniquement les rapports où une note a été soumise mais pas encore validée admin
        this.rapports.set(data.filter(r => r.statut === 'NOTE_SOUMISE'));
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Erreur lors du chargement des corrections.');
        this.loading.set(false);
      }
    });
  }

  valider(r: Rapport) {
    this.processingAction.set(true);
    this.noteService.validerAdmin(r.id).subscribe({
      next: () => {
        this.chargerRapports();
        this.selectedRapport.set(null);
        this.processingAction.set(false);
      },
      error: () => this.processingAction.set(false)
    });
  }

  rejeter(r: Rapport) {
    this.processingAction.set(true);
    this.noteService.rejeterAdmin(r.id).subscribe({
      next: () => {
        this.chargerRapports();
        this.selectedRapport.set(null);
        this.processingAction.set(false);
      },
      error: () => this.processingAction.set(false)
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
}
