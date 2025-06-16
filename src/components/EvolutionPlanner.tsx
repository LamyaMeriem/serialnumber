import React, { useState } from 'react';
import { CheckCircle, Circle, Clock, Star, ArrowRight } from 'lucide-react';

export const EvolutionPlanner: React.FC = () => {
  const [selectedPhase, setSelectedPhase] = useState(0);

  const evolutionPhases = [
    {
      id: 1,
      title: 'Phase 1: Configuration Avancée',
      status: 'planned',
      priority: 'high',
      duration: '2-3 semaines',
      description: 'Refonte complète du système de configuration',
      features: [
        'Interface de configuration moderne avec Symfony Forms',
        'Gestion flexible des formats de numéros de série (RegEx)',
        'Configuration des moments d\'assignation (validation, statuts)',
        'Options d\'affichage (factures, bons de livraison, front-office)',
        'Système de notifications par seuils de stock',
        'Options de désinstallation sécurisées',
      ],
      impact: 'Améliore considérablement la flexibilité du module',
    },
    {
      id: 2,
      title: 'Phase 2: Gestion des Approvisionnements',
      status: 'planned',
      priority: 'high',
      duration: '3-4 semaines',
      description: 'Outils avancés pour la gestion des stocks de numéros de série',
      features: [
        'Import/Export CSV et Excel massif',
        'Interface de saisie dynamique avec validation temps réel',
        'Générateur automatique de numéros de série',
        'Traçabilité fournisseur (ajout de champs BD)',
        'Association avec les commandes fournisseurs PrestaShop',
        'Gestion des lots et références fournisseur',
      ],
      impact: 'Automatise et sécurise la gestion des approvisionnements',
    },
    {
      id: 3,
      title: 'Phase 3: Gestion Avancée des Commandes',
      status: 'planned',
      priority: 'medium',
      duration: '2-3 semaines',
      description: 'Amélioration du processus de traitement des commandes',
      features: [
        'Assignation manuelle dans l\'interface commande',
        'Sélection intelligente des numéros de série',
        'Gestion complète des retours et du SAV',
        'Nouveaux statuts (returned, in_repair, refurbished, scrapped)',
        'Workflow automatisé pour les retours',
        'Interface dédiée pour le service SAV',
      ],
      impact: 'Optimise le traitement des commandes et le service client',
    },
    {
      id: 4,
      title: 'Phase 4: Reporting et Historique',
      status: 'planned',
      priority: 'medium',
      duration: '2 semaines',
      description: 'Outils de reporting et d\'analyse avancés',
      features: [
        'Interface complète pour l\'historique des actions',
        'Filtres avancés et recherche multicritères',
        'Vue chronologique du cycle de vie',
        'Tableaux de bord avec métriques clés',
        'Exports de rapports personnalisables',
        'Alertes et notifications automatiques',
      ],
      impact: 'Fournit une visibilité complète sur les opérations',
    },
    {
      id: 5,
      title: 'Phase 5: Intégration Front-Office',
      status: 'planned',
      priority: 'low',
      duration: '3 semaines',
      description: 'Fonctionnalités client et interface publique',
      features: [
        'Affichage des numéros de série dans l\'espace client',
        'Page d\'enregistrement de garantie',
        'Vérificateur public d\'authenticité',
        'Interface responsive et moderne',
        'Intégration avec les emails de confirmation',
        'API pour applications mobiles',
      ],
      impact: 'Améliore l\'expérience client et la valeur perçue',
    },
  ];

  const getPriorityColor = (priority: string) => {
    switch (priority) {
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

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <CheckCircle className="h-5 w-5 text-green-500" />;
      case 'in-progress':
        return <Clock className="h-5 w-5 text-blue-500" />;
      case 'planned':
        return <Circle className="h-5 w-5 text-gray-400" />;
      default:
        return <Circle className="h-5 w-5 text-gray-400" />;
    }
  };

  return (
    <div className="space-y-8">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-6">Plan d'Évolution du Module</h2>
        
        {/* Vue d'ensemble du roadmap */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <div className="flex items-center space-x-2 mb-6">
            <Star className="h-5 w-5 text-yellow-500" />
            <h3 className="text-lg font-semibold text-gray-900">Roadmap Général</h3>
          </div>
          
          <div className="space-y-4">
            {evolutionPhases.map((phase, index) => (
              <div
                key={phase.id}
                className={`p-4 rounded-lg border-2 cursor-pointer transition-all ${
                  selectedPhase === index
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200 hover:border-gray-300'
                }`}
                onClick={() => setSelectedPhase(index)}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    {getStatusIcon(phase.status)}
                    <div>
                      <h4 className="font-medium text-gray-900">{phase.title}</h4>
                      <p className="text-sm text-gray-600">{phase.description}</p>
                    </div>
                  </div>
                  
                  <div className="flex items-center space-x-2">
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPriorityColor(phase.priority)}`}>
                      {phase.priority === 'high' ? 'Priorité Haute' :
                       phase.priority === 'medium' ? 'Priorité Moyenne' : 'Priorité Basse'}
                    </span>
                    <span className="text-sm text-gray-500">{phase.duration}</span>
                    <ArrowRight className="h-4 w-4 text-gray-400" />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Détails de la phase sélectionnée */}
        {evolutionPhases[selectedPhase] && (
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div className="mb-6">
              <div className="flex items-center space-x-3 mb-4">
                {getStatusIcon(evolutionPhases[selectedPhase].status)}
                <h3 className="text-xl font-semibold text-gray-900">
                  {evolutionPhases[selectedPhase].title}
                </h3>
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPriorityColor(evolutionPhases[selectedPhase].priority)}`}>
                  {evolutionPhases[selectedPhase].priority === 'high' ? 'Priorité Haute' :
                   evolutionPhases[selectedPhase].priority === 'medium' ? 'Priorité Moyenne' : 'Priorité Basse'}
                </span>
              </div>
              
              <p className="text-gray-600 mb-4">{evolutionPhases[selectedPhase].description}</p>
              
              <div className="flex items-center space-x-4 text-sm text-gray-500">
                <span>⏱️ Durée estimée: {evolutionPhases[selectedPhase].duration}</span>
                <span>📈 Impact: {evolutionPhases[selectedPhase].impact}</span>
              </div>
            </div>

            <div>
              <h4 className="font-medium text-gray-900 mb-4">Fonctionnalités à développer:</h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                {evolutionPhases[selectedPhase].features.map((feature, index) => (
                  <div key={index} className="flex items-start space-x-2 p-3 bg-gray-50 rounded-lg">
                    <CheckCircle className="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                    <span className="text-sm text-gray-700">{feature}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Métriques du projet */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div className="text-3xl font-bold text-blue-600 mb-2">12-15</div>
            <div className="text-sm text-gray-600">Semaines de développement</div>
          </div>
          
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div className="text-3xl font-bold text-green-600 mb-2">25+</div>
            <div className="text-sm text-gray-600">Nouvelles fonctionnalités</div>
          </div>
          
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div className="text-3xl font-bold text-purple-600 mb-2">v2.0.0</div>
            <div className="text-sm text-gray-600">Version cible</div>
          </div>
        </div>
      </div>
    </div>
  );
};