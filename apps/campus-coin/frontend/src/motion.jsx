/* ============================================================================
   MOTION KIT — vanilla only (CSS + IntersectionObserver + requestAnimationFrame)
   ----------------------------------------------------------------------------
   Every effect here is opt-in: the resting state of anything animated is the
   normal, fully visible UI. Motion only exists while <html> carries the
   `cc-motion` class, which is added when prefers-reduced-motion is NOT set.
   If this script never runs, the page renders exactly as it does today.
   ========================================================================= */

import { useEffect, useMemo, useRef, useState } from 'react'
import { useLocation } from 'react-router-dom'

export const MOTION_CLASS = 'cc-motion'

/* ------------------------------------------------------------- motion gate */

export function motionAllowed() {
  if (typeof window === 'undefined' || !window.matchMedia) return false
  try {
    return !window.matchMedia('(prefers-reduced-motion: reduce)').matches
  } catch {
    return false
  }
}

/** Adds the opt-in class to <html> and keeps it in sync with the OS setting. */
export function useMotion() {
  const [on, setOn] = useState(false)

  useEffect(() => {
    const root = document.documentElement
    if (!root.classList.contains(MOTION_CLASS) && motionAllowed()) root.classList.add(MOTION_CLASS)
    setOn(root.classList.contains(MOTION_CLASS))

    let mq
    try {
      mq = window.matchMedia('(prefers-reduced-motion: reduce)')
    } catch {
      return undefined
    }
    const sync = () => {
      root.classList.toggle(MOTION_CLASS, !mq.matches)
      setOn(!mq.matches)
    }
    if (typeof mq.addEventListener === 'function') mq.addEventListener('change', sync)
    else if (typeof mq.addListener === 'function') mq.addListener(sync)
    return () => {
      if (typeof mq.removeEventListener === 'function') mq.removeEventListener('change', sync)
      else if (typeof mq.removeListener === 'function') mq.removeListener(sync)
    }
  }, [])

  return on
}

/* --------------------------------------------------------- background stage */

const BASE = import.meta.env.BASE_URL || '/'
const bgUrl = (file) => `${BASE}bg/${file}`

const IMAGES = [
  ['lagos', 'bg-lagos-night.jpg'],
  ['campus', 'bg-campus.jpg'],
  ['desk', 'bg-desk.jpg'],
  ['graphite', 'bg-gradient.jpg'],
]

const ROUTE_BG = {
  '/': 'lagos',
  '/login': 'desk',
  '/register': 'desk',
  '/reset-password': 'desk',
  '/dashboard': 'lagos',
  '/transactions': 'campus',
  '/budgets': 'graphite',
  '/goals': 'campus',
  '/splits': 'desk',
  '/heatmap': 'graphite',
  '/forecast': 'campus',
  '/roommate': 'graphite',
  '/simulator': 'lagos',
  '/reports': 'lagos',
  '/tips': 'campus',
  '/categories': 'desk',
  '/profile': 'desk',
  '/admin': 'lagos',
}

export function bgKeyFor(pathname) {
  return ROUTE_BG[pathname] || 'graphite'
}

/**
 * One fixed stage for the whole app. All four plates are mounted once so a
 * route change is a pure crossfade (no decode, no 404 race); the active plate
 * gets the Ken Burns drift, then a scrim and a grain pass keep contrast AA.
 */
export function BgStage() {
  const location = useLocation()
  const active = useMemo(() => bgKeyFor(location.pathname), [location.pathname])

  useEffect(() => {
    document.documentElement.dataset.ccBg = active
    return () => {
      delete document.documentElement.dataset.ccBg
    }
  }, [active])

  return (
    <div className="cc-bgstage" aria-hidden="true" data-bg={active}>
      {IMAGES.map(([key, file]) => (
        <div key={key} className={`cc-bglayer ${key === active ? 'is-on' : ''}`.trim()}>
          <i style={{ backgroundImage: `url('${bgUrl(file)}')` }} />
        </div>
      ))}
      <div className="cc-bgscrim" />
      <div className="cc-bggrain" />
    </div>
  )
}

/* ------------------------------------------------- curtain-wipe reveal scan */

const REVEAL_SELECTORS = [
  '.cc-card',
  '.cc-stat',
  '.cc-feature',
  '.cc-step',
  '.cc-cta-band',
  '.cc-mock',
  '.cc-tx',
  '.cc-upcoming-row',
  '.cc-budget-card',
  '.cc-goal',
  '.cc-auth-card',
  '.cc-page-head',
  '.cc-mk-section-head',
  '.cc-hero-mk-copy',
  '.cc-trust',
  '.metric',
  '.report-panel',
  '.split-card',
  '.tip-tile',
  '.jar',
  '.forecast-card',
  '.sim-out > div',
  '.auth-card',
  '.breakdown-card',
].join(', ')

const GLOW_SELECTORS = [
  '.cc-card',
  '.cc-stat',
  '.cc-feature',
  '.cc-cta-band',
  '.cc-mock',
  '.cc-budget-card',
  '.cc-goal',
  '.metric',
  '.report-panel',
  '.split-card',
  '.tip-tile',
  '.jar',
  '.forecast-card',
].join(', ')

const NEVER = ['.cc-modal-backdrop', '.cc-toasts', '.cc-pop', '.cc-scrim', '.cc-skel-stack', '.cc-bgstage']

function insideNever(el) {
  return NEVER.some((sel) => el.closest(sel))
}

/**
 * Tags structural blocks with data-reveal (staggered curtain wipe) and
 * data-glow (pointer specular), then reveals them as they enter the viewport.
 * Untagged / unrevealed elements are simply visible, so this is additive.
 */
export function useRevealScan() {
  const on = useMotion()
  const location = useLocation()

  useEffect(() => {
    if (!on) return undefined
    const root = document.getElementById('root')
    if (!root) return undefined

    const observer =
      typeof IntersectionObserver === 'undefined'
        ? null
        : new IntersectionObserver(
            (entries) => {
              entries.forEach((entry) => {
                if (!entry.isIntersecting) return
                entry.target.classList.add('cc-in')
                observer.unobserve(entry.target)
              })
            },
            { rootMargin: '0px 0px -6% 0px', threshold: 0.05 },
          )

    // Safety net for the observer: IntersectionObserver entries are only
    // delivered on rendered frames, so anything tagged but not yet wiped in is
    // re-checked on scroll. Cheap (the selector matches nothing once revealed)
    // and it is what keeps content visible where IO is unavailable.
    const sweep = () => {
      const vh = window.innerHeight || 0
      root.querySelectorAll('[data-reveal]:not(.cc-in)').forEach((el) => {
        const rect = el.getBoundingClientRect()
        if (rect.height > 0 && rect.top < vh * 0.94 && rect.bottom > 0) el.classList.add('cc-in')
      })
    }

    const tag = () => {
      const seen = new Map()
      const vh = window.innerHeight || 0
      const vw = window.innerWidth || 0
      root.querySelectorAll(REVEAL_SELECTORS).forEach((el) => {
        // Already wiped in, or inside a layer that must never animate.
        if (el.classList.contains('cc-in') || insideNever(el)) return
        const parent = el.parentElement
        const index = parent ? (seen.get(parent) || 0) + 1 : 1
        if (parent) seen.set(parent, index)
        if (!el.hasAttribute('data-reveal')) {
          el.setAttribute('data-reveal', '')
          el.style.setProperty('--rd', String(Math.min(index - 1, 9)))
          if (!el.hasAttribute('data-glow') && el.matches(GLOW_SELECTORS)) el.setAttribute('data-glow', '')
        }
        const rect = el.getBoundingClientRect()
        const visible = rect.width > 0 && rect.height > 0 && rect.top < vh * 0.98 && rect.bottom > 0 && rect.left < vw && rect.right > 0
        if (visible) el.classList.add('cc-in')
        // Re-observing an element this observer already watches is a no-op, so
        // this also repairs elements tagged by an earlier pass (StrictMode
        // double-invoke, or a route change that replaced the observer).
        else if (observer) observer.observe(el)
      })
    }

    // Microtask, not rAF: the tag has to land before the browser paints the
    // new node, otherwise a freshly loaded card flashes then rewinds.
    let queued = false
    const schedule = () => {
      if (queued) return
      queued = true
      Promise.resolve().then(() => {
        queued = false
        tag()
      })
    }

    schedule()
    const mutation = new MutationObserver(schedule)
    mutation.observe(root, { childList: true, subtree: true })

    let last = 0
    const onScroll = () => {
      const now = Date.now()
      if (now - last < 90) return
      last = now
      sweep()
    }
    window.addEventListener('scroll', onScroll, { passive: true })
    window.addEventListener('resize', onScroll)

    return () => {
      mutation.disconnect()
      if (observer) observer.disconnect()
      window.removeEventListener('scroll', onScroll)
      window.removeEventListener('resize', onScroll)
    }
  }, [on, location.pathname])
}

/* --------------------------------------------------- pointer specular glow */

export function usePointerGlow() {
  const on = useMotion()

  useEffect(() => {
    if (!on) return undefined
    let frame = 0
    let last = null
    let lit = null

    const clear = () => {
      if (!lit) return
      lit.classList.remove('is-lit')
      lit.style.removeProperty('--mx')
      lit.style.removeProperty('--my')
      lit = null
    }

    const apply = () => {
      frame = 0
      if (!last) return
      const node = last.target && last.target.nodeType === 1 ? last.target : null
      const el = node && node.closest ? node.closest('[data-glow]') : null
      if (el !== lit) {
        clear()
        if (!el) return
        lit = el
        el.classList.add('is-lit')
      }
      const rect = el.getBoundingClientRect()
      if (!rect.width || !rect.height) return
      el.style.setProperty('--mx', `${(((last.clientX - rect.left) / rect.width) * 100).toFixed(2)}%`)
      el.style.setProperty('--my', `${(((last.clientY - rect.top) / rect.height) * 100).toFixed(2)}%`)
    }

    const move = (event) => {
      last = event
      if (!frame) frame = requestAnimationFrame(apply)
    }

    window.addEventListener('pointermove', move, { passive: true })
    window.addEventListener('blur', clear)
    document.addEventListener('pointerleave', clear)
    return () => {
      if (frame) cancelAnimationFrame(frame)
      window.removeEventListener('pointermove', move)
      window.removeEventListener('blur', clear)
      document.removeEventListener('pointerleave', clear)
      clear()
    }
  }, [on])
}

/* ------------------------------------------------------ scroll progress bar */

export function ScrollProgress() {
  const barRef = useRef(null)
  const on = useMotion()

  useEffect(() => {
    if (!on) return undefined
    if (typeof CSS !== 'undefined' && CSS.supports && CSS.supports('animation-timeline: scroll(root)')) return undefined
    let frame = 0
    const write = () => {
      frame = 0
      const doc = document.documentElement
      const max = doc.scrollHeight - doc.clientHeight
      const ratio = max > 4 ? Math.min(1, Math.max(0, doc.scrollTop / max)) : 0
      if (barRef.current) barRef.current.style.setProperty('--p', ratio.toFixed(4))
    }
    const onScroll = () => {
      if (!frame) frame = requestAnimationFrame(write)
    }
    write()
    window.addEventListener('scroll', onScroll, { passive: true })
    window.addEventListener('resize', onScroll)
    return () => {
      if (frame) cancelAnimationFrame(frame)
      window.removeEventListener('scroll', onScroll)
      window.removeEventListener('resize', onScroll)
    }
  }, [on])

  if (!on) return null
  return <div className="cc-progress" ref={barRef} aria-hidden="true" />
}

/* ------------------------------------------------------- decode/scramble text */

function poolFor(text) {
  const chars = Array.from(text)
  return chars.filter((ch) => ch !== ' ')
}

/**
 * Decode/scramble settle. The random glyphs are drawn from the target string
 * itself, so the line never changes width while it settles — no reflow, no
 * layout jump in a data-dense UI.
 */
export function Scramble({ text = '', as: Tag = 'span', className = '', duration = 700, delay = 0 }) {
  const on = useMotion()
  const [shown, setShown] = useState(text)
  const cache = useRef(null)

  useEffect(() => {
    if (!on) {
      setShown(text)
      return undefined
    }
    const chars = Array.from(text)
    if (!cache.current || cache.current.key !== text) {
      cache.current = { key: text, pool: poolFor(text) }
    }
    const pool = cache.current.pool.length ? cache.current.pool : ['•', '—', '/']
    const start = performance.now() + delay
    let frame = 0

    const tick = (now) => {
      if (now < start) {
        frame = requestAnimationFrame(tick)
        return
      }
      const progress = Math.min(1, (now - start) / duration)
      const eased = 1 - (1 - progress) ** 2.4
      const settled = Math.floor(eased * chars.length)
      setShown(
        chars
          .map((ch, index) => {
            if (ch === ' ' || index < settled) return ch
            return pool[(Math.random() * pool.length) | 0]
          })
          .join(''),
      )
      if (progress < 1) frame = requestAnimationFrame(tick)
      else setShown(text)
    }

    frame = requestAnimationFrame(tick)
    return () => {
      if (frame) cancelAnimationFrame(frame)
      setShown(text)
    }
  }, [text, on, duration, delay])

  return <Tag className={`cc-scramble ${className}`.trim()}>{shown}</Tag>
}

/* ------------------------------------------------------------- digit roll ---

  Wraps an already-formatted display string (money, "3 / 8", "42 days") so each
  digit settles into place. The readable value is exposed once, to screen
  readers, through the visually-hidden copy.
-------------------------------------------------------------------------- */

export function Roll({ text, className = '' }) {
  const value = String(text ?? '')
  const chars = useMemo(() => Array.from(value), [value])

  return (
    <span className={`cc-roll ${className}`.trim()}>
      <span className="cc-sr">{value}</span>
      <span className="cc-roll-face" aria-hidden="true">
        {chars.map((ch, index) =>
          /[0-9]/.test(ch) ? (
            <b key={index} className="cc-roll-d" style={{ '--ri': index }}>
              {ch}
            </b>
          ) : (
            <b key={index} className="cc-roll-x">
              {ch === ' ' ? ' ' : ch}
            </b>
          ),
        )}
      </span>
    </span>
  )
}

/* ------------------------------------------------------------- marquee strip */

export function Ticker({ label = '', items = [] }) {
  if (!items.length) return null
  return (
    <div className="cc-ticker">
      {label && <p className="cc-ticker-label">{label}</p>}
      <div className="cc-ticker-view">
        <div className="cc-ticker-rail">
          {[0, 1].map((copy) => (
            <div className="cc-ticker-set" key={copy} aria-hidden={copy === 1 ? 'true' : undefined}>
              {items.map((item, index) => (
                <span className="cc-ticker-item" key={`${copy}-${index}`}>
                  {item}
                </span>
              ))}
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

/* ---------------------------------------------------------- rotating conic ring */

export function PctRing({ value = 0, children, tone = '' }) {
  const ratio = Math.max(0, Math.min(100, Number(value) || 0))
  const mod = tone ? ` cc-pctring--${tone}` : ''
  return (
    <span className={`cc-pctring${mod}`.trim()} style={{ '--p': `${ratio}%` }}>
      <span className="cc-pctring-face">{children}</span>
    </span>
  )
}
