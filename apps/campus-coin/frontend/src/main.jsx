import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './legacy.css'
import './styles.css'
import App from './App'
import { ToastProvider } from './components'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <ToastProvider>
      <App />
    </ToastProvider>
  </StrictMode>,
)
