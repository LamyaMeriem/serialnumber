import React from 'react';
import { Database, Table, Users, Package, AlertCircle } from 'lucide-react';

export const DatabaseAnalyzer: React.FC = () => {
  const tableStats = [
    {
      name: 'serial_numbers',
      records: 1247,
      size: '2.3 MB',
      lastUpdate: '2025-05-23',
      status: 'healthy',
      icon: Package,
    },
    {
      name: 'serial_numbers_history',
      records: 3891,
      size: '5.7 MB',
      lastUpdate: '2025-05-23',
      status: 'healthy',
      icon: Table,
    },
    {
      name: 'product',
      records: 2156,
      size: '12.4 MB',
      lastUpdate: '2025-05-22',
      status: 'warning',
      icon: Package,
    },
    {
      name: 'orders',
      records: 892,
      size: '8.9 MB',
      lastUpdate: '2025-05-23',
      status: 'healthy',
      icon: Users,
    },
  ];

  const issues = [
    {
      type: 'warning',
      message: '58 produits sans numéros de série disponibles',
      table: 'serial_numbers',
      severity: 'high',
    },
    {
      type: 'error',
      message: 'Index manquant sur id_product_attribute',
      table: 'serial_numbers',
      severity: 'medium',
    },
    {
      type: 'info',
      message: 'Nettoyage recommandé des anciens logs',
      table: 'serial_numbers_history',
      severity: 'low',
    },
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'healthy':
        return 'text-green-500 bg-green-50';
      case 'warning':
        return 'text-orange-500 bg-orange-50';
      case 'error':
        return 'text-red-500 bg-red-50';
      default:
        return 'text-gray-500 bg-gray-50';
    }
  };

  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'high':
        return 'bg-red-100 text-red-800';
      case 'medium':
        return 'bg-orange-100 text-orange-800';
      case 'low':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="space-y-8">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-6">Analyse de la Base de Données</h2>
        
        {/* Vue d'ensemble des tables */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {tableStats.map((table, index) => {
            const Icon = table.icon;
            return (
              <div key={index} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className={`p-2 rounded-lg ${getStatusColor(table.status)}`}>
                    <Icon className="h-5 w-5" />
                  </div>
                  <div className={`w-3 h-3 rounded-full ${
                    table.status === 'healthy' ? 'bg-green-400' :
                    table.status === 'warning' ? 'bg-orange-400' : 'bg-red-400'
                  }`}></div>
                </div>
                
                <h3 className="text-sm font-medium text-gray-900 mb-2">{table.name}</h3>
                <div className="space-y-1 text-sm text-gray-600">
                  <div>{table.records.toLocaleString()} enregistrements</div>
                  <div>{table.size}</div>
                  <div className="text-xs">MAJ: {table.lastUpdate}</div>
                </div>
              </div>
            );
          })}
        </div>

        {/* Problèmes détectés */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <div className="flex items-center space-x-2 mb-6">
            <AlertCircle className="h-5 w-5 text-orange-500" />
            <h3 className="text-lg font-semibold text-gray-900">Problèmes Détectés</h3>
          </div>
          
          <div className="space-y-4">
            {issues.map((issue, index) => (
              <div key={index} className="flex items-start space-x-4 p-4 rounded-lg border border-gray-200">
                <div className={`flex-shrink-0 w-2 h-2 rounded-full mt-2 ${
                  issue.type === 'error' ? 'bg-red-400' :
                  issue.type === 'warning' ? 'bg-orange-400' : 'bg-blue-400'
                }`}></div>
                
                <div className="flex-1">
                  <div className="flex items-center space-x-2 mb-1">
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getSeverityColor(issue.severity)}`}>
                      {issue.severity === 'high' ? 'Critique' :
                       issue.severity === 'medium' ? 'Moyen' : 'Faible'}
                    </span>
                    <span className="text-sm text-gray-500">{issue.table}</span>
                  </div>
                  <p className="text-sm text-gray-900">{issue.message}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Recommandations d'optimisation */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center space-x-2 mb-6">
            <Database className="h-5 w-5 text-blue-500" />
            <h3 className="text-lg font-semibold text-gray-900">Recommandations d'Optimisation</h3>
          </div>
          
          <div className="space-y-4">
            <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
              <h4 className="font-medium text-blue-900 mb-2">Index de Performance</h4>
              <p className="text-sm text-blue-800">
                Ajouter un index composite sur (id_product, id_product_attribute, status) 
                pour améliorer les performances des requêtes de recherche de numéros de série.
              </p>
            </div>
            
            <div className="p-4 bg-green-50 rounded-lg border border-green-200">
              <h4 className="font-medium text-green-900 mb-2">Archivage des Données</h4>
              <p className="text-sm text-green-800">
                Mettre en place un système d'archivage pour les entrées d'historique 
                de plus de 2 ans afin de maintenir les performances.
              </p>
            </div>
            
            <div className="p-4 bg-orange-50 rounded-lg border border-orange-200">
              <h4 className="font-medium text-orange-900 mb-2">Contraintes de Données</h4>
              <p className="text-sm text-orange-800">
                Ajouter des contraintes de clés étrangères pour assurer l'intégrité 
                référentielle entre les tables serial_numbers et product.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};