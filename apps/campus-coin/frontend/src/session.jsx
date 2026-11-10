import { createContext, useContext, useEffect, useState } from 'react'
import { api, restoreSession } from './api'
import { setCurrency } from './currency'

const SessionContext = createContext(null)

export function SessionProvider({ children }) {
  // Campus Coin ships a single deep-ink theme. The attribute is still set so
  // older utility pages that key off [data-theme] resolve to the dark surface.
  const theme = 'dark'
  const [size] = useState(() => localStorage.getItem('cc-size') || 'md')
  const [profile, setProfile] = useState(null)
  const [ready, setReady] = useState(false)

  useEffect(() => {
    document.documentElement.dataset.theme = theme
    document.documentElement.dataset.size = size
  }, [theme, size])

  const refreshProfile = async () => {
    if (!localStorage.getItem('campusCoinToken')) {
      setProfile(null)
      return null
    }
    const me = await api('/auth/me/')
    setCurrency(me?.country)
    setProfile(me)
    return me
  }

  useEffect(() => {
    restoreSession()
      .catch(() => false)
      .then(() => refreshProfile())
      .catch(() => setProfile(null))
      .finally(() => setReady(true))
  }, [])

  const value = {
    theme,
    size,
    profile,
    ready,
    setProfile,
    refreshProfile,
    setTheme: () => {},
    setSize: () => {},
    toggleTheme: () => {},
    clearSession: () => {
      localStorage.removeItem('campusCoinToken')
      localStorage.removeItem('campusCoinRefresh')
      setProfile(null)
    },
  }

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>
}

export function useSession() {
  return useContext(SessionContext)
}
