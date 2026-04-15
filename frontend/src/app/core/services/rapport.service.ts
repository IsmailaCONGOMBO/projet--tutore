import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Rapport {
  id: number;
  titre: string;
  statut: string;
  taux_plagiat?: number;
  seuil_plagiat?: number;
  note?: number;
  commentaire?: string;
  etudiant_id?: number;
  enseignant_id?: number;
  theme_id?: number;
  etudiant?: { user: { id: number, name: string } };
  enseignant?: { id: number, user: { id: number, name: string } };
  created_at: string;
  date_analyse?: string;
  date_correction?: string;
  date_validation_admin?: string;
  date_validation_finale?: string;
}

@Injectable({ providedIn: 'root' })
export class RapportService {
  private http = inject(HttpClient);
  private api = 'http://localhost:8000/api/rapports';

  // Étudiant
  testerRapport(formData: FormData): Observable<any> {
    return this.http.post(`${this.api}/tester`, formData);
  }

  soumettreRapport(formData: FormData): Observable<any> {
    return this.http.post(this.api, formData);
  }

  getMes(): Observable<Rapport[]> {
    return this.http.get<Rapport[]>(this.api);
  }

  // Chef de Département
  getListTous(): Observable<Rapport[]> {
    return this.http.get<Rapport[]>(`${this.api}/tous`);
  }

  analyserRapport(id: number): Observable<any> {
    return this.http.post(`${this.api}/analyse/${id}`, {});
  }

  affecterEnseignant(id: number, enseignantId: number): Observable<any> {
    return this.http.post(`${this.api}/affecter/${id}`, { enseignant_id: enseignantId });
  }

  decisionFinale(id: number, decision: 'VALIDE_FINAL' | 'REJETE_FINAL'): Observable<any> {
    return this.http.post(`${this.api}/decision-finale/${id}`, { decision });
  }

  // Enseignant
  getAssignes(): Observable<Rapport[]> {
    return this.http.get<Rapport[]>(`${this.api}/assignes`);
  }

  // Legacy Aliases for compatibility
  getRapportsAssignes(): Observable<Rapport[]> { return this.getAssignes(); }
  getRapportsArchives(): Observable<Rapport[]> { return this.getListTous(); }
  getTousLesRapports(): Observable<Rapport[]> { return this.getListTous(); }
  telechargerRapport(id: number): Observable<Blob> { return this.download(id); }

  // Utils
  download(id: number): Observable<Blob> {
    return this.http.get(`${this.api}/${id}/download`, { responseType: 'blob' });
  }
}
