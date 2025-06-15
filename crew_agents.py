#!/usr/bin/env python3
"""
🤖 Équipe d'Agents GeekBoard avec CrewAI
Équipe collaborative d'agents IA pour développer et maintenir GeekBoard
"""

import os
from crewai import Agent, Task, Crew, Process
from crewai_tools import SerperDevTool, FileReadTool, DirectoryReadTool

# Configuration
os.environ['OPENAI_API_BASE'] = 'http://localhost:11434/v1'
os.environ['OPENAI_API_KEY'] = 'ollama'  # Factice pour Ollama
os.environ['OPENAI_MODEL_NAME'] = 'codeqwen:7b'

# Outils disponibles
file_tool = FileReadTool()
directory_tool = DirectoryReadTool()

# 🎯 AGENT CHEF ORCHESTRATEUR
chef_orchestrateur = Agent(
    role='Chef de Projet IA',
    goal='Analyser les demandes utilisateur et coordonner l\'équipe d\'agents pour développer GeekBoard',
    backstory="""
    Tu es le chef d'équipe expérimenté qui comprend parfaitement GeekBoard.
    Tu analyses chaque demande et délègues aux bons agents spécialisés.
    Tu coordonnes les efforts et ensures que le projet avance efficacement.
    """,
    verbose=True,
    allow_delegation=True,
    tools=[file_tool, directory_tool]
)

# 🏗️ AGENT ARCHITECTE SYSTÈME
architecte_systeme = Agent(
    role='Architecte Système Senior',
    goal='Concevoir l\'architecture technique et gérer la migration PHP vers Next.js pour GeekBoard',
    backstory="""
    Tu es un architecte système expert spécialisé dans :
    - Migration progressive PHP vers Next.js 15.3 + React 19
    - Architecture API REST et base de données MySQL
    - Configuration PWA et optimisation performance
    - Patterns de sécurité et scalabilité
    
    Tu connais parfaitement le projet GeekBoard existant et ses besoins techniques.
    """,
    verbose=True,
    tools=[file_tool, directory_tool]
)

# 🎨 AGENT FRONTEND REACT
frontend_react = Agent(
    role='Développeur Frontend React Expert',
    goal='Créer l\'interface utilisateur moderne avec Next.js, React 19 et Tailwind CSS',
    backstory="""
    Tu es un expert React/Next.js spécialisé dans :
    - Composants React 19 avec hooks modernes
    - Interface responsive avec Tailwind CSS
    - PWA et optimisation mobile
    - UX/UI moderne et intuitive
    - Intégration avec APIs backend
    
    Tu développes des interfaces élégantes et performantes pour GeekBoard.
    """,
    verbose=True,
    tools=[file_tool, directory_tool]
)

# 🔧 AGENT BACKEND & API
backend_api = Agent(
    role='Développeur Backend & API',
    goal='Développer les APIs et gérer la migration progressive du backend PHP',
    backstory="""
    Tu es un expert backend spécialisé dans :
    - APIs REST avec PHP et transition vers Node.js/Next.js
    - Gestion base de données MySQL et optimisation
    - Authentification et sécurité
    - Migration progressive des modules métier
    
    Tu maintiens la compatibilité pendant la migration de GeekBoard.
    """,
    verbose=True,
    tools=[file_tool, directory_tool]
)

# ⚡ AGENT PERFORMANCE & TESTS
performance_tests = Agent(
    role='Expert Performance & Tests',
    goal='Optimiser les performances et garantir la qualité avec des tests automatisés',
    backstory="""
    Tu es un expert en :
    - Tests automatisés (unit, integration, e2e)
    - Optimisation performance frontend/backend
    - Monitoring et observabilité
    - CI/CD et déploiement
    
    Tu assures la qualité et les performances de GeekBoard.
    """,
    verbose=True,
    tools=[file_tool, directory_tool]
)

# 🚀 FONCTION PRINCIPALE D'ORCHESTRATION
def executer_demande(demande_utilisateur: str):
    """
    Point d'entrée principal pour traiter les demandes utilisateur
    """
    
    # Tâche d'analyse initiale par le chef
    tache_analyse = Task(
        description=f"""
        Analyse cette demande utilisateur pour GeekBoard : "{demande_utilisateur}"
        
        Détermine :
        1. L'objectif principal de la demande
        2. Quel(s) agent(s) spécialisé(s) doit/doivent intervenir
        3. L'ordre optimal des tâches
        4. Les priorités et dépendances
        
        Fournis un plan d'action structuré avec délégation appropriée.
        """,
        agent=chef_orchestrateur,
        expected_output="Plan d'action détaillé avec délégations d'agents"
    )
    
    # Création dynamique de l'équipe selon la demande
    crew = Crew(
        agents=[chef_orchestrateur, architecte_systeme, frontend_react, backend_api, performance_tests],
        tasks=[tache_analyse],
        process=Process.hierarchical,
        manager_agent=chef_orchestrateur,
        verbose=True
    )
    
    # Exécution
    print(f"\n🚀 Traitement de votre demande : {demande_utilisateur}")
    print("=" * 60)
    
    resultat = crew.kickoff()
    
    print("\n✅ Résultat :")
    print("=" * 60)
    print(resultat)
    
    return resultat

# 💬 INTERFACE INTERACTIVE
def interface_conversationnelle():
    """
    Interface simple pour interagir avec l'équipe d'agents
    """
    print("\n🤖 Équipe d'Agents GeekBoard - Interface Conversationnelle")
    print("=" * 60)
    print("💡 Exemples de demandes :")
    print("   • 'Optimise les performances de la liste des réparations'")
    print("   • 'Crée un composant de recherche de clients'")
    print("   • 'Migre le module de facturation vers Next.js'")
    print("   • 'Ajoute des tests pour l'authentification'")
    print("\nTapez 'quit' pour quitter\n")
    
    while True:
        demande = input("🎯 Votre demande : ").strip()
        
        if demande.lower() in ['quit', 'exit', 'q']:
            print("👋 Au revoir !")
            break
            
        if demande:
            try:
                executer_demande(demande)
            except Exception as e:
                print(f"❌ Erreur : {e}")
        
        print("\n" + "="*60 + "\n")

if __name__ == "__main__":
    interface_conversationnelle() 