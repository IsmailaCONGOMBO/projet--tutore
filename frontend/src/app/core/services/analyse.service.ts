import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface PassageSuspect {
  texte: string;
  source: string;
  similarite: number;
}

export interface AnalysePlagiat {
  rapport_id: number;
  taux_plagiat: number;
  passages_suspects: PassageSuspect[];
  analyse_date?: string;
}

@Injectable({ providedIn: 'root' })
export class AnalyseService {
  private http = inject(HttpClient);
  private api = 'http://localhost:8000/api';

  getResultat(rapportId: number) {
    return this.http.get<AnalysePlagiat>(`${this.api}/rapports/${rapportId}/analyse-resultat`);
  }

  getDernier() {
    return this.http.get<AnalysePlagiat>(`${this.api}/analyses/derniere`);
  }
}
