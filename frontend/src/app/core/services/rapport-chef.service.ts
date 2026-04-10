import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface RapportChef {
  id: number;
  titre: string;
  etudiant: { id: number; name: string; email: string };
  statut: 'EN_ATTENTE' | 'ACCEPTE' | 'REJETE';
  taux_plagiat: number | null;
  created_at: string;
}

export interface DecisionRapport {
  type: 'ACCEPTE' | 'REJETE';
  motif?: string;
}

@Injectable({ providedIn: 'root' })
export class RapportChefService {
  private http = inject(HttpClient);
  private api  = 'http://localhost:8000/api/chef';

  getTous()                                  { return this.http.get<RapportChef[]>(`${this.api}/rapports`); }
  getAnalyse(id: number)                     { return this.http.get<any>(`${this.api}/rapports/${id}/analyse`); }
  decision(id: number, d: DecisionRapport)   { return this.http.post<RapportChef>(`${this.api}/rapports/${id}/decision`, d); }
}
