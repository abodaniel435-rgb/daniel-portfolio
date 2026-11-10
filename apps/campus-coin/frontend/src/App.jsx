import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import Layout from './layout'
import Admin from './pages/Admin'
import Auth from './pages/Auth'
import Budgets from './pages/Budgets'
import Categories from './pages/Categories'
import Dashboard from './pages/Dashboard'
import Forecast from './pages/Forecast'
import Goals from './pages/Goals'
import Heatmap from './pages/Heatmap'
import Landing from './pages/Landing'
import Profile from './pages/Profile'
import Reports from './pages/Reports'
import ResetPassword from './pages/Reset'
import RoommatePage from './pages/Roommate'
import Simulator from './pages/Simulator'
import Splits from './pages/Splits'
import Tips from './pages/Tips'
import Transactions from './pages/Transactions'
import { SessionProvider } from './session'

function Protected({ children }) {
  if (!localStorage.getItem('campusCoinToken')) return <Navigate to="/login" replace />
  return <Layout>{children}</Layout>
}

export default function App() {
  return (
    <BrowserRouter>
      <SessionProvider>
        <Routes>
          <Route path="/" element={<Landing />} />
          <Route path="/login" element={<Auth />} />
          <Route path="/register" element={<Auth register />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          <Route path="/dashboard" element={<Protected><Dashboard /></Protected>} />
          <Route path="/transactions" element={<Protected><Transactions /></Protected>} />
          <Route path="/budgets" element={<Protected><Budgets /></Protected>} />
          <Route path="/goals" element={<Protected><Goals /></Protected>} />
          <Route path="/splits" element={<Protected><Splits /></Protected>} />
          <Route path="/heatmap" element={<Protected><Heatmap /></Protected>} />
          <Route path="/forecast" element={<Protected><Forecast /></Protected>} />
          <Route path="/roommate" element={<Protected><RoommatePage /></Protected>} />
          <Route path="/simulator" element={<Protected><Simulator /></Protected>} />
          <Route path="/reports" element={<Protected><Reports /></Protected>} />
          <Route path="/tips" element={<Protected><Tips /></Protected>} />
          <Route path="/categories" element={<Protected><Categories /></Protected>} />
          <Route path="/profile" element={<Protected><Profile /></Protected>} />
          <Route path="/admin" element={<Protected><Admin /></Protected>} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </SessionProvider>
    </BrowserRouter>
  )
}
