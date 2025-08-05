// File: src/App.tsx - FIXED VERSION
import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'react-hot-toast';
import ErrorBoundary from './components/Common/ErrorBoundary';

// Auth Module imports
import {
  Login,
  Register,
  ForgotPassword,
  ResetPassword,
  AuthLayout
} from './pages/Auth';

// Import AuthProvider and useAuth from the correct location
import { AuthProvider, useAuth } from './pages/Auth/contexts/AuthContext';

// Landing Module imports
import { LandingPage } from './pages/Landing';

import AdminLayout from './pages/Admin/Layout';
import TenantLayout from './pages/Tenant/Layout';

// [PERBAIKAN]: Import semua halaman tenant, termasuk AccessHistory.
import Dashboard from './pages/Tenant/pages/Dashboard';
import PaymentHistory from './pages/Tenant/pages/PaymentHistory';
import PaymentSuccessPage from './pages/Tenant/components/feature/payments/PaymentSuccessPage';
import PaymentPendingPage from './pages/Tenant/components/feature/payments/PaymentPendingPage';
import PaymentErrorPage from './pages/Tenant/components/feature/payments/PaymentErrorPage';
import AccessHistory from './pages/Tenant/pages/AccessHistory'; // Ditambahkan
import Notifications from './pages/Tenant/pages/Notifications';
import Profile from './pages/Tenant/pages/Profile';


const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: false } },
});

// Protected Route wrapper
function ProtectedRoute({ children, allowedRole }: { children: React.ReactNode; allowedRole?: string }) {
  const { isAuthenticated, isLoading, user, hasCheckedAuth } = useAuth();

  if (isLoading || !hasCheckedAuth) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (allowedRole && user?.role !== allowedRole) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}

// Public Route wrapper
function PublicRoute({ children, redirectAuthenticated = true }: {
  children: React.ReactNode;
  redirectAuthenticated?: boolean
}) {
  const { isAuthenticated, isLoading, user, hasCheckedAuth } = useAuth();

  if (isLoading || !hasCheckedAuth) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (redirectAuthenticated && isAuthenticated && user) {
    const redirectTo = user.role === 'admin' ? '/admin' : '/tenant';
    return <Navigate to={redirectTo} replace />;
  }

  return <>{children}</>;
}

// Landing Page with Auth Integration
function LandingPageWithAuth() {
  const { isAuthenticated, user } = useAuth();

  const handleLoginClick = () => {
    if (isAuthenticated && user) {
      const redirectTo = user.role === 'admin' ? '/admin' : '/tenant';
      window.location.href = redirectTo;
    } else {
      window.location.href = '/login';
    }
  };

  return <LandingPage onLoginClick={handleLoginClick} />;
}

function App() {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <Router
            future={{
              v7_startTransition: true,
              v7_relativeSplatPath: true,
            }}
          >
          <Toaster
            position="top-right"
            toastOptions={{
              duration: 3000,
              style: { background: '#333', color: '#fff' },
            }}
          />

          <Routes>
            {/* Landing Page as home route */}
            <Route
              path="/"
              element={
                <PublicRoute redirectAuthenticated={false}>
                  <LandingPageWithAuth />
                </PublicRoute>
              }
            />

            {/* Auth routes with AuthLayout */}
            <Route
              path="/login"
              element={
                <PublicRoute redirectAuthenticated={true}>
                  <AuthLayout>
                    <Login />
                  </AuthLayout>
                </PublicRoute>
              }
            />

            <Route
              path="/register"
              element={
                <PublicRoute redirectAuthenticated={true}>
                  <AuthLayout>
                    <Register />
                  </AuthLayout>
                </PublicRoute>
              }
            />

            <Route
              path="/forgot-password"
              element={
                <PublicRoute redirectAuthenticated={true}>
                  <AuthLayout>
                    <ForgotPassword />
                  </AuthLayout>
                </PublicRoute>
              }
            />

            <Route
              path="/reset-password"
              element={
                <PublicRoute redirectAuthenticated={true}>
                  <AuthLayout>
                    <ResetPassword />
                  </AuthLayout>
                </PublicRoute>
              }
            />

            {/* Protected Admin routes */}
            <Route
              path="/admin/*"
              element={
                <ProtectedRoute allowedRole="admin">
                  <AdminLayout />
                </ProtectedRoute>
              }
            />

            {/* Protected Tenant routes dengan nested routing */}
            <Route
              path="/tenant"
              element={
                <ProtectedRoute allowedRole="tenant">
                  <TenantLayout />
                </ProtectedRoute>
              }
            >
              {/* Nested routes - ini akan di-render di <Outlet /> dalam TenantLayout */}
              <Route index element={<Navigate to="dashboard" replace />} />
              <Route path="dashboard" element={<Dashboard />} />
              <Route path="payments" element={<PaymentHistory />} />
              <Route path="payments/success" element={<PaymentSuccessPage />} />
              <Route path="payments/pending" element={<PaymentPendingPage />} />
              <Route path="payments/failed" element={<PaymentErrorPage />} />
              {/* [PERBAIKAN]: Menambahkan rute untuk halaman AccessHistory */}
              <Route path="access-history" element={<AccessHistory />} />
              <Route path="notifications" element={<Notifications />} />
              <Route path="profile" element={<Profile />} />
              {/* <Route path="devices" element={<Devices />} /> */}
              {/* <Route path="rfid-cards" element={<RfidCards />} /> */}
            </Route>

            {/* Additional public routes for landing features */}
            <Route
              path="/about"
              element={
                <PublicRoute redirectAuthenticated={false}>
                  <div className="min-h-screen flex items-center justify-center">
                    <div className="text-center">
                      <h1 className="text-2xl font-bold mb-4">About Kos Putri Melati</h1>
                      <p className="text-gray-600 mb-4">Coming soon...</p>
                      <a href="/" className="text-primary-600 hover:underline">← Back to Home</a>
                    </div>
                  </div>
                </PublicRoute>
              }
            />

            <Route
              path="/contact"
              element={
                <PublicRoute redirectAuthenticated={false}>
                  <div className="min-h-screen flex items-center justify-center">
                    <div className="text-center">
                      <h1 className="text-2xl font-bold mb-4">Contact Us</h1>
                      <p className="text-gray-600 mb-4">
                        WhatsApp: <a href="https://wa.me/628123456789" className="text-green-600">+62 812 3456 7890</a>
                      </p>
                      <p className="text-gray-600 mb-4">
                        Phone: <a href="tel:021-12345678" className="text-blue-600">021-12345678</a>
                      </p>
                      <p className="text-gray-600 mb-4">
                        Email: <a href="mailto:info@kosputrimelati.com" className="text-primary-600">info@kosputrimelati.com</a>
                      </p>
                      <a href="/" className="text-primary-600 hover:underline">← Back to Home</a>
                    </div>
                  </div>
                </PublicRoute>
              }
            />

            {/* Catch all route - redirect to home */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </Router>
      </AuthProvider>
    </QueryClientProvider>
    </ErrorBoundary>
  );
}

export default App;
