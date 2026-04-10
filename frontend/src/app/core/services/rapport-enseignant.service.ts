import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface RapportEnseignant {
  id: number;
  titre: string;
  etudiant: { id: number; name: string; email: string };
  statut: 'EN_ATTENTE' | 'CORRIGE' | 'NOTE';
  fichier_url?: string;
  note?: number;
  commentaire?: string;
  created_at: string;
}

export interface StatsEnseignant {
  assignes: number;
  corriges: number;
  notes_soumises: number;
}

@Injectable({ providedIn: 'root' })
export class RapportEnseignantService {
  private http = inject(HttpClient);
  private api  = 'http://localhost:8000/api/enseignant';

  getStats()           { return this.http.get<StatsEnseignant>(`${this.api}/stats`); }
  getAssignes()        { return this.http.get<RapportEnseignant[]>(`${this.api}/rapports/assignes`); }
  getArchives()        { return this.http.get<RapportEnseignant[]>(`${this.api}/rapports/archives`); }

  telecharger(id: number) {
    return this.http.get(`${this.api}/rapports/${id}/telecharger`, {
      responseType: 'blob',
      observe: 'response'
    });
  }
}
