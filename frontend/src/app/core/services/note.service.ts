import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Note {
  id: number;
  rapport_id: number;
  valeur: number;
  commentaire: string;
  enseignant?: string;
  date_attribution?: string;
  created_at?: string;
}

@Injectable({
  providedIn: 'root'
})
export class NoteService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8000/api/notes';

  // Pour l'Enseignant
  ajouterNote(data: { rapport_id: number; note: number; commentaire: string }): Observable<any> {
    return this.http.post(this.apiUrl, data);
  }

  // Pour l'Étudiant
  getMaNote(): Observable<Note> {
    return this.http.get<Note>(`${this.apiUrl}/ma-note`);
  }
}
