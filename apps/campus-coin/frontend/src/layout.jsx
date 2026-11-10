import { useEffect, useState } from 'react'
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom'
import { api } from './api'
import { money } from './currency'
import { Icon, useToast } from './components'
import { useSession } from './session'
import { Roommate } from './wow2'

const CORE = [
  ['/dashboard', 'home', 'Overview'],
  ['/transactions', 'list', 'Transactions'],
  ['/budgets', 'wallet', 'Budgets'],
  ['/goals', 'target', 'Goal jars'],
  ['/profile', 'user', 'Profile'],
]

const MORE = [
  ['/splits', 'Split & settle'],
  ['/reports', 'Reports'],
  ['/heatmap', 'Spend heatmap'],
  ['/forecast', 'Forecast room'],
  ['/tips', 'Smart tips'],
  ['/roommate', 'Money roommate'],
  ['/simulator', 'What-if simulator'],
  ['/categories', 'Categories'],
]

const TITLES = {
  '/dashboard': ['Overview', 'Your month, in one place'],
  '/transactions': ['Transactions', 'Every naira, logged'],
  '/budgets': ['Budgets', 'Guardrails per category'],
  '/goals': ['Goal jars', 'Save toward the thing you want'],
  '/profile': ['Profile & settings', 'Your account, currency and targets'],
  '/splits': ['Split & settle', 'Roommate bills, squared away'],
  '/reports': ['Reports', 'Six months of spending, compared'],
  '/heatmap': ['Spend heatmap', 'A year of daily spending'],
  '/forecast': ['Forecast room', 'Where this month lands'],
  '/tips': ['Smart tips', 'Nudges built from your own ledger'],
  '/roommate': ['Money roommate', 'Ask your ledger questions'],
  '/simulator': ['What-if simulator', 'Change one habit, see the month'],
  '/categories': ['Categories', 'Your own labels'],
  '/admin': ['Admin studio', 'Campus-wide numbers'],
}

const TABS = [CORE[0], CORE[1], CORE[2], CORE[3], CORE[4]]

export default function Layout({ children }) {
  const navigate = useNavigate()
  const location = useLocation()
  const { profile, clearSession } = useSession()
  const { toast } = useToast()
  const [open, setOpen] = useState(false)
  const [alerts, setAlerts] = useState([])
  const [bellOpen, setBellOpen] = useState(false)

  useEffect(() => {
    setOpen(false)
    setBellOpen(false)
  }, [location.pathname])

  useEffect(() => {
    let live = true
    api('/dashboard/')
      .then((data) => {
        if (!live) return
        setAlerts(
          (data.budgets || []).filter((budget) => {
            const limit = Number(budget.limit)
            return limit > 0 && Number(budget.spent) / limit >= 0.8
          }),
        )
      })
      .catch(() => live && setAlerts([]))
    return () => {
      live = false
    }
  }, [location.pathname])

  const logout = () => {
    clearSession()
    toast({ title: 'Signed out', message: 'Your ledger is safe. See you soon.' })
    navigate('/login')
  }

  const links = profile?.is_staff ? [...CORE, ['/admin', 'spark', 'Admin studio']] : CORE
  const [pageTitle, pageNote] = TITLES[location.pathname] || ['Campus Coin', 'Student money, sorted']
  const initials = (profile?.first_name || profile?.username || 'C').slice(0, 1).toUpperCase()
  const today = new Date().toLocaleDateString(undefined, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })

  const navList = (extraClass = '') => (
    <nav className={`cc-nav ${extraClass}`.trim()} aria-label="Main">
      {links.map(([to, icon, label]) => (
        <NavLink key={to} to={to} className={({ isActive }) => `cc-navlink${isActive ? ' active' : ''}`}>
          <Icon name={icon} size={17} />
          <span>{label}</span>
        </NavLink>
      ))}
      <p className="cc-nav-group cc-eyebrow">Explore</p>
      {MORE.map(([to, label]) => (
        <NavLink key={to} to={to} className={({ isActive }) => `cc-navlink${isActive ? ' active' : ''}`}>
          <Icon name="chevronRight" size={14} />
          <span>{label}</span>
        </NavLink>
      ))}
    </nav>
  )

  return (
    <div className="cc-app">
      <a href="#main" className="cc-sr">
        Skip to content
      </a>

      <button
        type="button"
        className={`cc-scrim ${open ? 'show' : ''}`}
        onClick={() => setOpen(false)}
        aria-label="Close navigation"
        tabIndex={open ? 0 : -1}
      />

      <aside className={`cc-side ${open ? 'open' : ''}`.trim()} aria-label="Sidebar navigation">
        <Link to="/dashboard" className="cc-brand" aria-label="Campus Coin home">
          <span className="cc-brand-mark">C</span>
          Campus<em>Coin</em>
        </Link>
        {navList()}
        <div className="cc-side-foot">
          <Link to="/profile" className="cc-user">
            <span className="cc-avatar">{initials}</span>
            <span className="cc-user-meta">
              <b>{profile ? `${profile.first_name || profile.username}` : 'Student'}</b>
              <small>{profile?.academic_year || profile?.email || 'Campus Coin member'}</small>
            </span>
          </Link>
          <button type="button" className="cc-navlink" onClick={logout} style={{ textAlign: 'left' }}>
            <Icon name="logout" size={17} />
            <span>Log out</span>
          </button>
        </div>
      </aside>

      <div className="cc-main">
        <header className="cc-topbar">
          <button type="button" className="cc-iconbtn cc-menu-btn" aria-label="Open menu" onClick={() => setOpen(true)}>
            <Icon name="menu" />
          </button>
          <div className="cc-topbar-title">
            <h1>{pageTitle}</h1>
            <p>{location.pathname === '/dashboard' ? today : pageNote}</p>
          </div>
          <div className="cc-topbar-actions">
            <div className="cc-bell-wrap">
              <button
                type="button"
                className="cc-iconbtn"
                aria-label={alerts.length ? `Budget alerts: ${alerts.length}` : 'Budget alerts'}
                aria-expanded={bellOpen}
                onClick={() => setBellOpen((value) => !value)}
              >
                <Icon name="alert" size={17} />
              </button>
              {alerts.length > 0 && (
                <span
                  aria-hidden="true"
                  style={{
                    position: 'absolute',
                    top: -4,
                    right: -4,
                    minWidth: 18,
                    height: 18,
                    borderRadius: 9,
                    background: 'var(--amber)',
                    color: '#160f00',
                    fontSize: '.625rem',
                    fontWeight: 800,
                    display: 'grid',
                    placeItems: 'center',
                    padding: '0 4px',
                  }}
                >
                  {alerts.length}
                </span>
              )}
              {bellOpen && (
                <div className="cc-pop cc-card" role="status">
                  <p className="cc-eyebrow">Budget alerts</p>
                  {alerts.length === 0 && <p className="cc-sub">Every category is inside its line.</p>}
                  {alerts.map((alert) => {
                    const ratio = Math.round((Number(alert.spent) / Number(alert.limit)) * 100)
                    return (
                      <p key={alert.category} className="cc-sub" style={{ marginTop: 8 }}>
                        <b style={{ color: 'var(--text)' }}>{alert.category}</b> · {ratio}% used ·{' '}
                        {money(alert.spent)} of {money(alert.limit)}
                      </p>
                    )
                  })}
                  <Link to="/budgets" className="cc-link" style={{ marginTop: 10 }} onClick={() => setBellOpen(false)}>
                    Review budgets <Icon name="arrow" size={13} />
                  </Link>
                </div>
              )}
            </div>
            <Link to="/transactions" className="cc-btn cc-btn--primary cc-btn--sm">
              <Icon name="plus" size={15} /> Log money
            </Link>
          </div>
        </header>

        <main className="cc-page" id="main" key={location.pathname}>
          <div className="cc-enter">{children}</div>
        </main>
      </div>

      <nav className="cc-tabbar" aria-label="Bottom navigation">
        {TABS.map(([to, icon, label]) => (
          <NavLink key={to} to={to} className={({ isActive }) => (isActive ? 'active' : '')}>
            <Icon name={icon} size={19} />
            {label === 'Transactions' ? 'Money' : label === 'Goal jars' ? 'Goals' : label}
          </NavLink>
        ))}
      </nav>

      <Roommate />
    </div>
  )
}
