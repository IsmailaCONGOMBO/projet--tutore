import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Theme {
  id: number;
  etudiant_id: number;
  titre: string;
  description: string;
  statut: 'EN_ATTENTE_CHEF' | 'VALIDE_CHEF' | 'REJETE_CHEF' | 'VALIDE_ADMIN' | 'REJETE_ADMIN';
  motif_rejet?: string;
  etudiant?: {
    id: number;
    user: {
      id: number;
      name: string;
    };
  };
  chef?: {
    id: number;
    name: string;
  };
  date_validation_chef?: string;
  date_validation_admin?: string;
  created_at: string;
}

@Injectable({ providedIn: 'root' })
export class ThemeService {
  private http = inject(HttpClient);
  private api = 'http://localhost:8000/api/themes';

  // Étudiant
  soumettre(t: { titre: string; description: string }): Observable<any> {
    return this.http.post(this.api, t);
  }

  getMes(): Observable<Theme[]> {
    return this.http.get<Theme[]>(`${this.api}/mes`);
  }

  // Chef
  getThemesChef(): Observable<Theme[]> {
    return this.http.get<Theme[]>(`${this.api}/en-attente-chef`);
  }

  checkSimilarity(id: number): Observable<{ score: number; titre: string }> {
    return this.http.get<{ score: number; titre: string }>(`${this.api}/${id}/similarity`);
  }

  validerChef(id: number): Observable<any> {
    return this.http.post(`${this.api}/valider-chef/${id}`, {});
  }

  rejeterChef(id: number, motif: string): Observable<any> {
    return this.http.post(`${this.api}/rejeter-chef/${id}`, { motif });
  }

  // Admin
  getThemesAdmin(): Observable<Theme[]> {
    return this.http.get<Theme[]>(`${this.api}/en-attente-admin`);
  }

  validerAdmin(id: number): Observable<any> {
    return this.http.post(`${this.api}/valider-admin/${id}`, {});
  }

  rejeterAdmin(id: number, motif: string): Observable<any> {
    return this.http.post(`${this.api}/rejeter-admin/${id}`, { motif });
  }
}
