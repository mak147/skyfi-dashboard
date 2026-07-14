Document 17: UI/UX Design System
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document establishes the UI/UX Design System for the SkyFi Networks ISP Management System. It defines the visual language, design principles, component behaviors, and interaction patterns that govern the user interface.

The purpose is to ensure that the application delivers a cohesive, intuitive, and aesthetically pleasing experience across all modules and user roles. It serves as the definitive reference for frontend developers, UI designers, and product stakeholders when implementing or evaluating interface elements.

2.0 Responsibilities
Role	Responsibility
Principal Architect / Design Lead	Define and maintain the design system. Approve deviations or additions.
UI/UX Designer	Create visual mockups, icons, and detailed component specifications that adhere to this system.
Frontend Developers	Implement components using the specified colors, typography, spacing, and interaction patterns.
QA Engineers	Verify that implemented UI matches the design system specifications (visual regression testing).
3.0 Goals
Consistency: Create a unified visual language that makes the application feel like a single, cohesive product rather than a collection of disparate modules.
Efficiency: Enable rapid UI development through reusable patterns and standardized component behaviors.
Accessibility: Ensure the interface is usable by people with disabilities (WCAG 2.1 Level AA compliance).
Brand Alignment: Reflect the SkyFi Networks brand identity—modern, reliable, and technologically advanced.
Cross-Role Usability: Support the diverse needs of power users (Finance, Network Engineers) who need dense data displays, and field users (Technicians) who need clear, touch-friendly interfaces.
4.0 Design Philosophy & Inspirations
The SkyFi Networks interface will blend the density and efficiency of enterprise tools (Salesforce, Stripe Dashboard) with the clarity and polish of modern consumer applications (Apple, Linear, Notion).

Core Principles:

Information Density without Clutter: Finance and Network Engineers need to see a lot of data at once. We will use clear hierarchy, subtle borders, and adequate whitespace to present dense information without overwhelming the user.
Progressive Disclosure: Show only what is necessary for the current task. Advanced options and secondary data are hidden behind intuitive interactions (expansion panels, modals, tooltips).
Motion with Purpose: Animations (via Framer Motion) will be used to guide attention, provide feedback, and create a sense of spatial orientation—not merely for decoration.
Contextual Awareness: The UI should adapt to the user's role and current task. A suspended customer's banner should be alarming red; a successful payment confirmation should be calming green.
Inspirational References:

Stripe Dashboard: For financial data presentation, clear status indicators, and modal-based workflows.
Linear: For keyboard navigation, dark mode aesthetics, and fluid interactions.
Apple: For typography, color palettes, and tactile feedback principles.
Notion: For flexibility in content layout and sidebar navigation patterns.
Vercel: For dashboard design, deployment status visualization (analogous to network status), and developer experience.
5.0 Color System
We will use a semantic color system built on Tailwind CSS. Colors are defined not just by hue, but by function.

Primary Palette:

Brand Blue: #0F172A (Slate 900) - Headers, primary buttons, active states.
Accent Indigo: #4F46E5 (Indigo 600) - Links, interactive elements, selected items.
Success Green: #10B981 (Emerald 500) - Positive statuses (Active, Paid), success messages.
Warning Amber: #F59E0B (Amber 500) - Warning states, pending actions, moderate alerts.
Danger Red: #EF4444 (Red 500) - Errors, critical alerts, suspended statuses, deletions.
Neutral Palette (Grays):

Background: #F8FAFC (Slate 50) - Page background (Light mode).
Surface: #FFFFFF (White) - Cards, modals, dropdowns.
Border: #E2E8F0 (Slate 200) - Subtle dividers, table borders.
Text Primary: #1E293B (Slate 800) - Headings, primary text.
Text Secondary: #64748B (Slate 500) - Descriptions, timestamps, placeholders.
Semantic Usage Table:

Context	Light Mode	Dark Mode	Tailwind Class
Page Background	Slate 50	Slate 950	bg-slate-50 / dark:bg-slate-950
Card/Panel Background	White	Slate 900	bg-white / dark:bg-slate-900
Primary Text	Slate 800	Slate 100	text-slate-800 / dark:text-slate-100
Secondary Text	Slate 500	Slate 400	text-slate-500 / dark:text-slate-400
Border/Divider	Slate 200	Slate 800	border-slate-200 / dark:border-slate-800
Primary Button	Indigo 600	Indigo 500	bg-indigo-600
Success State	Emerald 500	Emerald 400	text-emerald-500
Danger State	Red 500	Red 400	text-red-500
Warning State	Amber 500	Amber 400	text-amber-500
6.0 Typography
Font Family: Inter (Google Fonts). It is highly legible, optimized for screens, and has excellent numerical character support (tabular figures) critical for financial data.
Scale: We will use a typographic scale based on rem units for accessibility.
Element	Size	Weight	Line Height	Usage
H1	1.875rem (30px)	700 (Bold)	1.2	Page titles (e.g., "Customer Details")
H2	1.5rem (24px)	600 (Semibold)	1.3	Section headers, Modal titles
H3	1.25rem (20px)	600 (Semibold)	1.4	Card titles, Form section headers
H4	1.125rem (18px)	600 (Semibold)	1.5	Subsection headers
Body	0.875rem (14px)	400 (Regular)	1.5	Primary text, descriptions
Small	0.75rem (12px)	400 (Regular)	1.5	Metadata, timestamps, badges
Mono	0.875rem (14px)	500 (Medium)	1.5	Invoice numbers, IP addresses, code
Tabular Figures: For all numerical data (prices, invoice IDs, dates), use font-variant-numeric: tabular-nums (Tailwind: tabular-nums) to ensure numbers align vertically in tables.

7.0 Spacing & Layout
Base Unit: 4px (0.25rem). All spacing values will be multiples of 4px.
Layout Grid: 12-column grid with 24px gutters.
Container: Max-width of 1440px for the main content area, centered. Full-width for data-dense tables.
Spacing Scale: Standard Tailwind spacing (4, 8, 12, 16, 20, 24, 32, 40, 48...).
Common Patterns:

Card Padding: 24px (p-6).
Form Input Spacing: 16px gap between label and input, 24px gap between form groups.
Table Cell Padding: 16px horizontal (px-4), 12px vertical (py-3).
8.0 Component Library Patterns
While the full Component Library Specification follows in the next document, here are the key interaction and styling patterns:

8.1 Buttons

Primary: Solid Indigo background, white text, subtle shadow on hover, scale transform (1.02) on hover.
Secondary: White background, Slate border, Slate text. Hover: Slate-50 background.
Danger: Red text/background (outlined or solid variants). Used for destructive actions (Delete, Suspend).
Ghost: Transparent background. Used for icon buttons or tertiary actions.
8.2 Form Inputs

Style: Rounded corners (rounded-md), 1px border (border-slate-300), 40px height.
Focus State: 2px Indigo ring (ring-2 ring-indigo-500 ring-offset-2), border color change to Indigo.
Error State: Red border (border-red-500), red error text below input.
Labels: Always visible, positioned above the input (top-aligned labels), 12px font size, Slate-600 color.
8.3 Data Tables

Style: White background, subtle horizontal borders (border-b border-slate-200), no vertical borders.
Header: Slate-50 background, uppercase text (Small size, Slate-500, Semibold).
Rows: Hover state (hover:bg-slate-50) for interactivity. Cursor pointer if row is clickable.
Pagination: Simple, compact controls at the bottom right.
8.4 Modals (Dialogs)

Overlay: Black/50% opacity backdrop with blur (backdrop-blur-sm).
Animation: Fade in backdrop, scale up modal from 95% to 100% (Framer Motion).
Structure: Header (Title + Close X), Body (scrollable), Footer (Action buttons).
Size Variants: Small (confirmation dialogs), Medium (forms), Large (detailed views), Full-screen (mobile).
8.5 Status Badges

Shape: Pill-shaped (rounded-full), Small text (12px), horizontal padding 8px, vertical 4px.
Colors:
Active/Paid: Emerald background (light tint), Emerald text.
Pending/Unpaid: Amber background, Amber text.
Suspended/Overdue: Red background, Red text.
Draft/Inactive: Slate background, Slate text.
9.0 Motion & Animation (Framer Motion)
Animation should feel natural and responsive (300ms duration standard).

Page Transitions: Subtle fade-in (opacity: 0 -> 1) and slight upward translation (y: 10px -> 0) when navigating between major views.
List Item Entrance: Staggered children animation for lists (e.g., invoice items loading in one by one).
Modal Entrance: Scale from 0.95 to 1.0 with opacity fade. Backdrop fades in.
Hover States: Buttons scale to 1.02, cards lift with increased shadow (shadow-md to shadow-lg).
Loading States: Skeleton screens (shimmering gray boxes) preferred over spinners for content loading. Spinners for actions (buttons).
Example Framer Motion Variant:

TypeScript

const modalVariants = {
  hidden: { opacity: 0, scale: 0.95 },
  visible: { opacity: 1, scale: 1, transition: { duration: 0.2 } },
  exit: { opacity: 0, scale: 0.95, transition: { duration: 0.15 } }
};
10.0 Responsive Design Strategy
Desktop (1280px+): Full sidebar navigation, multi-column layouts, dense data tables.
Tablet (768px - 1279px): Collapsible sidebar (icons only), simplified layouts, touch-optimized button sizes (min 44px touch target).
Mobile (< 768px):
Staff Users: Hamburger menu, single-column layouts, cards instead of tables where possible, floating action buttons (FAB) for primary actions.
Field Technicians: Optimized for outdoor readability (high contrast mode option), large touch targets, offline-capable UI indicators.
11.0 Accessibility (WCAG 2.1 Level AA)
Color Contrast: Minimum 4.5:1 ratio for normal text, 3:1 for large text/UI components.
Focus Indicators: All interactive elements must have a visible focus ring (Indigo-500) for keyboard navigation.
Semantic HTML: Proper use of <header>, <nav>, <main>, <section>, <button>, etc.
ARIA Labels: Required for icon buttons, complex components (tabs, modals), and dynamic content updates.
Keyboard Navigation: Full support for Tab, Enter, Escape, and Arrow keys. Modal focus trapping.
Screen Readers: All images must have alt text. Status updates must be announced via ARIA live regions.
12.0 Dark Mode
Dark mode is a first-class citizen, not an afterthought.

Implementation: Tailwind's dark: modifier with a class-based strategy (class strategy in tailwind.config.js). Toggle stored in localStorage and user preferences.
Colors: As defined in the Color System table above. Avoid pure black (#000); use Slate-950 (#020617) for backgrounds to reduce eye strain.
13.0 Future Expansion
Theming: The design system should support white-labeling or minor brand color adjustments via CSS variables or Tailwind config changes.
Data Visualization: Specific chart color palettes and interaction patterns for the Analytics Dashboard (Chart.js integration) will be defined as an addendum to this document.
