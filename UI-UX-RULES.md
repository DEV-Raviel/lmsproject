# UI/UX Rules for AI Coding Agents

Applies to any agent that reads Markdown instructions (GitHub Copilot in VS Code, Claude, Cursor, Codex, etc.). Use these rules whenever you create or change anything a human will see or touch: pages, components, forms, emails, dashboards, error messages, copy.

Keywords: **MUST** = non-negotiable. **SHOULD** = default unless there is a stated reason. **MAY** = optional.

---

## 0. How to use this file

1. **The project's existing design system wins.** If the repo already has tokens, components, or a style guide, reuse them. These rules fill the gaps; they never justify a second, competing style.
2. **Before building UI, state three things in one line each:** who the user is, their primary task on this screen, and the device context. If unknown, assume a general audience on a phone first, then desktop, and say so.
3. **Priority when rules conflict:** safety and accessibility > usability > consistency with the project > aesthetics.
4. **Build every screen in all its states** (loading, empty, error, success, offline, disabled), not just the happy path.
5. **Never invent** features, data, testimonials, prices, or legal text. Mark placeholder content clearly.
6. **Before saying "done",** run the checklist in section 21.

---

## 1. Shneiderman's 8 Golden Rules of Interface Design

| Rule | The agent MUST |
|---|---|
| 1. Strive for consistency | Reuse the same terms, action sequences, colors, fonts, spacing, and component variants on every screen. Same action = same name = same look everywhere. |
| 2. Seek universal usability | Serve novices and experts: visible labels for beginners; shortcuts, defaults, bulk actions, and saved filters for experts. Support assistive tech, small screens, slow networks, and different languages. |
| 3. Offer informative feedback | Respond visibly to every action: pressed state, loading indicator, success or error message. Minor actions get quiet feedback; major actions get substantial feedback. |
| 4. Design dialogs to yield closure | Give multi-step flows a clear start, progress, and end with a confirmation ("Order placed", with what happens next). |
| 5. Prevent errors | Disable or hide invalid options (and say why), constrain inputs (date pickers, steppers, input types), set safe defaults, confirm destructive actions. |
| 6. Permit easy reversal | Offer undo, cancel, back, and restore. Prefer "Undo" toasts and soft-delete over "Are you sure?" dialogs for reversible actions. |
| 7. Keep users in control | Never trap, auto-advance, auto-play, or hijack scrolling or focus. Every flow has a visible exit. Users trigger actions; the system does not surprise them. |
| 8. Reduce short-term memory load | Keep context visible: show selected filters, carry values between steps, label icons, show format hints, avoid making users remember data from another screen. |

## 2. Nielsen's 10 Usability Heuristics

| # | Heuristic | Concrete implementation |
|---|---|---|
| 1 | Visibility of system status | Loading, saving, syncing, and progress indicators; current page and step highlighted; "Saved" confirmations; offline banner. |
| 2 | Match between system and real world | Use the user's vocabulary, not database or internal terms. Familiar icons and metaphors. Logical, natural ordering. |
| 3 | User control and freedom | Cancel, undo, redo, back, close (X + Esc + outside click for non-critical modals). |
| 4 | Consistency and standards | Follow platform and web conventions (logo top-left goes home, cart top-right, underlined links, magnifier = search). |
| 5 | Error prevention | Constraints, confirmations for destructive actions, inline format hints, autosave, duplicate-submit protection. |
| 6 | Recognition rather than recall | Visible options, recent items, autocomplete, persistent labels (never placeholder-only), breadcrumbs. |
| 7 | Flexibility and efficiency | Keyboard shortcuts, command palette or search for dense apps, bulk actions, remembered preferences, sensible defaults. |
| 8 | Aesthetic and minimalist design | Every element earns its place. Remove anything that competes with the primary task. |
| 9 | Help users recognize, diagnose, recover from errors | Plain language, say what happened, why, and how to fix it; place it next to the problem; keep the user's input. |
| 10 | Help and documentation | Contextual hints, searchable help, short task-oriented steps, empty states that teach. Don't rely on docs to fix a confusing UI. |

## 3. Laws of UX (apply as design constraints)

| Law | Apply it as |
|---|---|
| **Fitts's Law** | Make primary targets large and near where attention or the thumb already is. Targets at least 44×44 CSS px. Put mobile primary actions in the lower half of the screen. Keep destructive actions away from primary ones. |
| **Hick's Law** | Fewer choices = faster decisions. One primary action per view. Use progressive disclosure, sensible defaults, and grouping. Break long flows into steps. |
| **Jakob's Law** | Users expect your site to work like the ones they already use. Use standard patterns and placements. Innovate only where it clearly pays off. |
| **Miller's Law** | Chunk information into digestible groups (phone numbers, card numbers, menu sections, form sections). Treat 7±2 as a chunking heuristic, not a hard cap. Don't pad or truncate to hit a number. |
| **Law of Proximity** | Related items sit close together; unrelated groups get clearly larger gaps. Space is the cheapest grouping tool. |
| Law of Similarity | Elements that look alike are read as related. Same style = same function. Never style non-interactive text like a link or button. |
| Law of Common Region | Elements inside a shared container (card, fieldset, background) are read as a group. |
| Law of Uniform Connectedness | Visually connected items (lines, steppers, shared borders) read as one unit. |
| Law of Prägnanz | People perceive complex shapes in the simplest form. Prefer simple, clear layouts and icons. |
| Tesler's Law | Complexity can't be removed, only moved. Absorb it in the system (defaults, smart parsing) rather than pushing it to the user. |
| Postel's Law | Be liberal in what you accept (phone/date formats, extra spaces, case) and strict in what you output. |
| Doherty Threshold | Respond within ~400 ms. Show feedback immediately; use skeletons and optimistic UI for anything slower. |
| Von Restorff Effect | The one item that differs gets remembered. Use contrast for the primary CTA only. If everything stands out, nothing does. |
| Serial Position Effect | People remember the first and last items best. Put key nav items and actions at the start and end. |
| Peak-End Rule | Experiences are judged by their peak and their end. Polish the success moment and the final step. |
| Zeigarnik Effect / Goal-Gradient | Show progress and unfinished state. People work harder as completion nears. |
| Aesthetic-Usability Effect | Polished interfaces are perceived as easier to use, but never trade usability for looks. |
| Occam's Razor | Pick the simplest design that does the job. |
| Pareto Principle | Design the 20% of features that 80% of users need first, and make them prominent. |

---

## 4. Layout and styling rules

### 4.1 Spacing: the 8-point grid
- All spacing, margins, padding, sizing, and radii MUST be multiples of 8 px, or 4 px for tight spots (icon gaps, small component padding).
- Use a fixed spacing scale: `4, 8, 12, 16, 24, 32, 48, 64, 96, 128`. Don't use arbitrary values such as 13 px or 22 px.
- Inner padding of a group MUST be less than or equal to the gap between that group and its neighbors (proximity).
- Prefer `gap` in flex/grid over margins on children.
- Line-heights and icon sizes SHOULD land on the 4 px baseline (e.g. 16/24, 14/20, 20/28).

### 4.2 Color: the 60-30-10 rule
- **60%** dominant neutral (page background, surfaces, whitespace).
- **30%** secondary / structural (cards, sidebars, headers, borders, secondary text).
- **10%** accent / brand, reserved for key actions and key moments (primary button, active state, focus ring, links).
- Semantic colors (error, warning, success, info) are separate from the accent budget and carry fixed meanings only.
- Palette = one neutral ramp (about 9-11 steps) + one brand color ramp + four semantic colors. Don't add more hues without a reason.
- Never use color as the only carrier of meaning. Pair it with text, an icon, or a pattern.
- Don't use the accent color for decoration. Don't use the same color for "primary" and "danger".

### 4.3 Typography discipline
- **One font family, two at most** (clearly different roles, e.g. headings + body). System font stack is a valid, fast default.
- **3-4 distinct sizes per screen.** Use a modular scale (ratio about 1.2-1.25) defined once as tokens.
- At most **2-3 weights** (e.g. 400, 600, 700). Use size, weight, and color, not ALL CAPS or underline, for hierarchy.
- Body text MUST be at least 16 px (1rem). Secondary text at least 14 px. Nothing under 12 px.
- Body line-height 1.4-1.6; headings 1.1-1.3. Line length **45-75 characters** (`max-width: 65ch` is a good default).
- Use `rem`/`em` for type so user font-size settings work. Left-align LTR body text; don't justify.
- Reserve ALL CAPS for very short labels, if used at all. Never for sentences.

### 4.4 Whitespace (negative space)
- Let content breathe. Generous spacing improves comprehension more than extra borders, boxes, or dividers.
- Prefer space to lines to separate groups. Add a divider only when space can't do the job.
- Use larger section spacing (64-128 px desktop, 48-64 px mobile) than in-section spacing (16-32 px).
- Don't fill every area. Empty space around the primary action is a feature.

### 4.5 Visual hierarchy and composition
- Each screen MUST have one obvious primary focal point and one primary action.
- Establish hierarchy with size, weight, contrast, position, and space, in that order of preference.
- Follow reading patterns: F-pattern for text-heavy pages, Z-pattern for sparse landing pages. Put the key message and CTA where the eye lands first.
- Align to a consistent grid (12-column desktop, 4-8 columns mobile) with consistent gutters. Align left edges; avoid ragged, near-miss alignments.
- Group with proximity and common region. Limit nesting (cards inside cards inside cards).
- Keep a consistent border-radius scale (e.g. 4/8/16) and elevation scale (at most 3 levels). Don't mix many styles.
- Icons: one consistent set, one stroke weight, aligned to the text baseline, always with an accessible name. Icon-only buttons need a tooltip and `aria-label`.
- Imagery: purposeful, not filler. Use real photos or illustrations that show the product or context. Always set dimensions to avoid layout shift.

---

## 5. Design tokens (single source of truth)

Define all visual values once as tokens (CSS custom properties, Tailwind config, or the project's theme) and reference them everywhere. Never hard-code colors, spacing, or font sizes inside components.

```css
:root {
  /* spacing (8pt grid) */
  --space-1: 4px;  --space-2: 8px;  --space-3: 12px; --space-4: 16px;
  --space-6: 24px; --space-8: 32px; --space-12: 48px; --space-16: 64px;

  /* type */
  --font-body: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --text-sm: 0.875rem; --text-md: 1rem; --text-lg: 1.25rem; --text-xl: 1.5625rem;

  /* color: neutral (60), structure (30), accent (10), semantic */
  --bg: #ffffff;  --surface: #f6f7f9;  --border: #d9dde3;
  --text: #1b1f24; --text-muted: #566070;
  --accent: #2457d6; --accent-contrast: #ffffff;
  --danger: #b42318; --warning: #8a5a00; --success: #1a7f37; --info: #0b65c2;

  /* shape and motion */
  --radius-sm: 4px; --radius-md: 8px; --radius-lg: 16px;
  --dur-fast: 120ms; --dur-base: 200ms; --dur-slow: 320ms;
  --ease-out: cubic-bezier(0.2, 0, 0, 1);
}
```

Values above are an example; replace them with the project's brand while keeping the structure and contrast requirements.

---

## 6. Responsive and mobile-first design

- Design and code **mobile-first**; enhance with `min-width` media queries. Choose breakpoints where the content breaks (typical: 480 / 768 / 1024 / 1280), not per device.
- `<meta name="viewport" content="width=device-width, initial-scale=1">` is required. **Never** disable zoom (`user-scalable=no`, `maximum-scale=1`).
- No horizontal scrolling at 320 px width (except for deliberately scrollable regions such as wide tables or carousels).
- Content MUST reflow at 400% zoom (equivalent to 320 px wide) and remain usable at 200% text size.
- Touch targets at least **44×44 CSS px**, with at least 8 px between adjacent targets. (WCAG 2.2 AA minimum is 24×24; use 44 as the product standard.)
- Put primary mobile actions within thumb reach (lower half); sticky bottom action bars are fine if they don't cover content. Respect safe-area insets (`env(safe-area-inset-*)`).
- Use fluid sizing: `clamp()` for type and spacing, `minmax()`/`auto-fit` grids, container queries for components, `max-width: 100%` on media.
- Never make hover the only way to reveal information or actions. Touch and keyboard users have no hover.
- Prefer a simple one-column mobile layout; collapse secondary content behind clear disclosure controls.
- Tables on small screens: horizontal scroll container with sticky first column, or restructure rows into stacked cards.
- Use `srcset`/`sizes` and `<picture>` for responsive images. Support landscape orientation. Don't lock orientation.
- Test at 320, 375, 768, 1024, 1440 px, in light and dark themes.

---

## 7. Accessibility (WCAG 2.2 AA is the minimum)

**Structure and semantics**
- Use native HTML elements first: `<button>`, `<a href>`, `<input>`, `<select>`, `<dialog>`, `<details>`. Never use `<div onclick>`. Use ARIA only when no native element exists, and follow the ARIA Authoring Practices for custom widgets.
- Use landmarks: `<header>`, `<nav>`, `<main>` (one per page), `<aside>`, `<footer>`. Exactly one `<h1>`; headings in logical order with no skipped levels, and never chosen for visual size.
- Set `<html lang="…">`; mark language changes inline.
- Provide a "Skip to main content" link as the first focusable element.

**Perceivable**
- Contrast: **4.5:1** for normal text, **3:1** for large text (about 24 px, or 18.66 px bold) and for UI components, icons, input borders, and focus indicators. Check all states and both themes.
- Meaningful images get descriptive `alt`; decorative images get `alt=""`. Don't put text in images. Charts need a text alternative or data table.
- Video needs captions; audio needs transcripts. No autoplay with sound; any motion that auto-plays for more than 5 seconds needs pause/stop.
- Don't convey information by color, shape, or position alone.

**Operable**
- Everything MUST work with the keyboard alone, in a logical tab order that matches the visual order. No keyboard traps. `Esc` closes overlays, arrow keys navigate composite widgets (tabs, menus, listboxes).
- Visible focus on every interactive element: at least 2 px, at least 3:1 contrast, not hidden behind sticky headers. **Never** `outline: none` without an equal or better replacement. Use `:focus-visible`.
- Modals: move focus in on open, trap focus, restore focus to the trigger on close, make the background inert.
- On SPA route changes: move focus to the new page's heading or main, and update the document title.
- Link and button text MUST make sense out of context ("View invoice #1042", not "Click here").
- Honor `prefers-reduced-motion`; avoid flashing more than 3 times per second.
- Give users enough time: no hard timeouts without warning and an easy extension.
- Pointer gestures (drag, pinch, swipe) MUST have a single-pointer alternative.

**Understandable and robust**
- Every input has a programmatically associated `<label>`. Placeholders never replace labels.
- Errors are announced and linked to their fields (`aria-invalid`, `aria-describedby`); status updates use `aria-live="polite"` (`role="alert"` for critical).
- Reflect state in ARIA (`aria-expanded`, `aria-current`, `aria-selected`, `aria-pressed`, `aria-busy`).
- Don't ask users to re-enter information they already gave in the same flow.
- Don't use CAPTCHA that depends on a single sense without an alternative.
- Verify with automated tools (axe, Lighthouse) **and** manual checks: keyboard-only, screen reader (NVDA/VoiceOver), 200% zoom, high-contrast / forced-colors mode.

---

## 8. Navigation and information architecture

- Organize by **user goals and mental models**, not by company org chart or database structure. Validate labels with real vocabulary.
- Primary navigation: about 5-7 top-level items, in a consistent location on every page. Chunk larger sets into groups.
- Show where the user is (`aria-current="page"`, highlighted item, breadcrumbs for hierarchies deeper than two levels) and how to get back.
- Logo links to home. Include search on content-heavy sites and apps; make it findable, forgiving of typos, and show recent/suggested queries.
- Mobile: bottom tab bar (3-5 items with icon + label) for apps; for sites, a menu button with a visible "Menu" label or recognizable icon.
- URLs MUST be human-readable, stable, and shareable; every meaningful state (filters, tabs, search, pagination) SHOULD be linkable. The browser Back button must work as expected.
- Avoid deep mega-menus and hidden navigation on desktop. Avoid nav that reorders itself.
- Footer: secondary links (contact, help, legal, privacy, sitemap, social).
- Use progressive disclosure: show the essentials first, reveal details on demand.
- Every page has a clear purpose, a clear next step, and no dead ends. Include a helpful 404 page with search and links home.

---

## 9. Components: behavior rules

| Component | Rules |
|---|---|
| **Buttons** | One primary per view or section; secondary = outlined/ghost; tertiary = text. Labels are verb-first and specific ("Save changes", "Add to cart"). Min 44 px tall. Show a loading state that keeps width (no layout jump). Destructive buttons use the danger color and are not placed next to primary ones. Don't disable without explaining why. |
| **Links** | Underlined or otherwise distinguishable beyond color in body text. Visited/hover/focus states. External or download links are indicated. Open in the same tab by default. |
| **Text inputs** | Persistent label above the field, helper text, correct `type`, `autocomplete`, and `inputmode`. Large enough for touch. Show character limits before they're hit. |
| **Selection controls** | Radio for 2-5 mutually exclusive options (all visible). Select/dropdown for 6-15. Combobox with search for more. Checkbox = apply on submit; toggle switch = applies immediately. Label states clearly ("On"/"Off"). |
| **Modals / dialogs** | Use sparingly, only for focused, interrupting tasks. Clear title, one primary action, a close button, `Esc` to close, focus managed. Don't stack modals. Don't open on page load. Prefer inline or a new page for long forms. |
| **Toasts / snackbars** | For non-critical confirmations. Don't use for errors the user must act on. Visible for at least 5 s (longer for more text), pausable on hover/focus, include "Undo" where possible, announced via `aria-live`. |
| **Tooltips** | Supplementary only. Never the sole home of essential information. Keyboard- and touch-accessible. Show on focus as well as hover. |
| **Tabs** | For parallel content of the same type; the first tab is the default; no more than about 6. Don't use tabs for sequential steps. |
| **Accordions** | For optional or secondary content; allow keyboard control; clear expanded/collapsed indicator. Don't hide critical info. |
| **Tables** | Left-align text, right-align numbers, consistent decimals. Sticky header for long tables, sortable columns with indicated sort state, row hover/selection, bulk actions, empty/loading/error states. Use `<th scope>` and `<caption>`. Include pagination (preferred for goal-oriented tasks) or infinite scroll with a way to reach the footer and preserve position. |
| **Cards** | One topic per card, one clear primary action, entire card clickable only if there's a single destination. Consistent structure across a set. Don't nest cards. |
| **Pagination / lists** | Show total counts, current position, and page size controls when relevant. Preserve scroll and filters on back-navigation. |
| **Search and filters** | Visible applied filters with one-click removal, "Clear all", result counts, sensible empty-result suggestions. |
| **Date / time pickers** | Allow typing as well as picking; show the expected format; accept flexible input; handle time zones explicitly. |
| **File upload** | Drag-and-drop plus button, show accepted types and size limits up front, show progress, allow cancel and remove, show per-file errors. |
| **Progress indicators** | Determinate bar when you know the percent; indeterminate spinner for short unknown waits; step indicator for multi-step flows with "Step 2 of 4". |
| **Badges / tags** | Short, consistent, not color-only. Don't overuse. |
| **Menus / dropdowns** | Keyboard operable (arrows, `Esc`, type-ahead), close on outside click, don't open on hover only. |

Every interactive component MUST define these states: default, hover, focus-visible, active/pressed, disabled, loading, error, and success where relevant.

---

## 10. Forms

- **Single column.** Labels above fields. Group related fields with `<fieldset>`/`<legend>`. Order fields as users expect.
- Ask only for what's needed. Every extra field lowers completion. Make optional fields clearly "(optional)".
- Use the right input `type` (`email`, `tel`, `url`, `number` only for true numbers), `inputmode`, `autocomplete` tokens (`given-name`, `email`, `one-time-code`, `street-address`…), and `autocapitalize`/`spellcheck` where relevant.
- Validate **on blur or submit**, not while the user is still typing for the first time. Once a field has errored, re-validate live so the error clears as soon as it's fixed.
- Show errors **inline next to the field** and, in long forms, an error summary at the top that receives focus and links to each field. Never rely on color alone.
- **Preserve user input** after errors. Never clear the form.
- Accept flexible formats (Postel's Law): spaces in card/phone numbers, any date format with a clear hint, case-insensitive emails. Normalize on the system side.
- Don't disable the submit button as the only feedback. If you disable it, explain what is missing. Prevent double submits with a loading state.
- Show password toggle ("Show"), allow paste, support password managers, don't impose arbitrary composition rules; explain requirements up front. Offer passkeys/SSO where practical.
- Multi-step forms: show progress, allow back without losing data, summarize before final submit, autosave drafts.
- Use smart defaults, pre-fill known data, and don't pre-check opt-ins for marketing or upsells.
- End with a specific success state ("Your message was sent. We'll reply within 1 business day") and a clear next step.
- Use CAPTCHA only if needed; prefer invisible or honeypot methods.

---

## 11. System feedback and states

**Response-time guidance:** under 0.1 s feels instant; 1 s keeps the flow of thought; 10 s is the limit of attention.
- Under about 300 ms: no indicator needed. Over about 300 ms: show a skeleton or spinner. Over about 1 s: show a skeleton that mirrors the final layout. Over about 10 s: determinate progress, time estimate, and the ability to cancel or continue in the background.
- Use **optimistic UI** for low-risk actions (like, reorder, toggle) and roll back with a clear message on failure.
- Don't block the whole page for a local update. Update the smallest region possible.
- Prevent layout shift when content loads (reserve space).

**Required states for every data-driven view**
1. **Loading**: skeleton or spinner.
2. **Empty**: say why it's empty and give a primary action to fix it ("No projects yet. Create your first project").
3. **Error**: what happened, why if known, how to fix it, and a retry action. Keep the user's data.
4. **Partial / degraded**: show what loaded; flag what didn't.
5. **Success**: confirm what changed, where to see it, and what to do next.
6. **Offline / slow network**: detect, inform, queue or retry, never lose work.
7. **Permission denied / not found**: explain and offer a path forward.

---

## 12. Content and microcopy

- Write in the user's language, not the system's. Say "Your account", not "User entity". Don't expose error codes without a human explanation.
- **Sentence case** for headings, buttons, and labels. No ALL CAPS sentences. No exclamation marks except truly celebratory moments.
- Buttons and links start with a **verb** and name the result ("Download report", "Delete project"). Avoid "Submit", "OK", "Click here".
- Use the **same word for the same thing** everywhere, and the same name through a whole flow: the "Publish" button leads to a "Published" toast. Keep a small glossary.
- **Error message formula:** what happened + why (if known) + how to fix. Plain language, no blame, no jargon, no "Oops!", no apologizing for user mistakes.
  - Bad: "Invalid input." Good: "Enter a date like 03/21/2026."
  - Bad: "Error 500." Good: "We couldn't save your changes. Check your connection and try again."
- Destructive confirmations name the object and consequence: "Delete 'Q3 budget'? This also removes its 12 comments and can't be undone." Buttons: "Delete project" / "Keep project", not "Yes" / "No".
- Front-load the important word in headings, links, and list items. Use short paragraphs, descriptive subheads, and lists. Aim for roughly an 8th-grade reading level for general audiences.
- Empty states and onboarding teach by doing: short, specific, one action.
- Don't use lorem ipsum in finished work. Don't use filler copy that says nothing ("Welcome to our platform").
- Localize dates, numbers, currency, units; avoid idioms that don't translate.

---

## 13. Motion and interaction

- Motion MUST communicate something: cause and effect, spatial relationship, state change, or attention. Remove it if it doesn't.
- Durations: micro-interactions 100-150 ms; element transitions 200-300 ms; large or page-level transitions at most 500 ms. Ease-out for entering, ease-in for leaving, never linear for UI movement.
- Animate `transform` and `opacity` only, to stay at 60 fps and avoid layout thrash.
- Honor `prefers-reduced-motion: reduce`: replace movement with fades or no animation.
- No auto-playing carousels, parallax-heavy scroll-jacking, or looping decorative animation near reading content.
- Hover is an enhancement, never required. Provide active/pressed feedback within 100 ms for tap/click.
- Don't hijack native scrolling, back button, right-click, text selection, or zoom.

---

## 14. Performance is UX

Targets (at the 75th percentile, mobile): **LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1.**

- Optimize images: modern formats (AVIF/WebP), correct dimensions, `width`/`height` attributes (prevents CLS), `loading="lazy"` below the fold, high-priority load for the LCP image. Never lazy-load the hero.
- Fonts: subset, `font-display: swap`, preload only critical files, minimal families and weights. Use system fonts when possible.
- Ship less JavaScript: code-split by route, defer non-critical scripts, tree-shake, avoid heavy libraries for simple tasks. Third-party scripts load async and after interaction when possible.
- Reserve space for ads, embeds, banners, and late content. Don't inject content above existing content.
- Debounce search/typeahead, virtualize long lists, cache responses, prefetch likely next pages.
- Keep interactions responsive: break up long tasks, don't block the main thread, show feedback immediately.
- Build progressively: core content and actions work with HTML/CSS first; JavaScript enhances.
- Support slow networks and low-end devices. Test with throttling.

---

## 15. Trust, security, and privacy UX

- HTTPS only. No mixed content.
- Ask for permissions (notifications, location, camera) **in context, after** the user sees the value, and explain why. Never on page load.
- Cookie/consent banners: "Accept" and "Reject" have equal prominence and effort; granular options available; no pre-ticked boxes; remember the choice.
- Be transparent about pricing, fees, data use, subscriptions, and renewals **before** commitment. Link to privacy policy and terms where data is collected.
- Provide easy account controls: edit profile, export data, delete account, cancel subscription (as easy as signing up).
- Auth: clear errors that don't leak whether an account exists ("Email or password is incorrect"); support password reset and 2FA; warn before session expiry and preserve user work; mask sensitive data with reveal-on-demand.
- Don't put secrets, tokens, or personal data in URLs, logs, or client-visible errors.
- Social proof must be real and attributable. Never fabricate reviews, counts, or urgency.

**Dark patterns are forbidden:** confirmshaming, fake urgency/scarcity, hidden costs, pre-checked upsells, trick questions, disguised ads, forced continuity, roach-motel cancellation, nagging pop-ups, obstructed unsubscribe, misdirecting visual emphasis.

---

## 16. Theming, dark mode, and internationalization

**Theming**
- Support `prefers-color-scheme` and, where there's a settings area, a manual toggle (light / dark / system). Set `color-scheme` so native controls match.
- Dark mode is a separate token set, not an inversion. Re-check contrast, shadows (use borders or lighter surfaces instead), and images for both themes.
- Support `forced-colors` (Windows high contrast): keep borders and focus outlines visible.

**Internationalization**
- Use logical CSS properties (`margin-inline-start`, `padding-block`, `inset-inline`) so layouts flip for RTL; set `dir` correctly.
- Allow 30-40% text expansion; no fixed-width buttons or truncated labels. Don't build sentences by concatenating strings. Use ICU/message formats for plurals.
- Use `Intl` APIs for dates, numbers, currency, sorting. Don't hard-code formats.
- Don't embed text in images. Flip directional icons (arrows) in RTL.

---

## 17. Website essentials (the "perfect website" baseline)

- **Clear value proposition** above the fold: what it is, who it's for, why it matters, and one primary CTA.
- Clear primary CTA on every key page; secondary paths for different intents. Don't compete with yourself.
- Credibility: real contact info, about page, policies, genuine proof points.
- **SEO and sharing basics:** unique `<title>` (about 50-60 chars), meta description, one `<h1>`, canonical URL, Open Graph/Twitter card tags, descriptive link text, `alt` text, semantic HTML, structured data where relevant, `sitemap.xml`, `robots.txt`.
- Favicon, app icons, and a web manifest (if installable). `theme-color` meta.
- Custom 404 and 500 pages; maintenance mode page; redirects for moved URLs.
- Print styles for content users print (receipts, articles, tickets).
- Legal essentials where applicable: privacy policy, terms, cookie policy, accessibility statement.
- Analytics and tracking respect consent and privacy, and never block rendering.
- Email and notification UX: clear sender, subject, one primary action, plain-text fallback, unsubscribe that works.

---

## 18. Data display and visualization

- Pick the chart for the question: comparison → bar; trend over time → line; part of whole → stacked bar or (few categories) donut; distribution → histogram; relationship → scatter.
- Start bar-chart axes at zero. Label axes and units. Sort by value unless order is natural. Direct-label instead of relying on legends where possible.
- Don't encode meaning by color alone; use a colorblind-safe palette plus labels/patterns. Keep 3:1 contrast for chart elements.
- Provide a data table or text summary alternative. Make tooltips keyboard-accessible.
- Dashboards: lead with the most important number, show change vs. a baseline, limit to what supports decisions, allow drill-down. Don't use 3D, dual axes, or decorative effects.
- Format numbers for scanning: thousands separators, consistent decimals, units, right-aligned.

---

## 19. Code conventions for UI work

- Semantic HTML first, then CSS, then JS. The page must be understandable and operable without decoration.
- Reuse existing components. Before creating a new one, search for a close match; extend via props/variants instead of duplicating.
- No magic numbers or hard-coded colors; use tokens. No inline styles for design values. Avoid `!important`. Keep selector specificity low and avoid collisions between section-level and element-level selectors.
- Use `rem`, `em`, `%`, `fr`, `ch`, `clamp()`; avoid fixed pixel heights on containers that hold text.
- Don't remove or move focus unexpectedly during re-renders. Manage focus on route changes, dialogs, and dynamic content.
- `<button type="button">` unless it's meant to submit. Set `rel="noopener noreferrer"` on external `target="_blank"` links.
- Every image has `alt`; every input has a label; every icon-only control has an accessible name.
- Keep components small, state-driven, and presentational where possible. Handle loading/error/empty in the component or its container, consistently.
- Add tests for critical flows and accessibility (axe, Testing Library queries by role/label, keyboard navigation). Use visual regression or screenshots where available.
- Follow the project's styling approach (CSS modules, Tailwind, BEM, CSS-in-JS). Don't introduce a second one.

---

## 20. Avoid templated, generic output

AI-generated interfaces tend to converge on the same look. Make choices for this specific product and audience instead of defaulting.

- Don't chop everything into identical rounded cards with the same soft shadow; vary structure by importance.
- Don't put a tracked-out ALL-CAPS eyebrow label above every heading, or add "→" to every link.
- Don't number sections (01/02/03) unless the content is truly a sequence.
- Don't emphasize one random word in every headline with color or italics.
- Don't add fade-and-slide-up entrances to every section or hover lifts to every card.
- Don't default to a gradient wash, a hero with three feature cards, or a stock "trusted by" strip without real content.
- Do pick a deliberate palette, typography, and layout derived from the product's subject, and spend visual boldness in one memorable place. Keep the rest quiet and disciplined.
- Do cut decoration that doesn't serve the user's task.

---

## 21. Definition of Done checklist

Do not report UI work as finished until you have verified each applicable item. State anything you could not verify.

**Usability**
- [ ] One clear primary action per view; the user's task is obvious in 5 seconds
- [ ] Consistent terms, components, and patterns with the rest of the product
- [ ] Every action gets visible feedback; every flow has a clear end and a way out
- [ ] Destructive actions are confirmed or undoable; errors are prevented where possible
- [ ] Loading, empty, error, success, and offline states are designed and implemented
- [ ] Copy is plain-language, verb-first on buttons, with helpful error messages

**Visual**
- [ ] Spacing on the 4/8 px scale; values come from tokens
- [ ] 60-30-10 color balance; accent reserved for key actions
- [ ] 1-2 font families, 3-4 sizes per screen, body ≥ 16 px, line length 45-75 ch
- [ ] Clear hierarchy, alignment, grouping by proximity, and generous whitespace

**Responsive**
- [ ] Works at 320 px through large desktop; no horizontal scroll; zoom not disabled
- [ ] Touch targets ≥ 44×44 px with spacing; no hover-only functionality

**Accessibility**
- [ ] Semantic HTML, landmarks, one `<h1>`, ordered headings, `lang` set
- [ ] Contrast ≥ 4.5:1 text and ≥ 3:1 UI/large text in all states and themes
- [ ] Full keyboard operation, visible focus, no traps, focus managed in dialogs and route changes
- [ ] Labels, alt text, `aria-*` states, error association, live regions
- [ ] `prefers-reduced-motion` and forced-colors respected
- [ ] axe/Lighthouse clean; manual keyboard and screen-reader spot check done

**Performance and quality**
- [ ] Images sized, optimized, and lazy-loaded; no layout shift; fonts optimized
- [ ] LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1 (or a stated reason why not measured)
- [ ] No console errors; works with JS slow or failing for core content where feasible
- [ ] Light and dark themes verified; RTL and long-text resilience considered

**Trust**
- [ ] No dark patterns; pricing and data use are transparent; permissions are requested in context

---

## 22. Project-specific overrides (fill this in per repo)

```
Product / purpose:
Primary users and their skill level:
Platforms and browsers supported:
Design system / component library:
Brand tokens (colors, fonts, radius):
Accessibility target (default: WCAG 2.2 AA):
Languages / locales:
Known constraints (legacy code, framework, performance budget):
```

Anything filled in above overrides the general rules in this file.

---

## References

- Shneiderman, "The Eight Golden Rules of Interface Design": https://www.cs.umd.edu/users/ben/goldenrules.html
- Nielsen Norman Group, "10 Usability Heuristics": https://www.nngroup.com/articles/ten-usability-heuristics/
- Laws of UX: https://lawsofux.com/
- WCAG 2.2: https://www.w3.org/TR/WCAG22/
- Core Web Vitals: https://web.dev/articles/vitals
- Figma, UI design principles: https://www.figma.com/resource-library/ui-design-principles/
- Bakusevych, "58 rules for stunning and effective UI design": https://uxdesign.cc/58-rules-for-stunning-and-effective-user-interface-design-ea4b93f931f6
