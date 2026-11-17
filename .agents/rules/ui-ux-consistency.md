---
description: UI/UX consistency rules for Student Academic Planner
alwaysApply: true
---

# UI/UX Consistency Rule

For every UI task in this repository:
- Read `AGENTS.md`, `docs/UI_UX_GUIDELINES.md`, and `docs/DESIGN_SYSTEM.md`.
- Keep the approved dark charcoal + indigo/purple visual direction and Plus Jakarta Sans when available.
- Reuse the existing application shell and Blade components. Inspect existing code before creating alternatives.
- Keep sidebar, topbar, spacing, typography, buttons, cards, tables, badges, and modal patterns consistent across student, lecturer, and admin pages.
- The academic calendar is a primary dashboard feature, not a decorative widget.
- Distinguish task creation/publication date from task deadline. Never collapse them into one date.
- Make layouts responsive and accessible; provide visible focus states and readable contrast.
- Do not create fake controls, fake data, or decorative interactions. Every visible action must work or be clearly marked as unavailable.
- Do not redesign approved components or change the theme without explaining why and obtaining user approval.
- Do not alter backend/database behavior when the request is UI-only unless necessary and approved.
- Verify the page at desktop and mobile widths and report what was actually tested.
