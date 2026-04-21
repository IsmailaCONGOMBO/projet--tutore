import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';

export interface ThemeChef {
  id: number;
  titre: string;
  description: string;
  statut: 'EN_ATTENTE' | 'VALIDE' | 'REJETE';
  statut_raw?: string;
  motif_rejet?: string;
  etudiant?: { id: number; name: string; email: string };
  created_at: string;
  updated_at?: string;
}

export interface StatsDashboard {
  en_attente: number;
  valides: number;
  rejetes: number;
  total: number;
}

@Injectable({ providedIn: 'root' })
export class ThemeChefService {
  private http = inject(HttpClient);
  private api  = 'http://localhost:8000/api';

  getStats()    { return this.http.get<StatsDashboard>(`${this.api}/chef/themes/stats`); }

  getThemes(statut?: string) {
    let params = new HttpParams();
    if (statut) params = params.set('statut', statut);
    return this.http.get<ThemeChef[]>(`${this.api}/chef/themes`, { params });
  }

  valider(id: number) {
    return this.http.post<ThemeChef>(`${this.api}/chef/themes/${id}/valider`, {});
  }

  rejeter(id: number, motif: string) {
    return this.http.post<ThemeChef>(`${this.api}/chef/themes/${id}/rejeter`, { motif });
  }

  rechercher(motCle: string) {
    const params = new HttpParams().set('motCle', motCle);
    return this.http.get<ThemeChef[]>(`${this.api}/chef/themes/recherche`, { params });
  }

  getHistorique(filtre?: 'VALIDE' | 'REJETE') {
    let params = new HttpParams();
    if (filtre) params = params.set('statut', filtre);
    return this.http.get<ThemeChef[]>(`${this.api}/chef/themes/historique`, { params });
  }
}
