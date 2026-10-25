# Kago Theme

**Kago Fansub Themne** is a WordPress theme for publishing anime, episode, and movie catalogs. It includes editable metadata, custom download links, a MyAnimeList grabber, and view-based homepage rankings.

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Content types](#content-types)
- [Configuration](#configuration)
- [Popularity rankings](#popularity-rankings)
- [Development](#development)
- [License](#license)
- [AI assistance](#ai-assistance)

## Features

- Custom post types for anime, episodes, and movies.
- Anime metadata, posters, banners, cast, genres, studios, release dates, and ratings.
- Optional episode-specific image and title, with automatic title generation when no episode title is entered.
- Flexible download and stream groups: enter any quality label, host name, and URL.
- MyAnimeList metadata import through Tenrai, with Jikan as a fallback provider.
- Episode lists with episode titles, a seven-row scroll area, and a no-reload Newest/Oldest sort control.
- All-time Popular Posts and a homepage slider ranked by anime views from the last seven days.
- Optional WordPress comments, theme styling, and SEO metadata.
- Demo content importer and admin filters for catalog management.

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later
- MySQL/MariaDB supported by the installed WordPress version

## Installation

### Install as a theme

1. Download or package this repository as `kagofansub.zip`.
2. In WordPress, go to **Appearance > Themes > Add New > Upload Theme** and upload the ZIP.
3. Activate **Kago Fansub**.
4. Go to **Settings > Permalinks**, select **Post name**, and save.

Theme activation creates the default taxonomies and About, DMCA, and Contact pages, creates the Primary menu, and refreshes permalinks.

### Install for local development

Clone or copy the theme directory into `wp-content/themes/kagofansub`, then activate it from **Appearance > Themes**.

## Content types

| Type | Example URL | Main fields |
|---|---|---|
| Anime | `/anime/<slug>/` | Synopsis, poster, banner, score, release information, cast, and episodes |
| Episode | `/episode/<slug>/` | Parent anime, episode number, episode title, air date, optional image, and download links |
| Movie | `/movie/<slug>/` | Synopsis, poster, release information, and download links |

The theme provides taxonomies for genre, studio, age rating, source, release year, season, anime type, and status. Release year and season can be assigned from the release date.

## Configuration

### Posters and episode images

Use the **Anime Details** editor box to select a poster and banner from the Media Library or enter external URLs. Poster display falls back to the featured image, poster URL, and then `assets/images/poster-placeholder.webp`. An episode can use its own uploaded image instead of the parent anime poster.

### Download and stream links

On an episode or movie, use **Download / Stream Links** to add quality groups and hosts. Both names are free-form; for example, add a `4K` group with hosts such as `My Host` and their respective URLs. An empty URL produces a disabled button on the public page.

The `kago_download_qualities` and `kago_download_hosts` filters are available for sites that want to customize default values programmatically.

### MAL Grabber

Open **Anime > MAL Grabber** and search by title, MAL ID, or MAL URL. The grabber imports anime or movie details as a draft, including available genres, studios, ratings, source, and poster data. It prefers Romaji titles when available. Cast and draft episodes can be included; episode titles are imported when provided by the source. Download links remain manual.

Tenrai is the default provider, with Jikan available as a fallback. Settings include provider order, optional Tenrai server key, request timeout, cache duration, optional IPv4-only requests, and connection diagnostics. Imported images and text should only be used when you have permission to use them.

### Customizer

Go to **Appearance > Customize > Kago Fansub** to configure brand and footer text, homepage slider size, home section count, Popular Posts count, and whether comments are enabled on anime, movie, and episode pages. Comments are disabled by default. When enabled, **Recommended for You** appears before the comments section.

## Popularity rankings

- **Popular Posts** ranks anime and movies by their all-time view totals.
- The homepage slider ranks anime using view counts from the rolling last seven days.
- Views are counted on anime, movie, and episode pages. A browser cookie prevents the same page from being counted again for six hours. Bots and previews are excluded; episode views count toward the parent anime.
- View totals are automatic and are not editable in the post editor.
- Weekly tracking starts collecting data after installation. Existing all-time totals cannot be used to reconstruct views from the previous seven days. Until weekly counts exist, the homepage slider falls back to recent anime.

## Development

The theme entry point is `functions.php`. Theme features and WordPress hooks are organized under `inc/`; templates live in the repository root and `template-parts/`; styles and scripts are under `assets/`.

Useful commands and workflows depend on your local WordPress setup. No build step is required for the theme's PHP, CSS, or JavaScript files.

## License

Kago Fansub is licensed under the GNU General Public License, version 2 or later (GPL-2.0-or-later). See the license header in `style.css` and the [GNU GPL](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

## AI assistance

This project was developed with assistance from AI tools. AI-assisted suggestions and code were reviewed and adapted as part of the project; the project maintainers are responsible for the final contents of this repository.
