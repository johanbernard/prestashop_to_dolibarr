Marche à suivre :

Cote Prestashop :
1. Desinstaller toute ancienne version de "Prestashop to Dolibarr"

2. Si vous faites l'installation manuellement : copier le repertoire "prestashop_to_dolibarr" dans le repertoire "modules/" de votre Prestashop

3. Installer le module "Prestashop to dolibarr" (Outils de migration)

4. Suivez la marche a suivre :

4.1. Telecharger le module "dolipresta" via le lien fourni dans le module Prestashop To Dolibarr

Cote Dolibarr :
4.2. Dezipper le fichier "dolipresta.zip" dans le repertoire "htdocs/" de votre Dolibarr.

4.3. Activer le module "dolipresta" dans Dolibarr (Accueil -> Configuration -> Modules -> onglet "Modules Interfaces" -> dolipresta)

4.4. Configurer la cle qui sera utilisee dans les Webservices dans la fenetre de configuration de "dolipresta" (symbole "outils" a droite, notez la : elle sera parametree dans Prestashop)


NB : Si vous voulez exporter le stock des produits de Prestashop vers Dolibarr, n'oubliez pas de configurer un entrepot dans Dolibarr et de reporter son nom dans les parametres d'exportation du module "Prestashop to Dolibarr"