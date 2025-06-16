import React, { useState, useEffect } from 'react';
import { Dashboard } from './components/Dashboard';
import { LogAnalyzer } from './components/LogAnalyzer';
import { EvolutionPlanner } from './components/EvolutionPlanner';
import { DatabaseAnalyzer } from './components/DatabaseAnalyzer';
import { Header } from './components/Header';
import { Navigation } from './components/Navigation';

type TabType = 'dashboard' | 'logs' | 'database' | 'evolution';

function App() {
  const [activeTab, setActiveTab] = useState<TabType>('dashboard');
  const [moduleData, setModuleData] = useState(null);

  useEffect(() => {
    // Simulation du chargement des données du module
    const loadModuleData = async () => {
      // Ici, on simulerait le chargement des données réelles
      // Pour la démo, on utilise des données mockées
      setModuleData({
        version: '1.0.0',
        author: 'Mr-dev',
        lastUpdate: new Date().toISOString(),
      });
    };

    loadModuleData();
  }, []);

  const renderActiveTab = () => {
    switch (activeTab) {
      case 'dashboard':
        return <Dashboard />;
      case 'logs':
        return <LogAnalyzer />;
      case 'database':
        return <DatabaseAnalyzer />;
      case 'evolution':
        return <EvolutionPlanner />;
      default:
        return <Dashboard />;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
      <Header moduleData={moduleData} />
      <Navigation activeTab={activeTab} onTabChange={setActiveTab} />
      <main className="container mx-auto px-4 py-8">
        {renderActiveTab()}
      </main>
    </div>
  );
}

export default App;