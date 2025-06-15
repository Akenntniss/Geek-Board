#!/usr/bin/env python3
"""
🚀 Démarrage Équipe Complète GeekBoard
Launcher pour l'équipe de 13 agents experts
"""

import os
import subprocess
import sys
from crew_agents_complete import interface_avancee, executer_demande_complete

def verifier_ollama():
    """Vérifie si Ollama fonctionne"""
    try:
        result = subprocess.run(['ollama', 'list'], capture_output=True, text=True)
        if result.returncode == 0:
            return True, result.stdout
        return False, "Ollama non accessible"
    except FileNotFoundError:
        return False, "Ollama non installé"

def afficher_banniere():
    """Affiche la bannière de démarrage"""
    print("\n" + "="*80)
    print("🤖 GEEKBOARD - ÉQUIPE COMPLÈTE D'AGENTS IA")
    print("="*80)
    print("🎯 Mission : Développement complet de GeekBoard")
    print("👥 Équipe : 13 agents experts spécialisés")
    print("🧠 IA : Ollama CodeQwen 7B")
    print("🔥 Status : Équipe prête pour toute mission !")
    print("="*80)

def verifier_prerequis():
    """Vérifie les prérequis système"""
    print("🔍 Vérification des prérequis...")
    
    # Vérification Ollama
    ollama_ok, ollama_info = verifier_ollama()
    if ollama_ok:
        print("✅ Ollama : Actif")
        if "codeqwen:7b" in ollama_info:
            print("✅ Modèle CodeQwen 7B : Disponible")
        else:
            print("⚠️  Modèle CodeQwen 7B : Non trouvé")
            print("💡 Installation : ollama pull codeqwen:7b")
    else:
        print(f"❌ Ollama : {ollama_info}")
        return False
    
    # Vérification Python packages
    try:
        import crewai
        print("✅ CrewAI : Installé")
    except ImportError:
        print("❌ CrewAI : Non installé")
        print("💡 Installation : pip install crewai crewai-tools")
        return False
    
    print("🎉 Tous les prérequis sont satisfaits !")
    return True

def menu_principal():
    """Menu principal interactif"""
    while True:
        print("\n" + "="*60)
        print("🎮 MENU PRINCIPAL")
        print("="*60)
        print("1. 🚀 Lancer l'interface conversationnelle")
        print("2. ⚡ Mission express (demande unique)")
        print("3. 📊 Status de l'équipe")
        print("4. 💡 Exemples de missions")
        print("5. 🔧 Diagnostic système")
        print("6. 👋 Quitter")
        print("="*60)
        
        choix = input("🎯 Votre choix (1-6) : ").strip()
        
        if choix == "1":
            print("\n🚀 Lancement de l'interface conversationnelle...")
            interface_avancee()
        elif choix == "2":
            mission_express()
        elif choix == "3":
            afficher_status()
        elif choix == "4":
            afficher_exemples()
        elif choix == "5":
            diagnostic_systeme()
        elif choix == "6":
            print("👋 Mission terminée. À bientôt !")
            break
        else:
            print("❌ Choix invalide. Réessayez.")

def mission_express():
    """Mode mission express"""
    print("\n⚡ MODE MISSION EXPRESS")
    print("-" * 40)
    print("💡 Décrivez votre objectif en une phrase")
    print("🎯 Exemple : 'Optimise les performances de GeekBoard'")
    
    demande = input("\n🎯 VOTRE MISSION : ").strip()
    if demande:
        print(f"\n🚀 Lancement mission express : {demande}")
        executer_demande_complete(demande)
    else:
        print("❌ Mission annulée - aucune demande fournie")

def afficher_status():
    """Affiche le status de l'équipe"""
    print("\n📊 STATUS DE L'ÉQUIPE")
    print("-" * 40)
    
    ollama_ok, ollama_info = verifier_ollama()
    if ollama_ok:
        print("🟢 Ollama : ACTIF")
        print("🟢 Équipe : 13 AGENTS PRÊTS")
        print("🟢 Coordination : CHEF SUPRÊME ACTIF")
        print("🟢 Outils : ANALYSEUR CODE, DOCS, FICHIERS")
    else:
        print("🔴 Ollama : INACTIF")
        print("🟡 Équipe : EN ATTENTE")
    
    print(f"\n🧠 Modèles disponibles :")
    if ollama_ok:
        print(ollama_info)

def afficher_exemples():
    """Affiche des exemples de missions"""
    print("\n💡 EXEMPLES DE MISSIONS")
    print("-" * 40)
    
    exemples = [
        "🏗️  'Conçois l'architecture complète de GeekBoard'",
        "🚀 'Planifie la migration PHP vers Next.js'", 
        "🎨 'Améliore l'interface utilisateur avec React 19'",
        "⚡ 'Optimise les performances de l'application'",
        "🔒 'Audit sécurité complet de GeekBoard'",
        "📱 'Développe la version mobile PWA'",
        "🧪 'Implémente une suite de tests complète'",
        "📊 'Optimise le référencement SEO'",
        "🗄️  'Optimise la base de données MySQL'",
        "📚 'Crée la documentation technique complète'"
    ]
    
    for i, exemple in enumerate(exemples, 1):
        print(f"{i:2d}. {exemple}")

def diagnostic_systeme():
    """Diagnostic complet du système"""
    print("\n🔧 DIAGNOSTIC SYSTÈME")
    print("-" * 40)
    
    print("🐍 Python :", sys.version)
    print("📍 Répertoire :", os.getcwd())
    
    # Test Ollama
    print("\n🤖 Test Ollama...")
    ollama_ok, info = verifier_ollama()
    print(f"Status : {'✅ OK' if ollama_ok else '❌ KO'}")
    if ollama_ok:
        print(f"Modèles : {len(info.split())} disponibles")
    
    # Test CrewAI
    print("\n🚢 Test CrewAI...")
    try:
        import crewai
        print(f"✅ Version : {crewai.__version__}")
    except Exception as e:
        print(f"❌ Erreur : {e}")

def main():
    """Fonction principale"""
    afficher_banniere()
    
    if not verifier_prerequis():
        print("\n❌ Prérequis non satisfaits. Arrêt du programme.")
        sys.exit(1)
    
    try:
        menu_principal()
    except KeyboardInterrupt:
        print("\n\n👋 Interruption utilisateur. Au revoir !")
    except Exception as e:
        print(f"\n❌ Erreur inattendue : {e}")

if __name__ == "__main__":
    main() 