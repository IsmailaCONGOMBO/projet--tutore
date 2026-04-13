import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { RapportService, Rapport } from '../../../core/services/rapport.service';
import { NoteService } from '../../../core/services/note.service';

@Component({
  selector: 'app-enseignant-notes',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './notes.html',
  styleUrl: './notes.css'
})
export class EnseignantNotes implements OnInit {
  private fb = inject(FormBuilder);
  private rapportService = inject(RapportService);
  private noteService = inject(NoteService);

  noteForm: FormGroup;
  rapports = signal<Rapport[]>([]);
  submitting = signal(false);
  message = signal<{ type: 'success' | 'error', text: string } | null>(null);

  constructor() {
    this.noteForm = this.fb.group({
      rapport_id: ['', Validators.required],
      note: ['', [Validators.required, Validators.min(0), Validators.max(20)]],
      commentaire: ['', [Validators.required, Validators.minLength(10)]]
    });
  }

  ngOnInit() {
    this.rapportService.getAssignes().subscribe(data => {
      // On filtre les rapports qui ne sont pas encore notés pour le formulaire
      // ou ceux dont la note a été rejetée
      this.rapports.set(data.filter((r: Rapport) => r.statut === 'ASSIGNE_ENSEIGNANT' || r.statut === 'NOTE_REJETEE_ADMIN'));
    });
  }

  onSubmit() {
    if (this.noteForm.invalid) return;

    this.submitting.set(true);
    this.noteService.ajouterNote(this.noteForm.value).subscribe({
      next: () => {
        this.message.set({ type: 'success', text: 'Note attribuée avec succès !' });
        this.noteForm.reset();
        this.submitting.set(false);
        this.ngOnInit();
      },
      error: () => {
        this.message.set({ type: 'error', text: 'Une erreur est survenue lors de la soumission.' });
        this.submitting.set(false);
      }
    });
  }
}
