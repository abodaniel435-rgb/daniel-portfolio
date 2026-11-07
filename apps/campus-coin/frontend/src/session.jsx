import { createContext, useContext, useEffect, useState } from 'react'
import { api, restoreSession } from './api'
import { setCurrency } from './currency'

const SessionContext = createContext(null)

export function SessionProvider({ children }) {
  const [theme, setTheme] = useState(() => localStorage.getItem('cc-theme') || 'light')
  const [size, setSize] = useState(() => localStorage.getItem('cc-size') || 'md')
  const [profile, setProfile] = useState(null)

  useEffect(() => {
    document.documentElement.dataset.theme = theme
    document.documentElement.dataset.size = size
    localStorage.setItem('cc-theme', theme)
    localStorage.setItem('cc-size', size)
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
  }, [])

  const value = {
    theme,
    size,
    profile,
    setProfile,
    refreshProfile,
    setTheme,
    setSize,
    toggleTheme: () => setTheme((current) => (current === 'dark' ? 'light' : 'dark')),
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
