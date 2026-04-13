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

@Injectable({ providedIn: 'root' })
export class NoteService {
  private http = inject(HttpClient);
  private api = 'http://localhost:8000/api/rapports';

  soumettreNote(rapportId: number, note: number, commentaire: string): Observable<any> {
    return this.http.post(`${this.api}/note/${rapportId}`, { note, commentaire });
  }

  validerAdmin(rapportId: number): Observable<any> {
    return this.http.post(`${this.api}/valider-admin/${rapportId}`, {});
  }

  rejeterAdmin(rapportId: number): Observable<any> {
    return this.http.post(`${this.api}/rejeter-admin/${rapportId}`, {});
  }

  getMaNote(): Observable<any> {
    // Legacy support for student view
    return this.http.get(`http://localhost:8000/api/notes/ma-note`);
  }

  ajouterNote(data: any): Observable<any> {
    return this.http.post(`${this.api}/note/${data.rapport_id}`, data);
  }

  getNotesEnAttente(): Observable<any[]> {
    return this.http.get<any[]>('http://localhost:8000/api/notes/en-attente');
  }
}
