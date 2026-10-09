<style>
/* ================================================
   SLIDEBOOK PRESENTATION — DESIGN SYSTEM
   Consistent typography, color, spacing, animation.
   Layout variation is content-driven, not random.
   ================================================ */

/* --- Design Tokens --- */
.slide-presentation {
    --slide-radius: 1.1rem;
    --slide-radius-sm: 0.7rem;
    --reveal-stagger: 120ms;
    color-scheme: dark;
    overflow-wrap: anywhere;
    background-color: var(--slide-bg);
    color: var(--slide-text);
}

/* --- Focus & Accessibility --- */
.slide-presentation :focus-visible {
    outline: 2px solid var(--slide-violet);
    outline-offset: 5px;
}

/* --- Header --- */
.slide-presentation .presentation-header {
    background: var(--slide-surface);
}

/* --- Buttons --- */
.slide-presentation .presentation-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    min-height: 44px;
    padding: 0.65rem 1rem;
    border: 1px solid var(--slide-border);
    border-radius: var(--slide-radius);
    background: var(--slide-surface-raised);
    color: var(--slide-text);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s, transform 0.15s;
}
.slide-presentation .presentation-button:hover {
    background: #334155;
}
.slide-presentation .presentation-button:active {
    transform: scale(0.97);
}
.slide-presentation .presentation-button:disabled {
    opacity: 0.4;
    cursor: default;
    transform: none;
}
.slide-presentation .presentation-primary {
    background: var(--slide-indigo);
    border-color: #6366f1;
    color: white;
}
.slide-presentation .presentation-primary:hover {
    background: #4338ca;
}

/* --- Progress Bar --- */
.slide-presentation .presentation-progress {
    height: 3px;
    background: var(--slide-surface-raised);
}
.slide-presentation .presentation-progress span {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, var(--slide-gradient-start), var(--slide-gradient-end));
    transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

/* --- Progress Dots --- */
.slide-presentation .progress-dots {
    display: flex;
    gap: 0.4rem;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0;
}
.slide-presentation .progress-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #334155;
    border: none;
    cursor: pointer;
    padding: 0;
    transition: background 0.2s, transform 0.2s;
}
.slide-presentation .progress-dot:hover {
    background: #818cf8aa;
    transform: scale(1.3);
}
.slide-presentation .progress-dot.active {
    background: var(--slide-gradient-start);
    transform: scale(1.3);
}

/* --- Stage --- */
.slide-presentation .presentation-stage {
    display: grid;
    width: 100%;
    max-width: 1240px;
    margin: 0 auto;
    padding: clamp(1.25rem, 4vw, 4rem);
    position: relative;
}

/* --- Slide Container --- */
.slide-presentation .presentation-slide {
    grid-area: 1 / 1;
    min-width: 0;
}
/* Entry animation (applied via class toggle for smooth transitions) */
.slide-presentation .presentation-slide[data-entering="true"] {
    animation: slide-arrive 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
}

/* --- Slide Heading --- */
.slide-presentation .slide-heading {
    margin-bottom: clamp(1.5rem, 3vw, 2.75rem);
}
.slide-presentation .slide-heading h2 {
    font-size: clamp(1.7rem, 3.2vw, 2.8rem);
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.035em;
    max-width: 28ch;
    text-wrap: balance;
    color: var(--slide-text-heading);
}
.slide-presentation .slide-subtitle {
    font-size: clamp(1rem, 1.5vw, 1.2rem);
    line-height: 1.7;
    margin-top: 1rem;
    color: #cbd5e1;
    max-width: 70ch;
}

/* --- Kicker / Label Badge --- */
.slide-presentation .slide-kicker {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.13em;
    text-transform: uppercase;
    color: var(--slide-violet);
}
.slide-presentation .slide-heading .slide-kicker {
    border: 1px solid #6366f155;
    background: #312e8133;
    padding: 0.4rem 0.75rem;
    border-radius: 999px;
}

/* --- Blocks Grid --- */
.slide-presentation .slide-blocks {
    display: grid;
    gap: 1.1rem;
    min-width: 0;
}
.slide-presentation .slide-block {
    min-width: 0;
}

/* --- Block Reveal Animation --- */
.slide-presentation .slide-block[data-reveal] {
    opacity: 0;
    transform: translateY(12px);
    animation: block-reveal 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: var(--reveal-delay, 0ms);
}

/* --- Learning Point (Card) --- */
.slide-presentation .learning-point {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    height: 100%;
    padding: 1.5rem;
    border: 1px solid var(--slide-border);
    border-radius: var(--slide-radius);
    background: var(--slide-surface);
    transition: border-color 0.25s, box-shadow 0.25s;
}
.slide-presentation .learning-point:hover {
    border-color: var(--slide-accent-soft);
    box-shadow: 0 0 0 1px var(--slide-accent-soft);
}
.slide-presentation .point-icon {
    flex-shrink: 0;
    color: var(--slide-accent);
    margin-top: 0.15rem;
}
.slide-presentation .point-label {
    font-size: 1.15rem;
    font-weight: 700;
    color: #c7d2fe;
    margin-bottom: 0.7rem;
}
.slide-presentation .point-text,
.slide-presentation .learning-callout p {
    font-size: clamp(1rem, 1.3vw, 1.12rem);
    line-height: 1.85;
    white-space: pre-wrap;
    color: var(--slide-text);
}

/* --- Keyword Signaling --- */
.slide-presentation .signal-term {
    background: #312e8166;
    color: #c7d2fe;
    padding: 0.1em 0.4em;
    border-radius: 0.3em;
    font-weight: 600;
    border: 1px solid #6366f133;
}

/* --- Signal Terms Bar --- */
.slide-presentation .signal-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}
.slide-presentation .signal-badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    background: #312e8144;
    color: #a5b4fc;
    border: 1px solid #6366f133;
    letter-spacing: 0.03em;
}

/* ================================================
   LAYOUT: CONCEPT — Hero-style introduction
   ================================================ */
.slide-presentation .layout-concept .slide-composition {
    display: grid;
    grid-template-columns: minmax(120px, 0.6fr) minmax(0, 2fr);
    align-items: center;
    gap: clamp(1rem, 4vw, 4rem);
    padding: 2rem 0;
}
.slide-presentation .concept-mark {
    display: grid;
    place-items: center;
    color: var(--slide-violet);
    background: linear-gradient(135deg, #312e8155, #4c1d9533);
    border: 1px solid #6366f155;
    aspect-ratio: 1;
    border-radius: 2rem;
    max-width: 200px;
    position: relative;
    overflow: hidden;
}
.slide-presentation .concept-mark::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 30%, #818cf822, transparent 60%);
}
.slide-presentation .layout-concept .learning-point {
    background: none;
    border: 0;
    border-left: 2px solid var(--slide-gradient-start);
    border-radius: 0;
    padding: 1rem 0 1rem 2rem;
}
.slide-presentation .layout-concept .point-text {
    font-size: clamp(1.15rem, 2vw, 1.65rem);
    line-height: 1.65;
}

/* ================================================
   LAYOUT: KEY POINTS — Grid of cards
   ================================================ */
.slide-presentation .layout-key-points .slide-blocks {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.slide-presentation .layout-key-points .learning-point {
    border-top: 3px solid transparent;
    border-image: linear-gradient(90deg, var(--slide-gradient-start), var(--slide-gradient-end)) 1;
}
.slide-presentation .layout-key-points .block-item:nth-child(even) .learning-point {
    border-image: linear-gradient(90deg, var(--slide-gradient-end), #e879f9) 1;
}
/* 3-column for exactly 3 items on desktop */
.slide-presentation .layout-key-points .slide-blocks:has(> .block-item:nth-child(3)):not(:has(> .block-item:nth-child(4))) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

/* ================================================
   LAYOUT: COMPARISON — Side-by-side columns
   ================================================ */
.slide-presentation .layout-comparison .slide-blocks {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.slide-presentation .layout-comparison .slide-blocks:has(> .block-item:nth-child(3)):not(:has(> .block-item:nth-child(4))) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.slide-presentation .layout-comparison .learning-point {
    border-top: 3px solid var(--slide-gradient-start);
    display: block;
    padding: 1.75rem;
    position: relative;
}
.slide-presentation .layout-comparison .point-icon {
    margin-bottom: 1.2rem;
}
.slide-presentation .layout-comparison .slide-block:nth-child(even) .learning-point {
    border-top-color: var(--slide-violet);
    background: #17162f;
}
.slide-presentation .layout-comparison .slide-block:nth-child(odd) .learning-point::before {
    content: 'A';
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.65rem;
    font-weight: 700;
    width: 1.5rem;
    height: 1.5rem;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background: var(--slide-gradient-start);
    color: white;
    opacity: 0.6;
}
.slide-presentation .layout-comparison .slide-block:nth-child(even) .learning-point::before {
    content: 'B';
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.65rem;
    font-weight: 700;
    width: 1.5rem;
    height: 1.5rem;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background: var(--slide-violet);
    color: #1e1b4b;
    opacity: 0.6;
}

/* ================================================
   LAYOUT: PROCESS — Vertical flow with connectors
   ================================================ */
.slide-presentation .layout-process .slide-blocks {
    gap: 0;
}
.slide-presentation .layout-process .block-item {
    position: relative;
    padding-bottom: 0.6rem;
}
.slide-presentation .layout-process .block-item:has(+ .block-item)::after {
    content: '';
    display: block;
    width: 2px;
    height: 2rem;
    margin: 0.3rem auto;
    background: linear-gradient(180deg, var(--slide-gradient-start), var(--slide-gradient-end));
    border-radius: 1px;
}
.slide-presentation .layout-process .block-item:has(+ .block-item) .learning-point::before {
    content: '↓';
    position: absolute;
    bottom: -1.6rem;
    left: 50%;
    transform: translateX(-50%);
    color: var(--slide-accent);
    font-size: 1rem;
    z-index: 1;
    display: none; /* hidden by default, shown on desktop horizontal */
}
.slide-presentation .step-marker {
    display: grid;
    place-items: center;
    width: 2.4rem;
    height: 2.4rem;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--slide-indigo), #7c3aed);
    border-radius: var(--slide-radius-sm);
    font-size: 0.9rem;
    font-weight: 700;
    color: white;
    box-shadow: 0 4px 12px -2px #4f46e566;
}
.slide-presentation .layout-process .learning-point {
    align-items: center;
    border-color: #6366f155;
    position: relative;
}

/* ================================================
   LAYOUT: CODE — Code + explanation side by side
   ================================================ */
.slide-presentation .layout-code .slide-blocks,
.slide-presentation .layout-image-focus .slide-blocks {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
}
.slide-presentation .block-code,
.slide-presentation .block-image,
.slide-presentation .block-table,
.slide-presentation .block-heading,
.slide-presentation .block-callout {
    grid-column: 1 / -1;
}
.slide-presentation .layout-code .block-code:first-child,
.slide-presentation .layout-image-focus .block-image:first-child {
    grid-column: 1;
    grid-row: span 3;
}

/* --- Code Panel --- */
.slide-presentation .code-panel {
    overflow: hidden;
    border: 1px solid #475569;
    border-radius: var(--slide-radius);
    background: #080e1c;
    box-shadow: 0 8px 32px -8px #0008;
}
.slide-presentation .code-panel figcaption {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    background: var(--slide-surface-raised);
    padding: 0.8rem 1.2rem;
    color: #c7d2fe;
    font-size: 0.8rem;
    border-bottom: 1px solid #334155;
}
.slide-presentation .code-panel figcaption::before {
    content: '';
    display: flex;
    gap: 0.4rem;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 999px;
    background: #ef4444;
    box-shadow: 0.75rem 0 0 #eab308, 1.5rem 0 0 #22c55e;
}
.slide-presentation .code-panel pre {
    overflow: auto;
    max-height: 65vh;
    padding: 1.5rem;
    white-space: pre;
    font-size: 0.95rem;
    line-height: 1.9;
    color: #c7d2fe;
    tab-size: 4;
}

/* ================================================
   LAYOUT: VISUAL — Image + explanation
   ================================================ */
.slide-presentation .slide-image {
    border: 1px solid var(--slide-border);
    border-radius: var(--slide-radius);
    overflow: hidden;
    background: var(--slide-surface);
}
.slide-presentation .slide-image img {
    width: 100%;
    max-height: 65vh;
    object-fit: contain;
}
.slide-presentation .slide-image figcaption {
    padding: 1rem;
    color: #cbd5e1;
    font-size: 0.85rem;
}

/* ================================================
   LAYOUT: EXAMPLE — Purple left border accent
   ================================================ */
.slide-presentation .layout-example .slide-composition {
    border-left: 3px solid #a78bfa;
    padding-left: 1.5rem;
}
.slide-presentation .layout-example .learning-point {
    background: #19152e;
    border-color: #6d28d944;
}
.slide-presentation .layout-example .slide-heading::after {
    content: '💡';
    display: inline-block;
    margin-left: 0.5rem;
    font-size: 1.2rem;
}

/* ================================================
   LAYOUT: SUMMARY — Clean checklist
   ================================================ */
.slide-presentation .layout-summary .slide-heading h2 {
    color: #c4b5fd;
}
.slide-presentation .layout-summary .learning-point {
    border: 0;
    border-bottom: 1px solid var(--slide-border);
    border-radius: 0;
    background: none;
    padding: 1.2rem 0.25rem;
}
.slide-presentation .layout-summary .learning-point:hover {
    box-shadow: none;
    border-color: var(--slide-accent-soft);
}
.slide-presentation .layout-summary .slide-blocks {
    gap: 0;
    max-width: 850px;
}
.slide-presentation .layout-summary .point-icon {
    color: #34d399;
}

/* ================================================
   LAYOUT: CHECKPOINT — Interactive quiz-like
   ================================================ */
.slide-presentation .layout-checkpoint .slide-composition {
    border: 1px solid #6366f166;
    border-radius: 1.25rem;
    padding: clamp(1rem, 3vw, 2rem);
    background: #1e1b4b33;
}
.slide-presentation .layout-checkpoint .slide-heading h2 {
    color: #a5b4fc;
}

/* ================================================
   LAYOUT: QUOTE — Large centered text
   ================================================ */
.slide-presentation .slide-quote {
    font-size: clamp(1.3rem, 2.3vw, 2rem);
    font-weight: 500;
    line-height: 1.7;
    border-left: 3px solid #a78bfa;
    padding: 1rem 2rem;
    white-space: pre-wrap;
    color: #ddd6fe;
}

/* ================================================
   LAYOUT: READING — Default segmented text
   ================================================ */
.slide-presentation .layout-reading .slide-blocks {
    max-width: 850px;
}
.slide-presentation .layout-reading .learning-point {
    border-left: 3px solid var(--slide-accent-soft);
    border-radius: 0 var(--slide-radius) var(--slide-radius) 0;
}

/* --- Callout --- */
.slide-presentation .learning-callout {
    border-left: 3px solid var(--slide-gradient-start);
    background: #312e8133;
    border-radius: 0 var(--slide-radius-sm) var(--slide-radius-sm) 0;
    padding: 1.2rem 1.5rem;
    position: relative;
}
.slide-presentation .learning-callout h3 {
    margin-bottom: 0.7rem;
}
.slide-presentation .learning-callout[data-label="PENTING"] {
    border-left-color: #ef4444;
    background: #7f1d1d22;
}
.slide-presentation .learning-callout[data-label="TIPS"] {
    border-left-color: #22c55e;
    background: #14532d22;
}
.slide-presentation .learning-callout[data-label="CONTOH"] {
    border-left-color: #a78bfa;
    background: #312e8133;
}
.slide-presentation .learning-callout[data-label="INGAT"],
.slide-presentation .learning-callout[data-label="PERHATIKAN"] {
    border-left-color: #eab308;
    background: #713f1222;
}

/* --- Table / Comparison scroll --- */
.slide-presentation .comparison-scroll {
    overflow: auto;
    border: 1px solid #475569;
    border-radius: var(--slide-radius);
}
.slide-presentation table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 1rem;
}
.slide-presentation th,
.slide-presentation td {
    padding: 1.1rem 1.3rem;
    min-width: 150px;
    border-bottom: 1px solid var(--slide-border);
    vertical-align: top;
    white-space: pre-wrap;
}
.slide-presentation th {
    background: #312e8166;
    color: #c7d2fe;
    font-weight: 700;
}
.slide-presentation td {
    background: var(--slide-surface);
    line-height: 1.7;
}
.slide-presentation tr:hover td {
    background: var(--slide-surface-raised);
}

/* ================================================
   LAYOUT: COVER & SECTION-DIVIDER
   ================================================ */
.slide-presentation .layout-cover .presentation-slide,
.slide-presentation .layout-section-divider .presentation-slide {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    min-height: 50vh;
}
.slide-presentation .layout-cover .slide-heading h2,
.slide-presentation .layout-closing .slide-heading h2 {
    font-size: clamp(3rem, 6vw, 5rem);
    background: linear-gradient(135deg, var(--slide-text-heading), var(--slide-violet));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    max-width: 20ch;
    margin: 0 auto;
}
.slide-presentation .layout-section-divider .slide-heading h2 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    color: var(--slide-accent);
}
.slide-presentation .layout-cover .slide-subtitle,
.slide-presentation .layout-section-divider .slide-subtitle,
.slide-presentation .layout-closing .slide-subtitle {
    margin: 1.5rem auto 0;
    max-width: 40ch;
}
.slide-presentation .layout-cover .slide-heading,
.slide-presentation .layout-section-divider .slide-heading,
.slide-presentation .layout-closing .slide-heading {
    margin-bottom: 2rem;
    width: 100%;
}
.slide-presentation .layout-cover .slide-heading .flex,
.slide-presentation .layout-section-divider .slide-heading .flex,
.slide-presentation .layout-closing .slide-heading .flex {
    justify-content: center;
}

/* ================================================
   LAYOUT: CLOSING
   ================================================ */
.slide-presentation .layout-closing .presentation-slide {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    min-height: 50vh;
}

/* ================================================
   LAYOUT: TIMELINE
   ================================================ */
.slide-presentation .layout-timeline .slide-blocks {
    border-left: 3px solid var(--slide-violet);
    padding-left: 1.5rem;
    margin-left: 1rem;
    gap: 2rem;
}
.slide-presentation .layout-timeline .learning-point {
    position: relative;
    border-radius: var(--slide-radius);
    background: var(--slide-surface-raised);
}
.slide-presentation .layout-timeline .learning-point::before {
    content: '';
    position: absolute;
    left: -2.3rem;
    top: 1.5rem;
    width: 1rem;
    height: 1rem;
    border-radius: 999px;
    background: var(--slide-violet);
    border: 3px solid var(--slide-bg);
}

/* ================================================
   LAYOUT: DEFINITION / CASE STUDY
   ================================================ */
.slide-presentation .layout-definition .learning-point,
.slide-presentation .layout-case-study .learning-point {
    border-left: 4px solid var(--slide-gradient-end);
}
.slide-presentation .layout-definition .point-label,
.slide-presentation .layout-case-study .point-label {
    color: var(--slide-gradient-end);
    font-size: 1.3rem;
}

/* --- Takeaway / Summary aside --- */
.slide-presentation .slide-takeaway {
    margin-top: 2rem;
    padding: 1rem 0 1rem 1.25rem;
    border-left: 2px solid var(--slide-gradient-start);
    max-width: 85ch;
}
.slide-presentation .slide-takeaway p {
    margin-top: 0.5rem;
    line-height: 1.8;
    color: #cbd5e1;
    white-space: pre-wrap;
}

/* --- Source Text Toggle --- */
.slide-presentation .slide-source {
    margin-top: 1.5rem;
    color: var(--slide-text-muted);
    font-size: 0.8rem;
    line-height: 1.8;
}
.slide-presentation .slide-source summary {
    cursor: pointer;
    min-height: 44px;
    align-content: center;
    width: fit-content;
}
.slide-presentation .slide-source[open] {
    color: #cbd5e1;
}
.slide-presentation .slide-source > div {
    padding: 1rem;
    border: 1px solid var(--slide-border);
    border-radius: var(--slide-radius-sm);
}

/* --- Navigation Footer --- */
.slide-presentation .presentation-navigation {
    position: sticky;
    bottom: 0;
    z-index: 10;
    background: #0f172af5;
    backdrop-filter: blur(8px);
}
.slide-presentation .slide-select {
    max-width: 100%;
    background: var(--slide-surface);
    color: var(--slide-text);
    border: 0;
    padding: 0.6rem 0.25rem;
    font-size: 0.85rem;
    min-height: 44px;
    border-radius: 0.5rem;
}

/* --- Fullscreen --- */
.slide-presentation:fullscreen {
    overflow: auto;
}

/* ================================================
   ANIMATIONS
   ================================================ */
@keyframes slide-arrive {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes block-reveal {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes connector-draw {
    from { transform: scaleY(0); }
    to   { transform: scaleY(1); }
}

/* ================================================
   RESPONSIVE: DESKTOP WIDE (>= 1000px)
   ================================================ */
@media (min-width: 1000px) {
    /* Process: horizontal flow for <=3 items */
    .slide-presentation .layout-process .slide-blocks:has(> .block-item:first-child):not(:has(> :not(.block-item))):not(:has(> .block-item:nth-child(4))) {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.5rem;
    }
    .slide-presentation .layout-process .slide-blocks:not(:has(> .block-item:nth-child(4))) .learning-point {
        flex-direction: column;
        align-items: flex-start;
        text-align: center;
    }
    .slide-presentation .layout-process .slide-blocks:not(:has(> .block-item:nth-child(4))) .block-item:has(+ .block-item)::after {
        display: none;
    }
    .slide-presentation .layout-process .slide-blocks:not(:has(> .block-item:nth-child(4))) .block-item:has(+ .block-item) .learning-point::after {
        content: '→';
        position: absolute;
        right: -1.2rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--slide-accent);
        font-size: 1.4rem;
    }
}

/* ================================================
   RESPONSIVE: MOBILE (<= 640px)
   ================================================ */
@media (max-width: 640px) {
    .slide-presentation .layout-comparison .slide-blocks:has(> .block-item:nth-child(3)):not(:has(> .block-item:nth-child(4))),
    .slide-presentation .layout-key-points .slide-blocks,
    .slide-presentation .layout-key-points .slide-blocks:has(> .block-item:nth-child(3)):not(:has(> .block-item:nth-child(4))),
    .slide-presentation .layout-comparison .slide-blocks,
    .slide-presentation .layout-code .slide-blocks,
    .slide-presentation .layout-image-focus .slide-blocks {
        grid-template-columns: minmax(0, 1fr);
    }
    .slide-presentation .layout-concept .slide-composition {
        grid-template-columns: minmax(0, 1fr);
        padding: 0;
        gap: 1.5rem;
    }
    .slide-presentation .concept-mark {
        width: 85px;
        border-radius: 1.2rem;
    }
    .slide-presentation .concept-mark svg {
        width: 36px;
    }
    .slide-presentation .layout-code .block-code:first-child,
    .slide-presentation .layout-image-focus .block-image:first-child {
        grid-column: auto;
        grid-row: auto;
    }
    .slide-presentation .learning-point {
        padding: 1.2rem;
        gap: 0.75rem;
    }
    .slide-presentation .presentation-navigation {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .slide-presentation .presentation-navigation > div {
        order: -1;
        flex-basis: 100%;
    }
    .slide-presentation .presentation-navigation .presentation-button {
        font-size: 0.8rem;
        padding: 0.6rem 0.8rem;
    }
    .slide-presentation .layout-example .slide-composition {
        padding-left: 0.8rem;
    }
    .slide-presentation .slide-heading h2 {
        font-size: clamp(1.4rem, 5vw, 1.8rem);
    }
    .slide-presentation .signal-bar {
        gap: 0.35rem;
    }
    .slide-presentation .signal-badge {
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
    }
    .slide-presentation .layout-comparison .learning-point::before {
        display: none;
    }
}

/* ================================================
   ACCESSIBILITY: REDUCED MOTION
   ================================================ */
@media (prefers-reduced-motion: reduce) {
    .slide-presentation *,
    .slide-presentation *::before,
    .slide-presentation *::after {
        animation-duration: 0.01ms !important;
        animation-delay: 0ms !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
</style>
