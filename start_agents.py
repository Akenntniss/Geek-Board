#!/usr/bin/env python3
"""
🚀 Script de Démarrage - Équipe d'Agents GeekBoard
"""

from crew_agents import executer_demande, interface_conversationnelle

def main():
    print("🤖 Bienvenue dans votre Équipe d'Agents GeekBoard !")
    print("=" * 60)
    print("Votre équipe est composée de :")
    print("👑 Chef Orchestrateur - Analyse et délègue")
    print("🏗️  Architecte Système - Architecture & Migration")
    print("🎨 Frontend React - Interface utilisateur")
    print("🔧 Backend & API - APIs et base de données")
    print("⚡ Performance & Tests - Qualité et optimisation")
    print("=" * 60)
    
    choix = input("Choisissez votre mode:\n1. Interface conversationnelle\n2. Demande unique\n\nVotre choix (1 ou 2): ").strip()
    
    if choix == "1":
        interface_conversationnelle()
    elif choix == "2":
        demande = input("\n🎯 Tapez votre demande : ").strip()
        if demande:
            executer_demande(demande)
    else:
        print("Choix invalide. Lancement de l'interface conversationnelle...")
        interface_conversationnelle()

if __name__ == "__main__":
    main() 