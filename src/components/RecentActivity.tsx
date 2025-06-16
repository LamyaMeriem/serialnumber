import React from 'react';
import { Clock, AlertCircle, CheckCircle, XCircle } from 'lucide-react';

export const RecentActivity: React.FC = () => {
  const activities = [
    {
      id: 1,
      type: 'error',
      message: 'Pas de numéros de série disponibles pour le produit ID: 41',
      time: '2025-05-23 14:39:27',
      icon: XCircle,
      color: 'text-red-500',
    },
    {
      id: 2,
      type: 'success',
      message: 'Numéros de série assignés pour la commande ID: 57',
      time: '2024-12-27 19:25:44',
      icon: CheckCircle,
      color: 'text-green-500',
    },
    {
      id: 3,
      type: 'warning',
      message: 'Stock faible pour le produit ID: 23',
      time: '2025-04-07 13:15:00',
      icon: AlertCircle,
      color: 'text-orange-500',
    },
    {
      id: 4,
      type: 'error',
      message: 'Pas de numéros de série disponibles pour le produit ID: 57',
      time: '2025-05-19 09:49:43',
      icon: XCircle,
      color: 'text-red-500',
    },
  ];

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div className="flex items-center space-x-2 mb-6">
        <Clock className="h-5 w-5 text-gray-500" />
        <h3 className="text-lg font-semibold text-gray-900">Activité Récente</h3>
      </div>
      
      <div className="space-y-4">
        {activities.map((activity) => {
          const Icon = activity.icon;
          return (
            <div key={activity.id} className="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
              <Icon className={`h-5 w-5 mt-0.5 ${activity.color}`} />
              <div className="flex-1 min-w-0">
                <p className="text-sm text-gray-900 font-medium">{activity.message}</p>
                <p className="text-xs text-gray-500 mt-1">{activity.time}</p>
              </div>
            </div>
          );
        })}
      </div>
      
      <div className="mt-6 pt-4 border-t border-gray-200">
        <button className="text-sm text-blue-600 hover:text-blue-700 font-medium">
          Voir toute l'activité →
        </button>
      </div>
    </div>
  );
};