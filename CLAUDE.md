# RMD Focal Zoom Point – Projektregeln

WordPress-Plugin von reichelt media.design: Fokuspunkt und Zoom am Bild (Attachment), angewendet überall, wo ein Theme das Bild beschneidet. Repo: `github.com/reicheltmediadesign/wordpress-rmd-focal-zoom-point`, Lizenz GPL-2.0-or-later, Autor Philipp Reichelt.

## Aufbau

- `rmd-focal-zoom-point.php` Bootstrap (muss PHP-7-parsebar bleiben), `includes/` PSR-4 `RMD\FocalZoomPoint\`, öffentliche Theme-API in `includes/functions.php` (`rmd_fzp_*`).
- `includes/Domain/` ist **WordPress-frei** und wird mit plain PHPUnit getestet: `FocalPoint` ist die einzige Stelle, die die Datenform `{ x, y, zoom }` festlegt (Begrenzen, Runden, Custom Properties). Jeder Schreibpfad (Mediathek-Feld, REST-Sanitizer, Block-Attribut, Theme-API) läuft durch `FocalPoint::from()`. `src/editor/data.js` `normalize()` spiegelt diese Regeln in JS – Änderungen an beiden Stellen nachziehen.
- Daten: Post-Meta `_rmd_fzp` am Attachment (Mitte ohne Zoom wird gelöscht, nicht gespeichert); Override je Verwendung im Block-Attribut `rmdFzp`.
- Ausgabe (`Render`): inline `object-position` + Custom Properties auf `<img>` über `wp_get_attachment_image_attributes`, `wp_content_img_tag` und `render_block`. Vorhandenes inline `object-position` (Cover, Medien & Text) bleibt, außer bei Override. Zoom wirkt nur innerhalb von `.rmd-fzp-frame` (`assets/css/focal-zoom.css`, Properties `scale`/`translate`, damit Theme-`transform` erhalten bleibt). Core-Bild- und Beitragsbild-Block werden automatisch in einen Rahmen gepackt.
- Editor: `src/editor/` (wp-scripts → `build/editor.js`), Panel, Override-Schalter, Canvas-Vorschau über `editor.BlockListBlock`, `window.rmdFocalZoomPoint` für Themes. Wird mit Priorität 5 geladen, damit das Attribut auch an Theme-Blöcken hängt.
- Mediathek: `Admin\MediaField` + `assets/js/media.js` (Vanilla, kein Build), speichert über das Compat-Formular.
- `docs/theme-integration.md` ist die Entwickler-Doku für Themes. Sie wird mit ausgeliefert und auf der Hilfeseite (Medien → Focal Point & Zoom) über `Domain\Markdown` gerendert. Bei neuen Filtern, Properties, Klassen oder Funktionen dort **und** in README/CHANGELOG nachziehen. Der Renderer kann nur, was in `Markdown.php` steht – keine anderen Markdown-Konstrukte in der Doku verwenden.

## Code-Regeln

- PHP 8.1+, WP 6.8+. WordPress-Coding-Standards (`phpcs.xml.dist`), Methoden snake_case, Präfix `rmd_fzp_` / Namespace `RMD\FocalZoomPoint`, Textdomain `rmd-focal-zoom-point`.
- Quellstrings Englisch, Übersetzung in `languages/rmd-focal-zoom-point-de_DE.po` (Sie-Form). `de_DE_formal` erzeugt `make-i18n.sh` daraus – nicht separat pflegen.
- Sicherheit: Meta-Schreibrechte über `edit_post` am Attachment; Ausgabe escapen; keine externen Aufrufe außer Update-Check.
- Version an vier Stellen: Plugin-Header, `RMD_FZP_VERSION`, `package.json`, `readme.txt` (Stable tag). `bash bin/check-version.sh` prüft das.

## Build und Prüfung

```powershell
npm run build          # Editor nach build/
npm run lint           # JS + CSS
composer phpcs         # PHPCS
composer test          # PHPUnit (Domain)
npm run i18n           # Übersetzungen (WP-CLI: C:\xampp\php\wp.bat, in Git Bash WP_CLI=/c/xampp/php/wp.bat setzen)
npm run zip            # dist/rmd-focal-zoom-point.zip (Git Bash: zip, composer nötig)
```

Testen in WordPress läuft ausschließlich über das Release-Zip von GitHub, das Philipp selbst in seiner WP-Installation aktualisiert. Kein Verlinken oder Kopieren des Repos in WordPress-Installationen.

## Git und Release

- Committen nur auf Anfrage, auf Feature-Branches; nie direkt auf `main`.
- Release: Versionen an allen Stellen erhöhen, `CHANGELOG.md` und `readme.txt` (Changelog) ergänzen, committen, Tag `vX.Y.Z` pushen. Der Workflow lintet, testet, baut Assets + Übersetzungen, erzeugt `rmd-focal-zoom-point.zip` sowie `update.json` und legt ein **Draft-Release** an. Beide Assets müssen am Release bleiben: installierte Sites lesen `releases/latest/download/update.json`.
- Den vom Workflow angelegten Entwurf bearbeiten, nicht neu anlegen; erst nach Test des ZIPs veröffentlichen.
- Bei jedem Release Titel `vX.Y.Z – <Kernänderung>` und Release Notes (Englisch, Markdown in Codeblock) mit ausgeben; Grundlage `git log <letzter Tag>..HEAD`.
- `gh` ist lokal nicht installiert: Workflow-Status über `https://api.github.com/repos/reicheltmediadesign/wordpress-rmd-focal-zoom-point/actions/runs`.
- Topics fürs Repo: `wordpress`, `wordpress-plugin`, `focal-point`, `image-crop`, `gutenberg`.

## README

Nutzerorientiert. Entwickler-Integration steht in `docs/theme-integration.md`, Build/Release hier.
