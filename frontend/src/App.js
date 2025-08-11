import React from "react";
import { BrowserRouter as Router, Routes, Route, Link } from "react-router-dom";
import "./App.css";
import { AuthProvider, useAuth } from "./contexts/AuthContext";
import CreateCampaign from "./components/CreateCampaign";
import CampaignList from "./components/CampaignList";
import CampaignDetail from "./components/CampaignDetail";
import Login from "./components/Login";
import Register from "./components/Register";
import Dashboard from "./components/Dashboard";
import ProtectedRoute from "./components/ProtectedRoute";

const Navigation = () => {
  const { user, logout, isAuthenticated } = useAuth();

  const handleLogout = async () => {
    await logout();
  };

  return (
    <header className="app-header">
      <h1>Donation Platform</h1>
      <nav className="main-nav">
        <Link to="/">Home</Link>
        {isAuthenticated ? (
          <>
            <Link to="/create">Create Campaign</Link>
            <Link to="/dashboard">
              {user?.role === "admin" ? "Admin Panel" : "My Dashboard"}
            </Link>
          </>
        ) : (
          <>
            <Link to="/login">Login</Link>
            <Link to="/register">Register</Link>
          </>
        )}
      </nav>
      {isAuthenticated && (
        <div className="user-info">
          <span className="user-name">Welcome, {user?.name}</span>
          <button onClick={handleLogout} className="logout-btn">
            Logout
          </button>
        </div>
      )}
    </header>
  );
};

function AppContent() {
  return (
    <Router>
      <div className="App">
        <Navigation />

        <main className="app-main">
          <Routes>
            <Route
              path="/"
              element={
                <div>
                  <CampaignList
                    onCampaignSelect={(campaign) => {
                      window.location.href = `/campaign/${campaign.slug}`;
                    }}
                  />
                </div>
              }
            />
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            <Route path="/campaign/:slug" element={<CampaignDetail />} />

            {/* Protected Routes */}
            <Route
              path="/create"
              element={
                <ProtectedRoute>
                  <CreateCampaign />
                </ProtectedRoute>
              }
            />
            <Route
              path="/dashboard"
              element={
                <ProtectedRoute>
                  <Dashboard />
                </ProtectedRoute>
              }
            />
          </Routes>
        </main>

        <footer className="app-footer">
          <p>&copy; 2025 Donation Platform - Helping people help people</p>
        </footer>
      </div>
    </Router>
  );
}

function App() {
  return (
    <AuthProvider>
      <AppContent />
    </AuthProvider>
  );
}

export default App;
