# Le Réhausseur [MVP]
## _Des films à la hauteur des enfants_

![Code analysis](https://github.com/lerehausseur/site/actions/workflows/code-analysis.yaml/badge.svg)
![Continuous integration](https://github.com/lerehausseur/site/actions/workflows/ci.yaml/badge.svg)

**🌐 CONTEXTE**

À travers sa newsletter, “Le Réhausseur” conseille aux parents des films à montrer à leurs enfants : cinéma, télévision, plateformes de streaming (Netflix, Disney+, Prime Vidéo, OCS…), Internet... tous les médias sont passés au peigne fin !

**Site internet:** [Le Réhausseur](https://www.lerehausseur.fr)

**🎯 OBJECTIFS**

Le MVP doit permetttre :

- De s'inscrire à la newsletter
- De gérer son abonnement (RGPD compliant)
- De créer des campagnes de newsletters

**🧰 CHOIX DES OUTILS**

- Symfony pour l'application, suivant un modèle MVC classique
- Sendinblue pour l'envoi des mails transactionnels et des newsletters
- Un accent particulier sera mis sur la CI/CD

**🗺 PARCOURS UTILISATEURS**

**1°) Parcours abonné :**

- Accède au site le Réhausseur
- S'inscrit sur le site
- Reçoit un mail de confirmation / bienvenue pour activer son compte
- Peut gérer ses informations et son abonnement depuis son espace client (résiliation, ajout d'un enfant, ajout d'une plateforme de streaming payante...)
- Reçoit une newsletter tant que l'abonnement est actif
- Peut supprimer son compte et se désabonner de la newsletter

**2°) Parcours administrateur :**
- Se connecte à Sendinblue
- Crée un template email
- Se connecte au backoffice de Réhausseur
- Crée une campagne avec l'id du template Sendinblue
- Teste l'envoi d'une campagne sur une adresse de test
- Programme l'envoi l'envoi de la campagne
- Les envois de mail seront déclenchés par un CRON quotidien qui déclenchera les appels à Sendinblue
- Consulte les statistiques de la campagne

**🆕️ INSTALLATION DU PROJET**

**1°) Pré-requis**

Pour faire tourner la stack en local, vous devrez installer :
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
- [PHP 8.1](https://www.php.net/releases/8.1/en.php)
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download)
- [Yarn](https://classic.yarnpkg.com/lang/en/docs/install/#mac-stable)
- [Docker](https://www.docker.com/)

**2°) Récupérer le projet**

Récupérer le projet en exécutant la commande suivante :

```
git clone git@github.com:lerehausseur/site.git
```

**3°) Initialiser le projet**

Pour initialiser le projet, exécutez la commande suivante :
```
make install
make init-db # à lancer une seule fois lors de l'initialisation du projet
```

**4°) Démarrer le projet en local**

Pour démarrer le projet en local, exécutez la commande suivante :
```
make start
```

Le site sera alors accessible sur [http://localhost:8000/](http://localhost:8000/)

**⚙️ WORKFLOW**

Pour chaque modification de code, on prendra soin de :

- Ouvrir une issue sur le board du projet
- Ouvrir une PR dédiée associée à l'issue
- La PR devra passer tout le workflow de CI avant de pouvoir être mergée sur la branche main
- On prendra soin de mettre à jour la documentation, le cas échéant

**🚀️ DEPLOIEMENT**

TODO

