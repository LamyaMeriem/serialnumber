import React from 'react';
import { Activity, Database, Server, Zap } from 'lucide-react';

export const SystemHealth: React.FC = () => {
  const healthMetrics = [
    {
      name: 'Base de Données',
      status: 'healthy',
      value: '98%',
      icon: Database,
    },
    {
      name: 'Performance',
      status: 'warning',
      value: '76%',
      icon: Zap,
    },
    {
      name: 'Serveur',
      status: 'healthy',
      value: '99%',
      icon: Server,
    },
    {
      name: 'Synchronisation',
      status: 'error',
      value: '45%',
      icon: Activity,
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

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div className="flex items-center space-x-2 mb-6">
        <Activity className="h-5 w-5 text-gray-500" />
        <h3 className="text-lg font-semibold text-gray-900">État du Système</h3>
      </div>
      
      <div className="space-y-4">
        {healthMetrics.map((metric, index) => {
          const Icon = metric.icon;
          return (
            <div key={index} className="flex items-center justify-between p-3 rounded-lg border border-gray-100">
              <div className="flex items-center space-x-3">
                <div className={`p-2 rounded-lg ${getStatusColor(metric.status)}`}>
                  <Icon className="h-4 w-4" />
                </div>
                <span className="text-sm font-medium text-gray-900">{metric.name}</span>
              </div>
              <div className="text-right">
                <span className="text-sm font-bold text-gray-900">{metric.value}</span>
                <div className={`text-xs capitalize ${
                  metric.status === 'healthy' ? 'text-green-600' :
                  metric.status === 'warning' ? 'text-orange-600' : 'text-red-600'
                }`}>
                  {metric.status === 'healthy' ? 'Sain' :
                   metric.status === 'warning' ? 'Attention' : 'Erreur'}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};