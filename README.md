# Uve Mailrelay Newsletter (self-hosted)

Plugin WordPress para suscripción a Mailrelay con Cloudflare Turnstile, RGPD y logs.

## Instalación (en un sitio)
1. Descarga el ZIP de una Release (GitHub Releases).
2. WordPress → Plugins → Añadir nuevo → Subir plugin → Activar.
3. Ajustes → Uve Mailrelay Newsletter → configura API/Turnstile.

## Self-hosted updates (sin wp.org)
- Este repo genera un ZIP “instalable” en cada Release.
- Puedes actualizar manualmente subiendo el ZIP.
- (Opcional) puedes integrar un updater que mire GitHub Releases, pero no es obligatorio.

## GitFlow (ramas)
- `main`: producción (solo merges desde release/* o hotfix/*).
- `develop`: integración.
- `feature/*`: trabajo.
- `release/vX.Y.Z`: preparación de versión.
- `hotfix/vX.Y.Z`: arreglos urgentes sobre main.

## Cómo sacar una release
1. Trabaja en `feature/*`, PR a `develop`.
2. Cuando `develop` esté listo: ejecuta el workflow **Release (GitFlow)** desde Actions.
3. El workflow:
   - crea `release/vX.Y.Z` desde `develop`
   - bump de versiones
   - checks (lint/phpcs/phpstan)
   - PR a `main` y PR de back-merge a `develop`
   - merge a `main`, tag `vX.Y.Z`
   - genera ZIP instalable y GitHub Release

### Requisitos del repo para que el Action pueda “pasar a main”
- Settings → Actions → Workflow permissions: **Read and write**
- (Si hay branch protection en main/develop) permitir merges por GitHub Actions o permitir `gh pr merge` (dependiendo de tu política).
