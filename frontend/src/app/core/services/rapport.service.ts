import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Rapport {
  id: number;
  titre: string;
  etudiant?: string;
  statut: string;
  taux_plagiat?: number | null;
  created_at: string;
  file_url?: string;
}

@Injectable({
  providedIn: 'root'
})
export class RapportService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8000/api/rapports';

  // Pour l'Enseignant
  getRapportsAssignes(): Observable<Rapport[]> {
    return this.http.get<Rapport[]>(`${this.apiUrl}/assignes`);
  }

  getRapportsArchives(): Observable<Rapport[]> {
    return this.http.get<Rapport[]>(`${this.apiUrl}/archives`);
  }

  telechargerRapport(id: number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/${id}/download`, { responseType: 'blob' });
  }

  // Pour l'Étudiant
  getMes(): Observable<Rapport[]> {
    return this.http.get<Rapport[]>(this.apiUrl);
  }

  deposer(formData: FormData): Observable<any> {
    return this.http.post(this.apiUrl, formData);
  }
}
