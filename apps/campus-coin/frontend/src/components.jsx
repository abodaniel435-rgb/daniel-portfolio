import { createContext, useCallback, useContext, useEffect, useId, useMemo, useRef, useState } from 'react'
import { cash, dateLabel, money, shortMoney } from './currency'

export { cash, money, shortMoney, dateLabel }

/* ------------------------------------------------------------------ helpers */

export function asList(data) {
  if (Array.isArray(data)) return data
  if (Array.isArray(data?.results)) return data.results
  return []
}

export const pct = (value, max) => {
  const limit = Number(max) || 0
  const used = Number(value) || 0
  if (limit <= 0) return 0
  return Math.max(0, Math.min(100, (used / limit) * 100))
}

/* -------------------------------------------------------------------- icons */

const ICONS = {
  home: 'M3 10.5 12 3l9 7.5M5.5 9.5V20h13V9.5',
  list: 'M4 6h16M4 12h16M4 18h10',
  target: 'M12 3v3m0 12v3m9-9h-3M6 12H3m12.5 0a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z',
  wallet: 'M3 8.5A2.5 2.5 0 0 1 5.5 6H19v12H5.5A2.5 2.5 0 0 1 3 15.5v-7Zm0 0A2.5 2.5 0 0 1 5.5 6M16 12h2',
  user: 'M4.5 20a7.5 7.5 0 0 1 15 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
  plus: 'M12 5v14M5 12h14',
  search: 'M10.5 17a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13Zm4.8.3L20 22',
  close: 'M6 6l12 12M18 6 6 18',
  arrow: 'M5 12h13m-5-6 6 6-6 6',
  chevronLeft: 'M14 6 8 12l6 6',
  chevronRight: 'M10 6l6 6-6 6',
  check: 'M4.5 12.5 9 17l10-10',
  alert: 'M12 8v5m0 3.5v.5M10.3 4.2 2.8 17.4A2 2 0 0 0 4.5 20.5h15a2 2 0 0 0 1.7-3.1L13.7 4.2a2 2 0 0 0-3.4 0Z',
  spark: 'M12 3v4m0 10v4M3 12h4m10 0h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18',
  logout: 'M15 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19H15m1-4.5 4-3.5-4-3.5m4 3.5H10',
  menu: 'M4 7h16M4 12h16M4 17h16',
  calendar: 'M7 3v3m10-3v3M4 8h16M5 6h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z',
  trend: 'M4 17l5-6 4 3 6-8m0 0h-4m4 0v4',
  jar: 'M8 4h8M6 8h12v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V8Zm3 3h6',
  filter: 'M4 6h16M7 12h10m-7 6h4',
  card: 'M3 7.5A1.5 1.5 0 0 1 4.5 6h15A1.5 1.5 0 0 1 21 7.5v9A1.5 1.5 0 0 1 19.5 18h-15A1.5 1.5 0 0 1 3 16.5v-9Zm0 3h18',
}

export function Icon({ name, size = 18, strokeWidth = 1.7, className = '' }) {
  const path = ICONS[name]
  if (!path) return null
  return (
    <svg
      className={className}
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
    >
      <path d={path} />
    </svg>
  )
}

/* --------------------------------------------------------------------- money */

export function Money({ value, decimals = 2, signed = false, className = '' }) {
  return <span className={`cc-num ${className}`.trim()}>{money(value, { decimals, signed })}</span>
}

export function CountUp({ value }) {
  const target = Number(value) || 0
  const reduce =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const [shown, setShown] = useState(reduce ? target : 0)

  useEffect(() => {
    if (reduce) {
      setShown(target)
      return undefined
    }
    const start = performance.now()
    let frame = 0
    const tick = (now) => {
      const progress = Math.min(1, (now - start) / 900)
      const eased = 1 - (1 - progress) ** 3
      setShown(target * eased)
      if (progress < 1) frame = requestAnimationFrame(tick)
    }
    frame = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(frame)
  }, [target, reduce])

  return <span>{cash(shown)}</span>
}

/* ------------------------------------------------------------------- layout */

export function PageHead({ eyebrow, title, subtitle, action }) {
  return (
    <header className="cc-page-head">
      <div>
        {eyebrow && <p className="cc-eyebrow">{eyebrow}</p>}
        <h1 className="cc-h1" style={{ marginTop: 6 }}>
          {title}
        </h1>
        {subtitle && <p className="cc-sub">{subtitle}</p>}
      </div>
      {action}
    </header>
  )
}

export function Card({ as: Tag = 'section', className = '', children, ...rest }) {
  return (
    <Tag className={`cc-card ${className}`.trim()} {...rest}>
      {children}
    </Tag>
  )
}

export function CardHead({ eyebrow, title, action }) {
  return (
    <div className="cc-card-head">
      <div>
        {eyebrow && <p className="cc-eyebrow">{eyebrow}</p>}
        {title && <h2 className="cc-h2">{title}</h2>}
      </div>
      {action}
    </div>
  )
}

export function StatTile({ label, value, meta, tone = '', icon }) {
  return (
    <article className={`cc-stat ${tone ? `cc-stat--${tone}` : ''}`.trim()}>
      <p className="cc-stat-label">{label}</p>
      <p className="cc-stat-value">{value}</p>
      {meta && <p className="cc-stat-meta">{meta}</p>}
      {icon && <span className="cc-sr">{icon}</span>}
    </article>
  )
}

export function Pill({ children, tone = '', dot = false }) {
  return (
    <span className={`cc-pill ${tone ? `cc-pill--${tone}` : ''}`.trim()}>
      {dot && <i className="cc-dot" />}
      {children}
    </span>
  )
}

/* ------------------------------------------------------------------- meters */

export function Meter({ label, value, max, note, tone, compact = false }) {
  const ratio = pct(value, max)
  const level = tone || (ratio >= 100 ? 'over' : ratio >= 80 ? 'warn' : '')
  return (
    <div className="cc-meter">
      <div className="cc-meter-head">
        <b>{label}</b>
        <span>
          {money(value, { decimals: 0 })} / {money(max, { decimals: 0 })}
        </span>
      </div>
      <div
        className="cc-meter-track"
        role="progressbar"
        aria-label={`${label} budget used`}
        aria-valuenow={Math.round(ratio)}
        aria-valuemin="0"
        aria-valuemax="100"
      >
        <i className={`cc-meter-fill ${level}`.trim()} style={{ width: `${Math.max(ratio, 1)}%` }} />
      </div>
      {!compact && note && <p className="cc-meter-note">{note}</p>}
    </div>
  )
}

/* ------------------------------------------------------------ transactions */

export function TransactionRow({ item, onEdit, onDelete, busy, manage = false }) {
  const income = item.type === 'income'
  return (
    <div className={`cc-tx ${manage ? 'cc-tx--manage' : ''}`.trim()}>
      <span className={`cc-tx-icon ${income ? 'in' : ''}`.trim()} aria-hidden="true">
        {(item.category_name || '?').slice(0, 1)}
      </span>
      <div className="cc-tx-body">
        <b>{item.description || item.category_name}</b>
        <small>
          {item.category_name} · {dateLabel(item.transaction_date)}
          {item.is_recurring ? ' · monthly' : ''}
        </small>
      </div>
      <span className={`cc-tx-amount ${income ? 'in' : 'out'}`}>
        {money(income ? Number(item.amount) : -Number(item.amount), { signed: true })}
      </span>
      {(onEdit || onDelete) && (
        <div className="cc-tx-actions">
          {onEdit && (
            <button type="button" onClick={() => onEdit(item)} aria-label={`Edit ${item.description || item.category_name}`}>
              Edit
            </button>
          )}
          {onDelete && (
            <button
              type="button"
              onClick={() => onDelete(item)}
              disabled={busy === item.id}
              aria-label={`Delete ${item.description || item.category_name}`}
            >
              {busy === item.id ? '…' : 'Delete'}
            </button>
          )}
        </div>
      )}
    </div>
  )
}

/* -------------------------------------------------------------------- chart */

export function BarChart({ rows = [], labelKey = 'category__name', valueKey = 'total', height = 148, empty }) {
  const clean = rows.filter((row) => Number(row[valueKey]) > 0)
  if (!clean.length) return empty || null
  const max = Math.max(...clean.map((row) => Number(row[valueKey])), 1)
  return (
    <div className="cc-chart">
      <div className="cc-bars" style={{ height }} role="img" aria-label="Spending by category">
        {clean.slice(0, 7).map((row, index) => {
          const label = row[labelKey] || row.label || `Item ${index + 1}`
          const value = Number(row[valueKey])
          return (
            <div className="cc-bars-col" key={`${label}-${index}`} title={`${label}: ${money(value)}`}>
              <div className="cc-bars-track">
                <i
                  className={`cc-bars-fill ${index === 0 ? '' : index % 2 ? 'amber' : 'dim'}`.trim()}
                  style={{
                    height: `${Math.max(6, (value / max) * 100)}%`,
                    animationDelay: `${index * 0.06}s`,
                  }}
                />
              </div>
              <span className="cc-bars-label">{shortMoney(value)}</span>
              <span className="cc-bars-label" style={{ color: 'var(--text-soft)' }}>
                {String(label).slice(0, 9)}
              </span>
            </div>
          )
        })}
      </div>
    </div>
  )
}

export function Ring({ value = 0, max = 100, label = 'score', size = 132, tone }) {
  const radius = size / 2 - 10
  const circumference = 2 * Math.PI * radius
  const ratio = Math.max(0, Math.min(1, (Number(value) || 0) / (Number(max) || 100)))
  const colour = tone || (ratio >= 0.75 ? 'var(--lime)' : ratio >= 0.5 ? 'var(--amber)' : 'var(--coral)')
  return (
    <div className="cc-ring" style={{ width: size, height: size }}>
      <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} aria-hidden="true">
        <circle cx={size / 2} cy={size / 2} r={radius} fill="none" stroke="var(--ink-700)" strokeWidth="9" />
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          fill="none"
          stroke={colour}
          strokeWidth="9"
          strokeLinecap="round"
          strokeDasharray={circumference}
          strokeDashoffset={circumference - circumference * ratio}
          style={{ transition: 'stroke-dashoffset .7s cubic-bezier(.22,1,.36,1)' }}
        />
      </svg>
      <div className="cc-ring-centre">
        <b>{Math.round(value)}</b>
        <small>{label}</small>
      </div>
    </div>
  )
}

/* -------------------------------------------------------------------- forms */

export function Field({ label, hint, error, htmlFor, className = '', children }) {
  return (
    <label className={`cc-field ${className}`.trim()} htmlFor={htmlFor}>
      {label && <span className="cc-label">{label}</span>}
      {children}
      {error ? (
        <span className="cc-error" role="alert">
          <Icon name="alert" size={13} /> {error}
        </span>
      ) : (
        hint && <span className="cc-hint">{hint}</span>
      )}
    </label>
  )
}

export function Segmented({ options, value, onChange, label }) {
  return (
    <div className="cc-seg" role="group" aria-label={label}>
      {options.map(([key, text]) => (
        <button
          key={key}
          type="button"
          aria-pressed={value === key}
          onClick={() => onChange(key)}
          className={value === key ? 'on' : ''}
        >
          {text}
        </button>
      ))}
    </div>
  )
}

/* ------------------------------------------------------------------- states */

export function Skeleton({ lines = 3 }) {
  return (
    <div className="cc-skel-stack" aria-busy="true" aria-live="polite">
      <span className="cc-sr">Loading your numbers…</span>
      {Array.from({ length: lines }, (_, index) => (
        <i
          key={index}
          className="cc-skel cc-skel-line"
          style={{ width: `${100 - index * 9}%`, height: index === 0 ? 22 : 12 }}
        />
      ))}
    </div>
  )
}

export function SkeletonCard({ lines = 4 }) {
  return (
    <div className="cc-card" aria-busy="true">
      <div className="cc-skel-stack">
        <i className="cc-skel cc-skel-line" style={{ width: '38%', height: 10 }} />
        <i className="cc-skel" style={{ width: '62%', height: 30 }} />
        <div style={{ height: 8 }} />
        {Array.from({ length: lines }, (_, index) => (
          <i key={index} className="cc-skel cc-skel-line" style={{ width: `${92 - index * 12}%` }} />
        ))}
      </div>
    </div>
  )
}

export function ErrorState({ message, onRetry }) {
  return (
    <div className="cc-card cc-empty" role="alert">
      <span className="cc-empty-art">
        <Icon name="alert" />
      </span>
      <h3>That did not load</h3>
      <p>{message}</p>
      {onRetry && (
        <button type="button" className="cc-btn cc-btn--primary cc-btn--sm" onClick={onRetry}>
          Try again
        </button>
      )}
    </div>
  )
}

export function EmptyState({ icon = 'spark', title, text, action, actionLabel, onAction }) {
  return (
    <div className="cc-empty">
      <span className="cc-empty-art">
        <Icon name={icon} size={22} />
      </span>
      <h3>{title}</h3>
      {text && <p>{text}</p>}
      {action ||
        (actionLabel && (
          <button type="button" className="cc-btn cc-btn--primary cc-btn--sm" onClick={onAction}>
            {actionLabel}
          </button>
        ))}
    </div>
  )
}

/* -------------------------------------------------------------------- modal */

export function Modal({ open, onClose, title, eyebrow, children, footer, labelledBy }) {
  const panelRef = useRef(null)
  const restoreRef = useRef(null)
  const autoId = useId()
  const titleId = labelledBy || autoId

  useEffect(() => {
    if (!open) return undefined
    restoreRef.current = document.activeElement
    const panel = panelRef.current
    const focusables = () =>
      Array.from(
        panel?.querySelectorAll(
          'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ) || [],
      )
    const first = focusables()[0]
    if (first) first.focus()
    else panel?.focus()

    const onKey = (event) => {
      if (event.key === 'Escape') {
        event.stopPropagation()
        onClose?.()
        return
      }
      if (event.key !== 'Tab') return
      const items = focusables()
      if (!items.length) return
      const start = items[0]
      const end = items[items.length - 1]
      if (event.shiftKey && document.activeElement === start) {
        event.preventDefault()
        end.focus()
      } else if (!event.shiftKey && document.activeElement === end) {
        event.preventDefault()
        start.focus()
      }
    }

    document.addEventListener('keydown', onKey)
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = previousOverflow
      if (restoreRef.current instanceof HTMLElement) restoreRef.current.focus()
    }
  }, [open, onClose])

  if (!open) return null

  return (
    <div className="cc-modal-backdrop" onMouseDown={(event) => event.target === event.currentTarget && onClose?.()}>
      <div className="cc-modal" role="dialog" aria-modal="true" aria-labelledby={titleId} ref={panelRef} tabIndex={-1}>
        <div className="cc-modal-head">
          <div>
            {eyebrow && <p className="cc-eyebrow">{eyebrow}</p>}
            <h2 className="cc-h2" id={titleId} style={{ marginTop: 4 }}>
              {title}
            </h2>
          </div>
          <button type="button" className="cc-iconbtn" onClick={onClose} aria-label="Close dialog">
            <Icon name="close" size={16} />
          </button>
        </div>
        <div className="cc-modal-body">{children}</div>
        {footer && <div className="cc-modal-foot">{footer}</div>}
      </div>
    </div>
  )
}

/* -------------------------------------------------------------------- toast */

const ToastContext = createContext(null)

export function ToastProvider({ children }) {
  const [items, setItems] = useState([])

  const dismiss = useCallback((id) => {
    setItems((current) => current.filter((item) => item.id !== id))
  }, [])

  const toast = useCallback(
    (payload) => {
      const id = `${Date.now()}-${Math.random().toString(16).slice(2, 7)}`
      const entry = typeof payload === 'string' ? { title: payload } : payload
      setItems((current) => [...current.slice(-2), { id, tone: 'ok', ...entry }])
      window.setTimeout(() => dismiss(id), 4600)
      return id
    },
    [dismiss],
  )

  const value = useMemo(() => ({ toast, dismiss }), [toast, dismiss])

  return (
    <ToastContext.Provider value={value}>
      {children}
      <div className="cc-toasts" aria-live="polite" aria-atomic="false">
        {items.map((item) => (
          <div key={item.id} className={`cc-toast ${item.tone === 'error' ? 'error' : ''}`.trim()} role="status">
            <span className="cc-toast-mark" aria-hidden="true">
              {item.tone === 'error' ? '!' : '✓'}
            </span>
            <div className="cc-toast-body">
              {item.title && <b>{item.title}</b>}
              {item.message && <p>{item.message}</p>}
            </div>
            <button type="button" onClick={() => dismiss(item.id)} aria-label="Dismiss notification">
              ✕
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}

export function useToast() {
  const context = useContext(ToastContext)
  return context || { toast: () => {}, dismiss: () => {} }
}

/* --------------------------------------------------------------- misc bits */

export function Burst({ active }) {
  if (!active) return null
  return (
    <span className="burst" aria-hidden="true">
      {Array.from({ length: 10 }, (_, index) => (
        <i key={index} style={{ '--i': index }} />
      ))}
    </span>
  )
}

export function BudgetLine({ budget, onEdit, onDelete }) {
  const limit = Number(budget.limit_amount) || 0
  const spent = Number(budget.spent) || 0
  const ratio = pct(spent, limit)
  const left = limit - spent
  return (
    <div
      className={`cc-budget-card ${ratio >= 100 ? 'is-over' : ratio >= 80 ? 'is-warn' : ''}`.trim()}
    >
      <div className="cc-budget-top">
        <div style={{ minWidth: 0 }}>
          <h3>{budget.category_name}</h3>
          <p className="cc-mono" style={{ marginTop: 4 }}>
            {monthTag(budget.month)}
          </p>
        </div>
        <Pill tone={ratio >= 100 ? 'over' : ratio >= 80 ? 'warn' : 'ok'}>
          {ratio >= 100 ? 'Over' : ratio >= 80 ? 'Hot' : 'On track'}
        </Pill>
      </div>
      <p className="cc-budget-amount">
        {money(spent)} <small>of {money(limit)}</small>
      </p>
      <Meter label="Used" value={spent} max={limit} compact />
      <div className="cc-meter-head" style={{ fontSize: '0.75rem' }}>
        <span style={{ color: 'var(--muted)' }}>
          {left >= 0 ? `${money(left)} left` : `${money(Math.abs(left))} over`}
        </span>
        <span>{Math.round(ratio)}%</span>
      </div>
      {(onEdit || onDelete) && (
        <div className="cc-budget-actions">
          {onEdit && (
            <button type="button" className="cc-btn cc-btn--ghost cc-btn--sm" onClick={() => onEdit(budget)}>
              Edit
            </button>
          )}
          {onDelete && (
            <button type="button" className="cc-btn cc-btn--danger cc-btn--sm" onClick={() => onDelete(budget)}>
              Remove
            </button>
          )}
        </div>
      )}
    </div>
  )
}

function monthTag(value) {
  if (!value) return '—'
  const date = new Date(`${String(value).slice(0, 10)}T12:00:00`)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString(undefined, { month: 'short', year: 'numeric' })
}
