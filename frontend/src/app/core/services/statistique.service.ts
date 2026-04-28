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
  total_etudiants?: number;
  meilleure_filiere?: { nom: string; taux_moyen: number };
  rapports_valides?: number;
  rapports_rejetes?: number;
}

export interface FiliereStat {
  id: number;
  nom: string;
  total_rapports: number;
  taux_plagiat_moyen: number;
}

export interface PromotionStat {
  id: number;
  annee: number;
  libelle: string;
  total_rapports: number;
  taux_plagiat_moyen: number;
}

@Injectable({
  providedIn: 'root'
})
export class StatistiqueService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8000/api/statistiques';

  getStatistiquesGlobales(): Observable<StatistiqueGlobale> {
    return this.http.get<StatistiqueGlobale>(`${this.apiUrl}`);
  }

  getFiliereStats(): Observable<FiliereStat[]> {
    return this.http.get<FiliereStat[]>(`${this.apiUrl}/filiere`);
  }

  getPromotionStats(): Observable<PromotionStat[]> {
    return this.http.get<PromotionStat[]>(`${this.apiUrl}/promotion`);
  }

  getGlobalAdvancedStats(): Observable<StatistiqueGlobale> {
    return this.http.get<StatistiqueGlobale>(`${this.apiUrl}/global`);
  }
}
