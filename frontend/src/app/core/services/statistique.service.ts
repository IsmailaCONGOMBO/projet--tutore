import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface StatistiqueGlobale {
  total_rapports: number;
  taux_plagiat_moyen: number;
  rapports_par_filiere: Array<{
    filiere: string;
    count: number;
    taux_plagiat: number;
  }>;
  notes_en_attente: number;
  notes_validees: number;
  notes_rejetees: number;
}

export interface EvolutionData {
  mois: string;
  rapports: number;
  plagiat: number;
}

@Injectable({
  providedIn: 'root'
})
export class StatistiqueService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8000/api';

  getStatistiquesGlobales(): Observable<StatistiqueGlobale> {
    return this.http.get<StatistiqueGlobale>(`${this.apiUrl}/statistiques`);
  }

  getEvolutionRapports(): Observable<EvolutionData[]> {
    return this.http.get<EvolutionData[]>(`${this.apiUrl}/statistiques/evolution`);
  }
}
