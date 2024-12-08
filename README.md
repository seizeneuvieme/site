# 16/9 [BETA TEST]
## La newsletter des films cultes à voir directement depuis ton canapé 🍿

**🆕️ INSTALLATION DU PROJET**

**1°) Pré-requis**

Pour faire tourner la stack en local, installer :
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
- [PHP 8.1](https://www.php.net/releases/8.1/en.php)
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download)
- [Yarn](https://classic.yarnpkg.com/lang/en/docs/install/#mac-stable)
- [Docker](https://www.docker.com/)

**2°) Récupérer le projet**

Récupérer le projet en exécutant la commande suivante :

```
git clone git@github.com:seize9eme/site.git
```

**3°) Initialiser le projet**

Pour initialiser le projet, exécuter la commande suivante :
```
make install
make init-database # à lancer une seule fois lors de l'initialisation du projet
```

**4°) Démarrer le projet en local**

Pour démarrer le projet en local, exécuter la commande suivante :
```
make start
```

**5°) Lancer les tests**

Pour lancer les tests unitaires et fonctionnels, exécuter la commande suivante :
```
make test
```

**6°) Stopper le projet en local**

Pour stopper le projet en local, exécuter la commande suivante :
```
make stop
```

**⚙️ WORKFLOW**

Workflow à suivre pour chaque modification de code :

- Ouvrir une issue sur le board du projet
- Ouvrir une PR dédiée associée à l'issue
- La PR devra passer tout le workflow de CI avant de pouvoir être mergée sur la branche main
- On prendra soin de mettre à jour la documentation, le cas échéant

**🚀️ DEPLOIEMENT**

Pour déployer en production, lancer le script suivant : 

```
php prepare-deploy.php 
```

Cela va préparer une nouvelle branche de release qui pourra être récupérée sur le serveur de production.

Cette branche doit respecter la nomenclature suivante : `deploy/release-jj-mm-aaaa`

Se connecter ensuite au serveur de production puis récupérer le code de la branche :

```
ssh seizeeh@ssh.cluster030.hosting.ovh.net
git fetch origin deploy/release-jj-mm-aaaa #télécharge la branche distante
git checkout deploy/release-jj-mm-aaaa #se positionne sur la nouvelle branche en local
```

Enfin, exécuter les migrations en base de données puis nettoyer le cache : 

```
php bin/console doctrine:migrations:migrate 
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
php composer.phar dump-env prod
```

ℹ️ Une fois tout testé, supprimer la branche distante sur Github pour éviter tout accident (les branches de release ne doivent jamais être mergées) 
