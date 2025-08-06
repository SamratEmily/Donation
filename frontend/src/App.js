import React, { useState } from 'react';
import { BrowserRouter as Router, Routes, Route, Link } from 'react-router-dom';
import './App.css';
import CreateCampaign from './components/CreateCampaign';
import CampaignList from './components/CampaignList';
import CampaignDetail from './components/CampaignDetail';
import AdminPanel from './components/AdminPanel';

function App() {
  const [activeTab, setActiveTab] = useState('home');

  return (
    <Router>
      <div className="App">
        <header className="app-header">
          <h1>Donation Platform</h1>
          <nav className="main-nav">
            <Link to="/" onClick={() => setActiveTab('home')}>
              Home
            </Link>
            <Link to="/create" onClick={() => setActiveTab('create')}>
              Create Campaign
            </Link>
          </nav>
        </header>

        <main className="app-main">
          <Routes>
            <Route path="/" element={
              <div>
                <CampaignList onCampaignSelect={(campaign) => {
                  window.location.href = `/campaign/${campaign.slug}`;
                }} />
              </div>
            } />
            <Route path="/create" element={<CreateCampaign />} />
            <Route path="/campaign/:slug" element={<CampaignDetail />} />
          </Routes>
        </main>

        <footer className="app-footer">
          <p>&copy; 2025 Donation Platform - Helping people help people</p>
        </footer>
      </div>
    </Router>
  );
}

export default App;
