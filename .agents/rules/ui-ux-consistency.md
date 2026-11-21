---
description: UI/UX consistency rules for Student Academic Planner
alwaysApply: true
---

# UI/UX Consistency Rule

For every UI task in this repository:

## Visual direction
- The approved visual direction is **Lavender Floating Dashboard**, matching the reference image `docs/reference/eduplex-dashboard.png` (approved by the user on 21 Nov 2026). It replaces the earlier "Light Lavender" theme and must never revert to the old dark charcoal + indigo theme.
- Core look: saturated lavender page background (`#C2A5F9`), floating off-white application frame (`#F8F8F8`, 32px radius, soft shadow), floating dark navy sidebar (`#201F31`, 24px radius), lime accent (`#DAF17C`, dark text `#1A2200`), borderless white cards (20px radius, very soft shadow), pastel icon tiles and status pills, `rounded-full` buttons/pills/tabs/avatars, Plus Jakarta Sans.
- **Match the reference closely.** After any UI work, check the result against the fidelity checklist in `docs/DESIGN_SYSTEM.md` §9 at 1440px and 390px widths.
- **Copy the style, not the content.** Never show the reference's fake content (star ratings, "Go Premium", "Download our mobile app", study-hour numbers, course names). Use the real-data mapping in `docs/DESIGN_SYSTEM.md` §7; if no real data exists, omit the widget.
- All color, radius, and shadow values come from the centralized `@theme {}` tokens in `resources/css/app.css`. Do not scatter hardcoded hex values across files.

## Shell and layout
- One application shell for all three roles (mahasiswa, dosen, admin): floating navy sidebar, header row without a background (greeting/title left; search only if a real route exists, notifications and avatar right), main content, optional right panel (300px) on dashboards.
- Reuse the existing application shell and Blade components. Inspect existing code before creating alternatives.
- Mobile: full-screen frame (no outer margin, no frame radius), sidebar as drawer, single column, calendar as agenda/list.

## Sidebar specifics
- Background `#201F31`; inactive text `#C9C8DA`; hover `#2E2C45`.
- Active item: full pill (`rounded-full`), background `#DAF17C`, text `#1A2200` (never white).
- Notification count badge: peach `#F9D3BE` / `#7A3512`, only when the count is > 0 from a real query.
- Bottom lime card only with a real per-role shortcut; otherwise omit it.

## Calendar and agenda
- The academic calendar is a primary dashboard feature and stays the largest card on the dashboard. The right-panel mini calendar is a navigator for it, not a second calendar.
- Two views: Calendar View (month/week/agenda) and Timeline View (horizontal task bars).
- **Always distinguish task publication date (`dipublikasikan_pada`) from task deadline (`deadline`).** Never collapse them into one date, never show only one of them.
- UTS and UAS must be labeled explicitly as "UTS" and "UAS", never just "Ujian", with different colors (`#76A9E8` UTS, `#5B8FD0` UAS).
- Agenda colors: Tugas `#9A78E8`, UTS `#76A9E8`, UAS `#5B8FD0`, Agenda akademik `#E78DB7`, Jadwal kuliah `#8DBE83`, Pengumuman `#E9B94F`, Terlambat `#D96C75`.
- Do not rely only on color to distinguish agenda types or to highlight chart bars (lime on white is only 1.25:1); always add labels, icons, or tooltips.

## Interaction and performance
- Interactive but lightweight: CSS-first hover/tooltip/entrance animations, minimal JavaScript (Alpine.js only if already installed, otherwise one small vanilla module), server-render first.
- Do not add animation, chart, or UI libraries without approval. Animate only `transform` and `opacity` (150-250ms; chart intro up to 450ms). Respect `prefers-reduced-motion`. No blur, glow, or infinite decorative animation.
- Charts are built from server-rendered HTML/CSS/SVG, with keyboard-focusable bars and tooltips on hover and focus.
- Report bundle size from `npm run build` after UI changes.

## Data integrity
- Make layouts responsive and accessible; provide visible focus states and readable contrast (WCAG AA minimum). Muted text is `#686980` (the old `#77788A` fails AA on white).
- Do not create fake controls, fake data, or decorative interactions. Every visible action must work or be clearly marked as unavailable (for example, hide the search field until a real search route exists, and hide "+" buttons that have no real action).
- Do not display dummy/example data that looks like real production data.
- Do not show statistics or counts without a real database query backing them. The reference's "hours" chart becomes an activity-count chart because the schema does not record study hours.
- Do not display data belonging to another role without authorization.

## Changes and approvals
- Widgets that need new queries or services (for example the weekly activity chart) require a short plan and approval before implementation. Do not change the database schema for a visual widget.
- Do not redesign approved components or change the theme beyond this reference-based direction without explaining why and obtaining user approval.
- Do not alter backend/database behavior when the request is UI-only unless necessary and approved.
- Verify the page at desktop and mobile widths and report what was actually tested.
- Read `AGENTS.md`, `docs/UI_UX_GUIDELINES.md`, and `docs/DESIGN_SYSTEM.md` before starting any UI work, and look at `docs/reference/eduplex-dashboard.png`.
