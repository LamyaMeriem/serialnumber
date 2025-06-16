import React from 'react';
import { Package, Settings, TrendingUp } from 'lucide-react';

interface HeaderProps {
  moduleData: any;
}

export const Header: React.FC<HeaderProps> = ({ moduleData }) => {
  return (
    <header className="bg-white shadow-lg border-b-4 border-blue-500">
      <div className="container mx-auto px-4 py-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <div className="bg-blue-500 p-3 rounded-lg">
              <Package className="h-8 w-8 text-white" />
            </div>
            <div>
              <h1 className="text-3xl font-bold text-gray-900">
                Analyseur Module Serial Number
              </h1>
              <p className="text-gray-600 mt-1">
                Analyse et évolution du module PrestaShop
              </p>
            </div>
          </div>
          
          {moduleData && (
            <div className="bg-gray-50 rounded-lg p-4 border">
              <div className="flex items-center space-x-2 mb-2">
                <Settings className="h-4 w-4 text-gray-500" />
                <span className="text-sm font-medium text-gray-700">Module Info</span>
              </div>
              <div className="text-sm text-gray-600">
                <div>Version: {moduleData.version}</div>
                <div>Auteur: {moduleData.author}</div>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};