import React from 'react';
import { AlertTriangle, CheckCircle, Clock, TrendingDown } from 'lucide-react';
import { StatsCard } from './StatsCard';
import { RecentActivity } from './RecentActivity';
import { SystemHealth } from './SystemHealth';

export const Dashboard: React.FC = () => {
  const stats = [
    {
      title: 'Commandes Traitées',
      value: '64',
      change: '+12%',
      trend: 'up' as const,
      icon: CheckCircle,
      color: 'green' as const,
    },
    {
      title: 'Erreurs Détectées',
      value: '58',
      change: '+5%',
      trend: 'up' as const,
      icon: AlertTriangle,
      color: 'red' as const,
    },
    {
      title: 'Numéros de Série Manquants',
      value: '58',
      change: '0%',
      trend: 'stable' as const,
      icon: TrendingDown,
      color: 'orange' as const,
    },
    {
      title: 'Temps Moyen de Traitement',
      value: '2.3s',
      change: '-8%',
      trend: 'down' as const,
      icon: Clock,
      color: 'blue' as const,
    },
  ];

  return (
    <div className="space-y-8">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-6">Vue d'ensemble</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {stats.map((stat, index) => (
            <StatsCard key={index} {...stat} />
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <RecentActivity />
        <SystemHealth />
      </div>
    </div>
  );
};