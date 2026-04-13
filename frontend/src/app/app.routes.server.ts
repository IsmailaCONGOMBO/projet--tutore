import { RenderMode, ServerRoute } from '@angular/ssr';

export const serverRoutes: ServerRoute[] = [
  { path: 'chef/plagiat/:id', renderMode: RenderMode.Client },
  { path: 'enseignant/plagiat/:id', renderMode: RenderMode.Client },
  { path: '**',               renderMode: RenderMode.Prerender }
];
