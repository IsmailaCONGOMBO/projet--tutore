import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface NotePayload {
  rapport_id: number;
  valeur: number;
  commentaire?: string;
}

export interface NoteResponse {
  id: number;
  rapport_id: number;
  valeur: number;
  commentaire?: string;
  created_at: string;
}

@Injectable({ providedIn: 'root' })
export class NoteEnseignantService {
  private http = inject(HttpClient);
  private api  = 'http://localhost:8000/api';

  soumettre(data: NotePayload) {
    return this.http.post<NoteResponse>(`${this.api}/notes`, data);
  }

  getMesNotes() {
    return this.http.get<NoteResponse[]>(`${this.api}/enseignant/notes`);
  }
}
