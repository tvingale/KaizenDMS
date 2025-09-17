# 🌐 Kaizen UI/UX Guidelines

This document defines the **UI and UX principles** for all Kaizen enterprise applications.  
It is module-agnostic — applicable to QMS, HRMS, CRM, or any future system.

---

## 1. Overall Experience
- Design must feel **simple, clean, and professional**.
- Prioritize **clarity and efficiency** over decoration.
- Minimize scrolling and cognitive load.

---

## 2. Color & Branding
- **Core palette:** mild red, grey, black, white.
- **Semantic accents:**
  - Green = success / completed
  - Yellow/Orange = warning / in progress
  - Red = error / escalated / failed
  - Grey = pending / draft / expired
- Keep shades **soothing and pleasing**, not harsh.
- Default branding = Kaizen; allow **white-label theming** (logo, color tokens, font).

---

## 3. Typography
- **Primary font:** Segoe UI.
- **Base size:** ~16px body.
- **Hierarchy:** bold + size + spacing for emphasis, color only for urgency.
- Keep readability first.

---

## 4. Layout & Navigation
- **Hybrid navigation:**
  - **Top bar** = global, consistent (logo, search, notifications, user profile, site/line selector).
  - **Left sidebar** = module-specific, collapsible.
- **Balanced density**: show enough information without clutter.
- **Dashboards as landing pages**: bird’s-eye view, role-specific (visual KPIs for managers, tables for executives).

---

## 5. Tables & Lists
- **Row actions** for single-record operations.
- **Bulk actions** via selection checkboxes and toolbar (shown only when rows selected).
- Export options (CSV/PDF) standardized.

---

## 6. Forms & Data Entry
- Use **multi-step wizards** with progress indicators.
- Allow minimal scrolling inside steps, but avoid long single-page forms.
- **Hybrid validation:**
  - Instant for basic checks (required, format).
  - Full validation on submit for detailed business rules.

---

## 7. Actions & Feedback
- **Critical/irreversible actions** → modal confirmations.
- **Permanent but reversible** → inline confirmations.
- **Light/reversible** → toast notifications.
- **Success feedback**:
  - Toasts for routine actions.
  - Inline banners for critical approvals/changes.

---

## 8. Visual Hierarchy
- Rely on **typography & spacing** first.
- Reserve **color accents** only for urgent/functional highlights.

---

## 9. Status Indicators
- Use **colored badges with white text**:
  - Green = Completed/Approved/Pass
  - Yellow/Orange = In Progress/Warning
  - Red = Failed/Escalated/Rejected
  - Grey = Pending/Draft/Expired

---

## 10. Empty States
- **Default**: minimal text (“No records found”).
- **Guided states**: icon + short guidance only if user action is expected.
- Illustrations allowed only if they add clear value.

---

## 11. Responsiveness
- **Responsive balance:**
  - Desktop = primary environment.
  - Mobile = fully usable for critical flows (approvals, checklists, updates).
- **Mobile adaptation:**
  - Tables → stacked cards if readability is better; otherwise horizontal scroll.
  - Forms → single-column, touch-friendly.

---

## 12. Filters & Search
- **Hidden behind “Filter” button/panel** to save space.
- Filters should remember last-used values per session/role.

---

## 13. Notifications
- **Global bell icon (top bar)** for all alerts.
- **Inline banners** only for critical/time-sensitive module alerts.

---

## 14. Onboarding & Help
- **Inline contextual help** with hover tooltips/info icons.
- **Guided tutorials/docs** available via separate help link.

---

## 15. Consistency vs Flexibility
- **Standardized core components**: navigation, forms, tables, alerts, badges.
- **Module flexibility** allowed only when demanded by data type (e.g., calendar view for tasks, kanban for workflows).

---

## 16. Buttons & Actions
- **Primary button** = brand red, filled (main action).
- **Secondary button** = neutral outline (supportive action).
- **Tertiary button** = text/link style (least priority).
- Only one primary action per page to reduce confusion.

---

## 17. User Profiles
- Keep profile menu **minimal**: account options + logout.
- Allow **1–2 shortcuts** (e.g., Notifications, Inbox/My Approvals).

---

## 18. Accessibility (Internal-Use Standard)
- Not full WCAG compliance.
- **Focus on:**
  - High contrast & readable text.
  - Keyboard navigation & visible focus states.
  - Clear, consistent layouts.

---

## 19. Loading States
- **Spinner loaders** used consistently.
- Place them **contextually** (on buttons, sections, or page-level if needed).
- Show where the system is busy, not just that “something is happening.”

---

## 20. Error Handling
- **System-level errors** → banners at top with clear corrective action.
- **Field errors** → inline, specific, corrective messages near the field.
- Always concise and actionable, never vague.

---

## 21. Design System & Tokens
- **CSS variables as design tokens** for colors, spacing, typography.
- All modules inherit from this system for consistency and easy theming.
- White-label ready (replace logo, palette, font via config).

---

# Annex A — Design Tokens

### Colors
```css
:root {
  --brand-primary: #C53A3A;
  --brand-primary-dark: #A72E2E;
  --neutral-100: #F6F7F8;
  --neutral-300: #E6E9EC;
  --neutral-600: #6B7280;
  --text-default: #111827;
  --white: #FFFFFF;

  --success: #16A34A;
  --warning: #F59E0B;
  --error: #DC2626;
  --info: #2563EB;
  --pending: #9CA3AF;

  --radius-md: 8px;
  --radius-lg: 12px;
  --shadow-soft: 0 6px 18px rgba(16,24,40,0.06);
}
