import { useEffect, useState } from 'react'
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom'
import { api } from './api'
import { cash } from './components'
import { useSession } from './session'
import { Roommate } from './wow2'

const LINKS = [
  ['/dashboard', '◈', 'Overview'],
  ['/transactions', '⇄', 'Transactions'],
  ['/budgets', '◎', 'Budgets'],
  ['/goals', '🫙', 'Goal jars'],
  ['/splits', '⑂', 'Split & settle'],
  ['/heatmap', '◫', 'Heatmap'],
  ['/forecast', '◔', 'Forecast room'],
  ['/roommate', '💬', 'Roommate'],
  ['/simulator', '⌗', 'Simulator'],
  ['/reports', '◔', 'Reports'],
  ['/tips', '✦', 'Smart tips'],
  ['/categories', '▦', 'Categories'],
  ['/profile', '☺', 'Profile'],
]

const CRUMBS = {
  '/dashboard': 'Home / Overview',
  '/transactions': 'Home / Transactions',
  '/budgets': 'Home / Budgets',
  '/goals': 'Home / Goal jars',
  '/splits': 'Home / Split & settle',
  '/heatmap': 'Home / Heatmap',
  '/forecast': 'Home / Forecast room',
  '/roommate': 'Home / Roommate',
  '/simulator': 'Home / Simulator',
  '/reports': 'Home / Reports',
  '/tips': 'Home / Smart tips',
  '/categories': 'Home / Categories',
  '/profile': 'Home / Profile',
  '/admin': 'Home / Admin studio',
}

export default function Layout({ children }) {
  const navigate = useNavigate()
  const location = useLocation()
  const { profile, theme, size, setSize, toggleTheme, clearSession } = useSession()
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
        const next = (data.budgets || []).filter((budget) => {
          const limit = Number(budget.limit)
          return limit > 0 && Number(budget.spent) / limit >= 0.8
        })
        setAlerts(next)
      })
      .catch(() => {
        if (live) setAlerts([])
      })
    return () => {
      live = false
    }
  }, [location.pathname])

  const logout = () => {
    clearSession()
    navigate('/login')
  }

  const links = profile?.is_staff ? [...LINKS, ['/admin', '♛', 'Admin']] : LINKS

  return (
    <div className="shell">
      <div className={open ? 'scrim show' : 'scrim'} onClick={() => setOpen(false)} />
      <aside className={open ? 'sidebar open' : 'sidebar'}>
        <Link to="/dashboard" className="brand"><span>◒</span> Campus<span>Coin</span></Link>
        <p className="tagline">Student money club</p>
        <nav aria-label="Main">
          {links.map(([to, icon, label]) => (
            <NavLink to={to} key={to}><i>{icon}</i>{label}</NavLink>
          ))}
        </nav>
        <div className="sidebar-bottom">
          <span className="pulse" /> Online & secure
          <button type="button" onClick={logout}>Log out ↗</button>
        </div>
      </aside>
      <main className="content">
        <div className="campus-band" aria-hidden="false">
          <img src="https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1600&auto=format&fit=crop" alt="" loading="eager" />
          <div>
            <b>CAMPUS LIFE • {theme === 'dark' ? 'DARK MODE' : 'LIGHT MODE'}</b>
            <h2 style={{ margin: '8px 0 2px', letterSpacing: '-.04em' }}>University energy, student money.</h2>
            <p style={{ margin: 0, opacity: .92 }}>Glass halls behind you. Your ledger in front of you.</p>
          </div>
        </div>
        <div className="topbar">
          <button className="menu" type="button" aria-label="Open menu" onClick={() => setOpen(true)}>☰</button>
          <p className="crumb">{CRUMBS[location.pathname] || 'Home'}</p>
          <div className="top-actions">
            <div className="font-size" role="group" aria-label="Text size">
              {[['sm', 'A−'], ['md', 'A'], ['lg', 'A+']].map(([value, label]) => (
                <button key={value} type="button" className={size === value ? 'on' : ''} onClick={() => setSize(value)}>{label}</button>
              ))}
            </div>
            <button className="icon-btn" type="button" onClick={toggleTheme} aria-label="Toggle colour mode">
              {theme === 'dark' ? '☀' : '☾'}
            </button>
            <div className="bell-wrap">
              <button className="icon-btn" type="button" aria-label="Budget alerts" onClick={() => setBellOpen((value) => !value)}>
                ✶
                {alerts.length > 0 && <b>{alerts.length}</b>}
              </button>
              {bellOpen && (
                <div className="bell-panel rise">
                  <p className="overline">Budget alerts</p>
                  {alerts.length === 0 && <p>Every category is still inside its line.</p>}
                  {alerts.map((alert) => (
                    <p key={alert.category}>
                      <strong>{alert.category}</strong> is at {cash(alert.spent)} of {cash(alert.limit)}.
                    </p>
                  ))}
                  <Link to="/budgets" onClick={() => setBellOpen(false)}>Review budgets →</Link>
                </div>
              )}
            </div>
          </div>
        </div>
        <div className="page-enter" key={location.pathname}>{children}</div>
      </main>
      <Roommate />
    </div>
  )
}
