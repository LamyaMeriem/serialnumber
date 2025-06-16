import React from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

export const LogChart: React.FC = () => {
  const errorsByMonth = [
    { month: 'Jan', errors: 0 },
    { month: 'Fév', errors: 5 },
    { month: 'Mar', errors: 12 },
    { month: 'Avr', errors: 8 },
    { month: 'Mai', errors: 33 },
  ];

  const errorsByType = [
    { name: 'Stock épuisé', value: 45, color: '#ef4444' },
    { name: 'Produit introuvable', value: 8, color: '#f97316' },
    { name: 'Erreur système', value: 5, color: '#eab308' },
  ];

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Évolution des Erreurs</h3>
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={errorsByMonth}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="month" />
            <YAxis />
            <Tooltip />
            <Bar dataKey="errors" fill="#ef4444" radius={[4, 4, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Types d'Erreurs</h3>
        <ResponsiveContainer width="100%" height={300}>
          <PieChart>
            <Pie
              data={errorsByType}
              cx="50%"
              cy="50%"
              outerRadius={100}
              dataKey="value"
              label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
            >
              {errorsByType.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.color} />
              ))}
            </Pie>
            <Tooltip />
          </PieChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};