---
description: UI/UX consistency rules for Student Academic Planner
alwaysApply: true
---

# UI/UX Consistency Rule

For every UI task in this repository:

## Visual direction
- The approved visual direction is **Light Lavender Academic Dashboard** — lavender page background (`#F0E9FF`), white/off-white application surface (`#FBFBFA`), dark navy sidebar (`#242238`), lime active menu accent (`#D8F36A`), white cards, and Plus Jakarta Sans typography.
- Do NOT use or restore the old dark charcoal + indigo theme. That direction has been replaced.
- All color values must come from the centralized `@theme {}` tokens in `resources/css/app.css`. Do not scatter hardcoded hex values across files.

## Shell and layout
- Use one application shell for all three roles (mahasiswa, dosen, admin): dark navy sidebar on the left, topbar at the top, main content area (white/off-white), optional right panel for dashboard notifications.
- Reuse the existing application shell and Blade components. Inspect existing code before creating alternatives.
- Keep sidebar, topbar, spacing, typography, buttons, cards, tables, badges, and modal patterns consistent across student, lecturer, and admin pages.

## Sidebar specifics
- Sidebar background: `#242238`. Menu hover: `#34314B`.
- Active menu item: background `#D8F36A` (lime), text `#1A2200` (dark, NOT white — lime is a light color).
- Inactive menu items: white/off-white text on navy background.

## Calendar and agenda
- The academic calendar is a primary dashboard feature, not a decorative widget. It must occupy a dominant visual area on the dashboard.
- The calendar has two complementary views: Calendar View (monthly/weekly/agenda) and Timeline View (horizontal task bars).
- **Always distinguish task publication date (`dipublikasikan_pada`) from task deadline (`deadline`).** Never collapse them into one date, never show only one of them.
- UTS (Ujian Tengah Semester) and UAS (Ujian Akhir Semester) must be labeled explicitly as "UTS" and "UAS" — never just "Ujian". They use different colors (`#76A9E8` for UTS, `#5B8FD0` for UAS).
- Agenda color scheme: Tugas `#9A78E8`, UTS `#76A9E8`, UAS `#5B8FD0`, Agenda akademik `#E78DB7`, Jadwal kuliah `#8DBE83`, Pengumuman `#E9B94F`, Terlambat `#D96C75`.
- Do not rely only on color to distinguish agenda types — include labels, icons, or status text.

## Data integrity
- Make layouts responsive and accessible; provide visible focus states and readable contrast (WCAG AA minimum).
- Do not create fake controls, fake data, or decorative interactions. Every visible action must work or be clearly marked as unavailable.
- Do not display dummy/example data that looks like real production data.
- Do not show statistics or counts without a real database query backing them.
- Do not display data belonging to another role without authorization.

## Changes and approvals
- Do not redesign approved components or change the theme without explaining why and obtaining user approval.
- Do not alter backend/database behavior when the request is UI-only unless necessary and approved.
- Verify the page at desktop and mobile widths and report what was actually tested.
- Read `AGENTS.md`, `docs/UI_UX_GUIDELINES.md`, and `docs/DESIGN_SYSTEM.md` before starting any UI work.
