import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { RapportService } from '../../../core/services/rapport.service';
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
  rapports = signal<any[]>([]);
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
    this.rapportService.getRapportsAssignes().subscribe(data => {
      // On filtre les rapports qui ne sont pas encore notés pour le formulaire
      this.rapports.set(data.filter(r => r.statut !== 'NOTÉ'));
    });
  }

  onSubmit() {
    if (this.noteForm.invalid) return;

    this.submitting.set(true);
    this.noteService.ajouterNote(this.noteForm.value).subscribe({
      next: (res) => {
        this.message.set({ type: 'success', text: 'Note attribuée avec succès !' });
        this.noteForm.reset();
        this.submitting.set(false);
        // Rafraîchir la liste des rapports disponibles
        this.ngOnInit();
      },
      error: (err) => {
        this.message.set({ type: 'error', text: 'Une erreur est survenue lors de la soumission.' });
        this.submitting.set(false);
      }
    });
  }
}
