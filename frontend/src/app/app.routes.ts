import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { adminGuard } from './core/guards/admin.guard';
import { chefGuard }  from './core/guards/chef.guard';

export const routes: Routes = [
  { path: '', loadComponent: () => import('./pages/home/home').then(m => m.Home) },
  { path: 'login', loadComponent: () => import('./pages/login/login').then(m => m.Login) },

  // Espace Admin
  { path: 'dashboard',    loadComponent: () => import('./pages/dashboard/dashboard').then(m => m.Dashboard),          canActivate: [adminGuard] },
  { path: 'utilisateurs', loadComponent: () => import('./pages/utilisateurs/utilisateurs').then(m => m.Utilisateurs),  canActivate: [adminGuard] },

  // Espace Étudiant
  {
    path: 'etudiant',
    loadComponent: () => import('./pages/etudiant/layout/layout').then(m => m.EtudiantLayout),
    canActivate: [authGuard],
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      { path: 'dashboard',     loadComponent: () => import('./pages/etudiant/dashboard/dashboard').then(m => m.EtudiantDashboard) },
      { path: 'theme',         loadComponent: () => import('./pages/etudiant/theme/theme').then(m => m.EtudiantTheme) },
      { path: 'rapport',       loadComponent: () => import('./pages/etudiant/rapport/rapport').then(m => m.EtudiantRapport) },
      { path: 'suivi',         loadComponent: () => import('./pages/etudiant/suivi/suivi').then(m => m.EtudiantSuivi) },
      { path: 'plagiat',       loadComponent: () => import('./pages/etudiant/plagiat/plagiat').then(m => m.EtudiantPlagiat) },
      { path: 'note',          loadComponent: () => import('./pages/etudiant/note/note').then(m => m.EtudiantNote) },
      { path: 'notifications', loadComponent: () => import('./pages/etudiant/notifications/notifications').then(m => m.EtudiantNotifications) },
    ]
  },

  // Espace Chef de Département
  {
    path: 'chef',
    loadComponent: () => import('./pages/chef/layout/layout').then(m => m.ChefLayout),
    canActivate: [chefGuard],
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      { path: 'dashboard',  loadComponent: () => import('./pages/chef/dashboard/dashboard').then(m => m.ChefDashboard) },
      { path: 'themes',     loadComponent: () => import('./pages/chef/themes/themes').then(m => m.ChefThemes) },
      { path: 'unicite',    loadComponent: () => import('./pages/chef/unicite/unicite').then(m => m.ChefUnicite) },
      { path: 'rapports',   loadComponent: () => import('./pages/chef/rapports/rapports').then(m => m.ChefRapports) },
      { path: 'plagiat/:id',loadComponent: () => import('./pages/chef/plagiat/plagiat').then(m => m.ChefPlagiat) },
      { path: 'historique', loadComponent: () => import('./pages/chef/historique/historique').then(m => m.ChefHistorique) },
    ]
  },

  // Espace Enseignant
  {
    path: 'enseignant',
    loadComponent: () => import('./pages/enseignant/layout/layout').then(m => m.EnseignantLayout),
    canActivate: [authGuard],
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      { path: 'dashboard', loadComponent: () => import('./pages/enseignant/dashboard/dashboard').then(m => m.EnseignantDashboard) },
      { path: 'rapports',  loadComponent: () => import('./pages/enseignant/rapports-assignes/rapports-assignes').then(m => m.EnseignantRapportsAssignes) },
      { path: 'notes',     loadComponent: () => import('./pages/enseignant/notes/notes').then(m => m.EnseignantNotes) },
      { path: 'plagiat/:id', loadComponent: () => import('./pages/enseignant/plagiat/plagiat').then(m => m.EnseignantPlagiat) },
      { path: 'archives',  loadComponent: () => import('./pages/enseignant/archives/archives').then(m => m.EnseignantArchives) },
    ]
  },

  { path: '**', redirectTo: '' }
];
