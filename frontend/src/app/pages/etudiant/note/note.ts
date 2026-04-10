import { Component, inject, signal, OnInit } from '@angular/core';
import { SlicePipe } from '@angular/common';
import { NoteService, Note } from '../../../core/services/note.service';

@Component({
  selector: 'app-note',
  imports: [SlicePipe],
  templateUrl: './note.html',
  styleUrl: './note.css'
})
export class EtudiantNote implements OnInit {
  private svc = inject(NoteService);
  note    = signal<Note | null>(null);
  loading = signal(true);
  error   = signal('');

  ngOnInit() {
    this.svc.getMaNote().subscribe({
      next: (n: Note) => { this.note.set(n); this.loading.set(false); },
      error: ()  => { this.error.set('Note non disponible.'); this.loading.set(false); }
    });
  }

  get noteColor(): string {
    const v = this.note()?.valeur ?? 0;
    if (v >= 14) return '#16a34a';
    if (v >= 10) return '#d97706';
    return '#dc2626';
  }

  get mention(): string {
    const v = this.note()?.valeur ?? 0;
    if (v >= 16) return 'Très Bien';
    if (v >= 14) return 'Bien';
    if (v >= 12) return 'Assez Bien';
    if (v >= 10) return 'Passable';
    return 'Insuffisant';
  }
}
