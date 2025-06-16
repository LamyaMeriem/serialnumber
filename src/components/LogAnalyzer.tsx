import React, { useState, useMemo } from 'react';
import { Search, Filter, Download, AlertTriangle, Info, Bug } from 'lucide-react';
import { LogChart } from './LogChart';

export const LogAnalyzer: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [filterLevel, setFilterLevel] = useState('all');

  // Données simulées basées sur le fichier log.txt
  const logData = useMemo(() => [
    { date: '2025-05-23 14:39:27', level: 'ERROR', message: 'Pas de numéros de série disponibles pour le produit ID: 41', orderId: 63 },
    { date: '2025-05-23 14:39:26', level: 'ERROR', message: 'Pas de numéros de série disponibles pour le produit ID: 4', orderId: 62 },
    { date: '2025-05-23 10:49:58', level: 'ERROR', message: 'Pas de numéros de série disponibles pour le produit ID: 9', orderId: 61 },
    { date: '2025-05-23 10:14:45', level: 'ERROR', message: 'Pas de numéros de série disponibles pour le produit ID: 3', orderId: 60 },
    { date: '2025-05-22 22:41:09', level: 'ERROR', message: 'Pas de numéros de série disponibles pour le produit ID: 5', orderId: 59 },
    { date: '2024-12-27 19:25:44', level: 'INFO', message: 'Numéros de série assignés pour le produit ID: 3', orderId: 57 },
    { date: '2024-12-27 19:25:44', level: 'DEBUG', message: 'Produit ID: 37 - Quantité : 1', orderId: 57 },
  ], []);

  const filteredLogs = useMemo(() => {
    return logData.filter(log => {
      const matchesSearch = log.message.toLowerCase().includes(searchTerm.toLowerCase()) ||
                           log.orderId.toString().includes(searchTerm);
      const matchesFilter = filterLevel === 'all' || log.level === filterLevel;
      return matchesSearch && matchesFilter;
    });
  }, [logData, searchTerm, filterLevel]);

  const getLevelIcon = (level: string) => {
    switch (level) {
      case 'ERROR':
        return <AlertTriangle className="h-4 w-4 text-red-500" />;
      case 'INFO':
        return <Info className="h-4 w-4 text-blue-500" />;
      case 'DEBUG':
        return <Bug className="h-4 w-4 text-gray-500" />;
      default:
        return <Info className="h-4 w-4 text-gray-500" />;
    }
  };

  const getLevelColor = (level: string) => {
    switch (level) {
      case 'ERROR':
        return 'bg-red-50 text-red-700 border-red-200';
      case 'INFO':
        return 'bg-blue-50 text-blue-700 border-blue-200';
      case 'DEBUG':
        return 'bg-gray-50 text-gray-700 border-gray-200';
      default:
        return 'bg-gray-50 text-gray-700 border-gray-200';
    }
  };

  return (
    <div className="space-y-8">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-6">Analyse des Logs</h2>
        
        {/* Graphique des erreurs */}
        <div className="mb-8">
          <LogChart />
        </div>

        {/* Filtres et recherche */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  placeholder="Rechercher dans les logs..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div className="flex gap-2">
              <select
                value={filterLevel}
                onChange={(e) => setFilterLevel(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">Tous les niveaux</option>
                <option value="ERROR">Erreurs</option>
                <option value="INFO">Informations</option>
                <option value="DEBUG">Debug</option>
              </select>
              
              <button className="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <Download className="h-4 w-4" />
                <span>Exporter</span>
              </button>
            </div>
          </div>
        </div>

        {/* Liste des logs */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200">
          <div className="p-6 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">
              Logs récents ({filteredLogs.length} entrées)
            </h3>
          </div>
          
          <div className="divide-y divide-gray-200">
            {filteredLogs.map((log, index) => (
              <div key={index} className="p-6 hover:bg-gray-50 transition-colors">
                <div className="flex items-start space-x-4">
                  <div className="flex-shrink-0">
                    {getLevelIcon(log.level)}
                  </div>
                  
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center space-x-2 mb-2">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${getLevelColor(log.level)}`}>
                        {log.level}
                      </span>
                      <span className="text-sm text-gray-500">Commande #{log.orderId}</span>
                      <span className="text-sm text-gray-500">{log.date}</span>
                    </div>
                    
                    <p className="text-sm text-gray-900 font-medium">{log.message}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};