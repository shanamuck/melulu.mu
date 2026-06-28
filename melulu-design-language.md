# Melulu — Design Language

> Dark-first music streaming UI. Deep purple cosmos aesthetic — immersive, data-rich, visually warm despite the darkness.

---

## 1. Design Philosophy

| Principle | Description |
|---|---|
| **Dark-first** | All surfaces are dark. No light-mode variant implied. |
| **Purple as identity** | Violet/purple is the single brand hue used for all interactive, active, and highlight states. |
| **Layered depth** | Three elevation levels via background color alone — no harsh borders or shadows. |
| **Data-dense but breathable** | Tables, stats, and cards coexist with generous padding. |
| **Glow as feedback** | Active/hover states use purple ambient glow, not outlines. |

---

## 2. Color System

### Backgrounds (layered elevation)

| Role | Name | Hex |
|---|---|---|
| App background | `bg-base` | `#0D0B1A` |
| Sidebar / nav | `bg-surface` | `#110E22` |
| Cards / panels | `bg-elevated` | `#1A1630` |
| Input / search bar | `bg-input` | `#211D38` |
| Hover state | `bg-hover` | `#241F3D` |

### Accent (Purple)

| Role | Name | Hex |
|---|---|---|
| Primary CTA, active nav, progress | `accent-primary` | `#7C3AED` |
| Buttons, highlights, rings | `accent-bright` | `#9333EA` |
| Glow / ambient | `accent-glow` | `rgba(124, 58, 237, 0.35)` |
| Gradient start | `accent-grad-from` | `#6D28D9` |
| Gradient end | `accent-grad-to` | `#A855F7` |

### Text

| Role | Name | Hex |
|---|---|---|
| Primary (headings, labels) | `text-primary` | `#FFFFFF` |
| Secondary (metadata, subtitles) | `text-secondary` | `#9B8EC4` |
| Muted (timestamps, counts) | `text-muted` | `#5E5480` |
| Disabled | `text-disabled` | `#3D3560` |

### Semantic Colors

| Role | Name | Hex |
|---|---|---|
| Success / Available Offline | `status-green` | `#22C55E` |
| Info / Syncing | `status-blue` | `#60A5FA` |
| Warning / Missing | `status-red` | `#F87171` |
| Like / Heart | `status-heart` | `#EF4444` |
| Streak / Fire | `status-orange` | `#F97316` |

---

## 3. Typography

**Font Stack:** `Inter`, `DM Sans`, or system-ui — clean geometric sans-serif.

| Scale | Size | Weight | Use |
|---|---|---|---|
| `display` | 28–32px | 700 | Page titles, stat numbers |
| `heading` | 18–22px | 600 | Section titles, song titles |
| `subheading` | 14–16px | 500 | Artist names, card labels |
| `body` | 13–14px | 400 | Descriptions, table rows |
| `caption` | 11–12px | 400 | Timestamps, counts, metadata |
| `label` | 11px | 500 | Column headers, tag text |

Letter spacing: tight on headings (`-0.02em`), normal on body.
Line height: `1.4` body, `1.2` headings.

---

## 4. Spacing & Layout

| Token | Value | Use |
|---|---|---|
| `space-xs` | 4px | Icon gap, tight inline |
| `space-sm` | 8px | Row padding, list gaps |
| `space-md` | 16px | Card internal padding |
| `space-lg` | 24px | Section gaps |
| `space-xl` | 32px | Page section margins |
| `space-2xl` | 48px | Major layout blocks |

**Layout structure:**
- Sidebar: fixed `200px` wide
- Content: fluid remainder
- Player bar: fixed `64px` bottom
- Top nav: fixed `56px` top
- Content max-width: `none` (full bleed)

---

## 5. Border Radius

| Token | Value | Use |
|---|---|---|
| `radius-sm` | 6px | Tags, badges, input |
| `radius-md` | 10px | Cards, panels |
| `radius-lg` | 16px | Playlist covers, genre tiles |
| `radius-xl` | 20px | Album art |
| `radius-full` | 9999px | Avatars, pill buttons, progress bars |

---

## 6. Components

### Navigation Sidebar
- Width: `200px`, `bg-surface`
- Active item: `bg-elevated` + left `3px` border in `accent-primary` + `text-primary`
- Inactive: `text-secondary`, no background
- Logo: purple icon + wordmark, top-left

### Search Bar
- Background: `bg-input`
- Radius: `radius-full`
- Icon: left-aligned, `text-muted`
- No border at rest; subtle `accent-glow` ring on focus

### Cards
- Background: `bg-elevated`
- Radius: `radius-md`
- Padding: `space-md`
- No border — separation via background contrast only

### Buttons
| Variant | Style |
|---|---|
| Primary | `bg: accent-primary`, white text, `radius-full`, `px-20 py-8` |
| Secondary | `bg: bg-elevated`, `text-secondary`, `radius-full` |
| Icon-only | Circular, `bg-elevated` or transparent |

### Progress / Seek Bar
- Track: `bg-elevated` or `bg-hover`, height `3-4px`, `radius-full`
- Fill: `accent-primary` → `accent-bright` gradient
- Thumb: white circle, `6-8px` diameter, appears on hover

### Tables (Song Lists)
- No outer border
- Row hover: `bg-hover`
- Active row: subtle left accent or text brightening
- Columns: `#`, Thumbnail, Song, Artist, Album, Duration, Actions
- Font: `caption`/`body` mix

### Tags / Mood Chips
- Small pills, `bg-elevated`, `radius-full`
- Emoji prefix + label text
- Selected: `accent-primary` background

### Stat Cards (Dashboard)
- Icon circle: colored per category (purple, teal, green, pink)
- Large number: `display` weight
- Sub-label: `text-muted caption`
- Trend indicator: `+x%` in green

### Donut / Ring Chart (Mood Ring)
- Thick ring, segments in purple family + muted tones
- Center: percentage + label

### Waveform Visualizer
- Thin vertical bars, purple fill for played portion, muted for unplayed
- Inline in song page header

---

## 7. Iconography

- Style: outline / stroke icons (not filled), `20–22px`
- Color: `text-secondary` default, `text-primary` on active
- Stroke width: `1.5px`

---

## 8. Imagery

- Album art: square, `radius-lg` or `radius-xl`
- Playlist covers: 4-grid thumbnail mosaic
- Map: dark choropleth with purple intensity scale
- Subtle purple-tinted overlays on imagery

---

## 9. Motion Guidelines

- Transitions: `150–200ms ease-out` for hover/active states
- Page transitions: fade or slide-up `250ms`
- Progress bar: smooth linear animation
- Glow pulse: `@keyframes` ambient on playing indicator
- Avoid heavy animations — immersion over showiness
